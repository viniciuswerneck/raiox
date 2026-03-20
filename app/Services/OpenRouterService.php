<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenRouterService
{
    protected $models = [
        'google/gemini-2.0-flash-001',
        'meta-llama/llama-3.3-70b-instruct',
        'anthropic/claude-3.1-sonnet',
        'deepseek/deepseek-chat',
    ];

    private function getNextKey()
    {
        $key = \App\Models\AiKey::where('is_active', true)
            ->where('provider', 'openrouter')
            ->where(function ($query) {
                $now = now()->toDateTimeString();
                $query->whereNull('cooldown_until')
                    ->orWhere('cooldown_until', '<=', $now);
            })
            ->orderBy('last_used_at', 'asc')
            ->first();

        if (! $key) {
            $nextToRelease = \App\Models\AiKey::where('is_active', true)
                ->where('provider', 'openrouter')
                ->whereNotNull('cooldown_until')
                ->orderBy('cooldown_until', 'asc')
                ->first();

            if ($nextToRelease) {
                $waitSecs = now()->diffInSeconds($nextToRelease->cooldown_until, false);
                Log::warning("OpenRouter: Nenhuma chave livre. Próxima em {$waitSecs}s (ID: {$nextToRelease->id})");

                if ($waitSecs < 10) {
                    $nextToRelease->update(['cooldown_until' => null]);

                    return $nextToRelease;
                }
            } else {
                Log::error('OpenRouter: Nenhuma chave ATIVA cadastrada no banco de dados.');
            }
        }

        return $key;
    }

    private function logUsage($keyId, $model, $success, $error = null, $latency = null)
    {
        \App\Models\AiUsageLog::create([
            'ai_key_id' => $keyId,
            'model' => $model,
            'success' => $success,
            'error_message' => $error,
            'latency_ms' => $latency,
        ]);

        \App\Models\AiUsageLog::where('created_at', '<', now()->subDay())->delete();
    }

    private function callOpenRouter(string $prompt, string $model, object $aiKeyRecord): ?string
    {
        $startTime = microtime(true);

        try {
            Log::info("OpenRouter: Usando chave [{$aiKeyRecord->id}] com modelo [{$model}]");
            $aiKeyRecord->update(['last_used_at' => now()]);

            $response = Http::when(app()->isProduction(), fn ($h) => $h, fn ($h) => $h->withoutVerifying())
                ->timeout(30)
                ->withHeaders([
                    'Authorization' => "Bearer {$aiKeyRecord->key}",
                    'Content-Type' => 'application/json',
                    'HTTP-Referer' => config('app.url'),
                    'X-Title' => config('app.name'),
                ])
                ->post('https://openrouter.ai/api/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 4096,
                ]);

            $latency = (int) ((microtime(true) - $startTime) * 1000);
            $status = $response->status();

            if ($response->successful()) {
                $result = $response->json()['choices'][0]['message']['content'] ?? null;
                $this->logUsage($aiKeyRecord->id, $model, true, null, $latency);

                return $result;
            }

            if ($status === 429) {
                Log::warning("OpenRouter Key #{$aiKeyRecord->id} atingiu limite (429). Suspendendo por 5 minutos.");
                $aiKeyRecord->update(['cooldown_until' => now()->addMinutes(5)]);
            }

            $this->logUsage($aiKeyRecord->id, $model, false, "Status: {$status} | Body: ".substr($response->body(), 0, 100), $latency);

        } catch (\Exception $e) {
            $latency = (int) ((microtime(true) - $startTime) * 1000);
            Log::warning('OpenRouter API Exception: '.$e->getMessage());
            $this->logUsage($aiKeyRecord->id, $model, false, $e->getMessage(), $latency);
        }

        return null;
    }

    private function parseJsonFromText(string $text): ?array
    {
        $text = trim($text);
        if (str_starts_with($text, '```json')) {
            $text = substr($text, 7);
        }
        if (str_starts_with($text, '```')) {
            $text = substr($text, 3);
        }
        if (str_ends_with($text, '```')) {
            $text = substr($text, 0, -3);
        }
        $text = trim($text);

        if (preg_match('/\{.*\}/s', $text, $matches)) {
            $text = $matches[0];
        }

        $json = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
            if (! str_ends_with(trim($text), '}')) {
                $text = rtrim(trim($text), " ,\"\n\r\t");
                $openBraces = substr_count($text, '{') - substr_count($text, '}');
                for ($i = 0; $i < $openBraces; $i++) {
                    $text .= '}';
                }
            }
            $text = str_replace(["\r\n", "\r", "\n"], ' ', $text);
            $text = preg_replace('/[\x00-\x1F\x7F]+/', '', $text);
            $json = json_decode($text, true);
        }

        return (json_last_error() === JSON_ERROR_NONE && is_array($json)) ? $json : null;
    }

    private function hasMinParagraphs(array $json, int $min = 3): bool
    {
        $historia = $json['historia'] ?? '';
        $paragraphs = array_filter(array_map('trim', explode("\n\n", $historia)));

        return count($paragraphs) >= $min;
    }

    private function enforceParagraphs(array $json, int $min = 3): array
    {
        $historia = $json['historia'] ?? '';
        $lines = array_filter(array_map('trim', explode("\n", $historia)));
        if (count($lines) >= $min) {
            $chunkSize = (int) ceil(count($lines) / $min);
            $chunks = array_chunk(array_values($lines), $chunkSize);
            $json['historia'] = implode("\n\n", array_map(fn ($c) => implode(' ', $c), $chunks));

            return $json;
        }
        $len = mb_strlen($historia);
        $partSize = (int) ceil($len / $min);
        $parts = [];
        for ($i = 0; $i < $min; $i++) {
            $parts[] = trim(mb_substr($historia, $i * $partSize, $partSize));
        }
        $json['historia'] = implode("\n\n", array_filter($parts));

        return $json;
    }

    public function generateNeighborhoodSummary(string $wikiText, string $location = '', array $aactContext = []): ?array
    {
        $categoria = $aactContext['categoria'] ?? 'Não classificada';
        $income = $aactContext['renda'] ?? 0;
        $safety = $aactContext['safety_level'] ?? 'MODERADO';

        $wikiSub = substr($wikiText, 0, 8000);

        $prompt = <<<PROMPT
AJA COMO UM AUDITOR TERRITORIAL E ANALISTA DE DADOS URBANOS. LOCAL: {$location}
DADOS REAIS: Categoria: {$categoria}, Renda: R$ {$income}, Sugurança: {$safety}.

INSTRUÇÕES DE ESCRITA:
1. Gere uma narrativa extremamente detalhada e técnica no campo "historia".
2. A narrativa DEVE ter OBRIGATORIAMENTE E EXATAMENTE 3 parágrafos longos.
3. USE EXATAMENTE O CARACTERE DE QUEBRA DE LINHA (\n\n) ENTRE OS 3 PARÁGRAFOS para garantir a formatação:
   - Parágrafo 1: Contexto histórico profundo e origem do local (use os dados da Wikipedia abaixo se houver).
   - Parágrafo 2: Descrição da atmosfera atual, perfil sociodemográfico e estilo de vida baseado na renda de R$ {$income} e categoria {$categoria}.
   - Parágrafo 3: Análise técnica do potencial de valorização imobiliária, segurança e infraestrutura projetada.
4. Campo "nivel_seguranca" deve ser exatamente "{$safety}".
5. RETORNE APENAS O JSON VÁLIDO.


JSON:
{
  "historia": "...",
  "nivel_seguranca": "{$safety}",
  "descricao_seguranca": "...",
  "mercado_imobiliario": {
    "preco_m2": "...",
    "perfil_imoveis": "...",
    "tendencia_valorizacao": "..."
  }
}

CONTEXTO:
{$wikiSub}
PROMPT;

        $models = $this->models;
        shuffle($models);
        $lastResortJson = null;

        foreach ($models as $model) {
            $maxKeyTries = \App\Models\AiKey::where('is_active', true)->where('provider', 'openrouter')->count();
            if ($maxKeyTries === 0) {
                break;
            }

            for ($i = 0; $i < $maxKeyTries; $i++) {
                if ($i > 0) {
                    usleep(800000);
                }

                $aiKeyRecord = $this->getNextKey();
                if (! $aiKeyRecord) {
                    break;
                }

                $raw = $this->callOpenRouter($prompt, $model, $aiKeyRecord);
                if (! $raw) {
                    continue;
                }

                $json = $this->parseJsonFromText($raw);
                if (! $json) {
                    continue;
                }

                $lastResortJson = $json;

                if ($this->hasMinParagraphs($json)) {
                    return $json;
                }

                Log::warning("OpenRouter [{$model}]: Narrativa curta. Tentando retry forçado.");
                $promptForcado = $prompt."\n\nATENÇÃO: O campo \"historia\" DEVE conter EXATAMENTE 3 parágrafos longos (\n\n).";
                $raw2 = $this->callOpenRouter($promptForcado, $model, $aiKeyRecord);

                if ($raw2) {
                    $json2 = $this->parseJsonFromText($raw2);
                    if ($json2 && $this->hasMinParagraphs($json2)) {
                        return $json2;
                    }
                    if ($json2) {
                        $lastResortJson = $json2;
                    }
                }
            }
        }

        if ($lastResortJson) {
            Log::warning('OpenRouter: Nenhum modelo entregou 3 parágrafos. Usando enforcement manual.');

            return $this->enforceParagraphs($lastResortJson);
        }

        return null;
    }

    public function generateComparisonAnalysis(array $dataA, array $dataB): ?string
    {
        $prompt = <<<PROMPT
VOCÊ É UM ANALISTA ESTRATÉGICO TERRITORIAL.
Sua tarefa é comparar dois microterritórios brasileiros e gerar uma análise sucinta (2 a 3 parágrafos).

DADOS DA REGIÃO A ({$dataA['cep']} - {$dataA['bairro']}, {$dataA['cidade']}):
- Categoria: {$dataA['class']}
- Renda Média: R$ {$dataA['income']}
- Infraestrutura: {$dataA['infra']} POIs
- Mobilidade: {$dataA['mobility']} POIs
- Lazer: {$dataA['leisure']} POIs

DADOS DA REGIÃO B ({$dataB['cep']} - {$dataB['bairro']}, {$dataB['cidade']}):
- Categoria: {$dataB['class']}
- Renda Média: R$ {$dataB['income']}
- Infraestrutura: {$dataB['infra']} POIs
- Mobilidade: {$dataB['mobility']} POIs
- Lazer: {$dataB['leisure']} POIs

REGRAS:
1. Compare os números e categorias de forma técnica e direta.
2. Identifique qual região é mais "completa" em termos de serviços.
3. Use um tom executivo e imparcial.
4. Responda APENAS com o texto da análise, sem saudações ou títulos.

FORMATO ESPERADO:
Texto corrido com parágrafos.
PROMPT;

        $models = $this->models;
        shuffle($models);

        foreach ($models as $model) {
            $maxKeyTries = \App\Models\AiKey::where('is_active', true)->where('provider', 'openrouter')->count();
            if ($maxKeyTries === 0) {
                break;
            }

            for ($i = 0; $i < $maxKeyTries; $i++) {
                if ($i > 0) {
                    usleep(500000);
                }

                $aiKeyRecord = $this->getNextKey();
                if (! $aiKeyRecord) {
                    break;
                }

                $result = $this->callOpenRouter($prompt, $model, $aiKeyRecord);
                if ($result) {
                    return $result;
                }
            }
        }

        return "As regiões apresentam perfis distintos. A Região A foca em {$dataA['class']} enquanto a Região B se destaca como {$dataB['class']}.";
    }
}
