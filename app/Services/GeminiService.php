<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected $models = [
        'gemini-2.5-flash-lite',
        'gemini-2.0-flash-lite',
        'gemini-2.0-flash',
        'gemini-1.5-flash',
        'gemini-1.5-flash-8b', // Backup com quota alta
    ];


    public function __construct()
    {
        // As chaves agora são carregadas sob demanda do banco de dados
    }

    private function getNextKey()
    {
        $key = \App\Models\AiKey::where('is_active', true)
            ->where('provider', 'gemini')
            ->where(function ($query) {
                // PHP Now() formatted for SQL comparison
                $now = now()->toDateTimeString();
                $query->whereNull('cooldown_until')
                      ->orWhere('cooldown_until', '<=', $now);
            })
            ->orderBy('last_used_at', 'asc')
            ->first();

        if (!$key) {
            // Se não achou nenhuma, vamos ver se tem alguma em cooldown curto (menos de 5 min)
            // e pegar a que vai liberar mais cedo
            $nextToRelease = \App\Models\AiKey::where('is_active', true)
                ->where('provider', 'gemini')
                ->whereNotNull('cooldown_until')
                ->orderBy('cooldown_until', 'asc')
                ->first();
                
            if ($nextToRelease) {
                $waitSecs = now()->diffInSeconds($nextToRelease->cooldown_until, false);
                Log::warning("Gemini: Nenhuma chave livre. Próxima em {$waitSecs}s (ID: {$nextToRelease->id})");
                
                if ($waitSecs < 10) {
                    Log::info("Gemini: Forçando uso da chave #{$nextToRelease->id} (Próxima livre em {$waitSecs}s)");
                    $nextToRelease->update(['cooldown_until' => null]);
                    return $nextToRelease;
                }
            } else {
                Log::error("Gemini: Nenhuma chave ATIVA cadastrada no banco de dados.");
            }
        }

        return $key;
    }

    private function logUsage($keyId, $model, $success, $error = null, $latency = null)
    {
        \App\Models\AiUsageLog::create([
            'ai_key_id'     => $keyId,
            'model'         => $model,
            'success'       => $success,
            'error_message' => $error,
            'latency_ms'    => $latency
        ]);

        // Limpeza de logs antigos (manter apenas últimas 24h conforme solicitado)
        \App\Models\AiUsageLog::where('created_at', '<', now()->subDay())->delete();
    }

    private function getBaseUrl($model)
    {
        return "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";
    }

    public function generateNeighborhoodSummary(string $wikiText, string $location = '', array $aactContext = []): ?array
    {
        $categoria = $aactContext['categoria'] ?? 'Não classificada';
        $income = $aactContext['renda'] ?? 0;
        $safety = $aactContext['safety_level'] ?? 'MODERADO';
        
        $contextLength = strlen($wikiText);
        Log::info("GeminiService: Gerando narrativa para {$location}. Contexto Wiki: {$contextLength} bytes.");
        
        $wikiSub = substr($wikiText, 0, 8000); // Aumentado para 8k para pegar mais dados se houver

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

        foreach ($this->models as $model) {
            $baseUrl = $this->getBaseUrl($model);
            $maxKeyTries = \App\Models\AiKey::where('is_active', true)->where('provider', 'gemini')->count();
            
            $availableKeys = \App\Models\AiKey::where('is_active', true)
                ->where('provider', 'gemini')
                ->where(function ($query) {
                    $query->whereNull('cooldown_until')
                          ->orWhere('cooldown_until', '<=', now());
                })->count();
            
            Log::info("GeminiService: Iniciando loop de modelos. Chaves disponíveis agora: {$availableKeys}");

            for ($i = 0; $i < $maxKeyTries; $i++) {
                // Se não for a primeira tentativa do loop, espera um pouco para não burst
                if ($i > 0) usleep(800000); // 0.8s de respiro entre chaves

                $aiKeyRecord = $this->getNextKey();
                if (!$aiKeyRecord) {
                    Log::error("Gemini Error: Nenhuma chave disponível para uso.");
                    break;
                }

                $apiKey = $aiKeyRecord->key;
                $startTime = microtime(true);

                try {
                    Log::info("Gemini: Usando chave [{$aiKeyRecord->id}] com modelo [{$model}]");
                    
                    // Marcar como usada
                    $aiKeyRecord->update(['last_used_at' => now()]);

                    $response = Http::when(app()->isProduction(), fn($h) => $h, fn($h) => $h->withoutVerifying())
                        ->timeout(15)
                        ->withHeaders(['User-Agent' => 'RaioXNeighborhood/1.0'])
                        ->post("{$baseUrl}?key={$apiKey}", [
                            'contents' => [
                                [
                                    'parts' => [
                                        ['text' => $prompt]
                                    ]
                                ]
                            ],
                            'generationConfig' => [
                                'temperature'     => 0.9,
                                'maxOutputTokens' => 4096
                            ]
                        ]);

                    $latency = (int)((microtime(true) - $startTime) * 1000);

                    if ($response->successful()) {
                        $data   = $response->json();
                        $result = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                        if ($result) {
                            $result = trim($result);
                            if (str_starts_with($result, '```json')) $result = substr($result, 7);
                            if (str_starts_with($result, '```')) $result = substr($result, 3);
                            if (str_ends_with($result, '```')) $result = substr($result, 0, -3);
                            $result = trim($result);
                            
                            if (preg_match('/\{.*\}/s', $result, $matches)) {
                                $result = $matches[0];
                            }
                            
                            $json = json_decode($result, true);

                            if (json_last_error() !== JSON_ERROR_NONE) {
                                $result = mb_convert_encoding($result, 'UTF-8', 'UTF-8'); 
                                if (!str_ends_with(trim($result), '}')) {
                                    $result = rtrim(trim($result), " ,\"\n\r\t");
                                    $openBraces = substr_count($result, '{') - substr_count($result, '}');
                                    for ($braceIdx = 0; $braceIdx < $openBraces; $braceIdx++) {
                                        $result .= '}';
                                    }
                                }
                                $result = str_replace(["\r\n", "\r", "\n"], " ", $result);
                                $result = preg_replace('/[\x00-\x1F\x7F]+/', '', $result);
                                $json = json_decode($result, true);
                            }

                            if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                                $this->logUsage($aiKeyRecord->id, $model, true, null, $latency);
                                return $json;
                            }
                        }
                    }

                    $status = $response->status();
                    $errorBody = $response->body();
                    
                    if ($status === 429) {
                        Log::warning("Gemini Key #{$aiKeyRecord->id} atingiu limite (429). Suspendendo por 5 minutos.");
                        $aiKeyRecord->update(['cooldown_until' => now()->addMinutes(5)]);
                    }

                    $this->logUsage($aiKeyRecord->id, $model, false, "Status: {$status} | Body: " . substr($errorBody, 0, 100), $latency);

                    if ($status === 404) {
                        Log::warning("Gemini: Modelo {$model} não encontrado para chave #{$aiKeyRecord->id} (404).");
                        continue; 
                    }

                } catch (\Exception $e) {
                    $latency = (int)((microtime(true) - $startTime) * 1000);
                    Log::warning("Gemini API Exception: " . $e->getMessage());
                    $this->logUsage($aiKeyRecord->id, $model, false, $e->getMessage(), $latency);
                }
            }
        }

        Log::error("Gemini: Todas as chaves e modelos falharam.");
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

        foreach ($this->models as $model) {
            $baseUrl = $this->getBaseUrl($model);
            $maxKeyTries = \App\Models\AiKey::where('is_active', true)->where('provider', 'gemini')->count();

            for ($i = 0; $i < $maxKeyTries; $i++) {
                if ($i > 0) usleep(500000); // 0.5s de respiro entre chaves

                $aiKeyRecord = $this->getNextKey();
                if (!$aiKeyRecord) break;

                $startTime = microtime(true);
                try {
                    $aiKeyRecord->update(['last_used_at' => now()]);
                    
                    $response = Http::when(app()->isProduction(), fn($h) => $h, fn($h) => $h->withoutVerifying())
                        ->timeout(15)
                        ->withHeaders(['User-Agent' => 'RaioXNeighborhood/1.0'])
                        ->post("{$baseUrl}?key={$aiKeyRecord->key}", [
                            'contents' => [['parts' => [['text' => $prompt]]]]
                        ]);

                    $latency = (int)((microtime(true) - $startTime) * 1000);
                    $status = $response->status();

                    if ($response->successful()) {
                        $res = $response->json();
                        $this->logUsage($aiKeyRecord->id, $model, true, null, $latency);
                        return $res['candidates'][0]['content']['parts'][0]['text'] ?? null;
                    }
                    
                    if ($status === 429) {
                        Log::warning("Gemini Analysis: Key #{$aiKeyRecord->id} atingiu limite (429). Suspendendo por 5 minutos.");
                        $aiKeyRecord->update(['cooldown_until' => now()->addMinutes(5)]);
                    }

                    $this->logUsage($aiKeyRecord->id, $model, false, "Status: " . $status, $latency);

                    if ($status === 404) {
                        Log::warning("Gemini Analysis: Modelo {$model} não encontrado para chave #{$aiKeyRecord->id} (404).");
                        continue; 
                    }

                } catch (\Exception $e) {
                    $latency = (int)((microtime(true) - $startTime) * 1000);
                    $this->logUsage($aiKeyRecord->id, $model, false, $e->getMessage(), $latency);
                }
            }
        }

        return "As regiões apresentam perfis distintos. A Região A foca em {$dataA['class']} enquanto a Região B se destaca como {$dataB['class']}.";
    }
}
