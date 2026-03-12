<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TextReviserService
{
    private const REVISION_PROMPT = <<<'PROMPT'
Você é um assistente especializado em revisão de textos informativos sobre bairros e regiões urbanas do Brasil.

Tarefa:
Revise, corrija e melhore o texto fornecido.

Objetivos da revisão:
1. Corrigir erros gramaticais, ortográficos e de pontuação.
2. Melhorar a fluidez e clareza do texto.
3. Reduzir frases muito longas.
4. Remover repetições desnecessárias.
5. Substituir termos não naturais ou inventados por termos reais usados em urbanismo e geografia urbana.
6. Evitar classificações não oficiais como "ALTO FLUXO / ATENÇÃO".
7. Evitar dados excessivamente específicos sem fonte (ex: renda média com centavos).
8. Atualizar a linguagem para parecer natural e humana, evitando estilo típico de texto gerado por IA.
9. Manter o conteúdo informativo sobre história, comércio, perfil socioeconômico e infraestrutura da região.
10. Não inventar dados estatísticos.
11. OBRIGATÓRIO: O texto final DEVE ter exatamente 3 parágrafos separados por linha em branco.

Formato da resposta:
* Retorne apenas o texto corrigido.
* Não explique as mudanças.
* Não adicione comentários.
* TRÊS PARÁGRAFOS, separados por \n\n.

Texto para revisão:

PROMPT;

    /**
     * Revisa o campo 'historia' do resultado gerado por qualquer IA.
     * Garante 3 parágrafos e texto humanizado.
     * Nunca lança exceção — se falhar, retorna o texto original.
     */
    public function reviseHistoria(array $aiSummary): array
    {
        $originalHistoria = $aiSummary['historia'] ?? '';
        if (empty($originalHistoria)) return $aiSummary;

        $revised = $this->callWithGroq($originalHistoria)
            ?? $this->callWithOpenRouter($originalHistoria)
            ?? $this->callWithGemini($originalHistoria);

        if ($revised) {
            $revised = $this->enforceThreeParagraphs($revised, $originalHistoria);
            $aiSummary['historia'] = $revised;
            Log::info("TextReviser: Narrativa revisada com sucesso.");
        } else {
            Log::warning("TextReviser: Revisão falhou. Mantendo texto original.");
        }

        return $aiSummary;
    }

    private function callWithGroq(string $text): ?string
    {
        $key = \App\Models\AiKey::where('is_active', true)
            ->where('provider', 'groq')
            ->where(function ($q) {
                $q->whereNull('cooldown_until')->orWhere('cooldown_until', '<=', now());
            })
            ->orderBy('last_used_at', 'asc')
            ->first();

        if (!$key) return null;

        try {
            $response = Http::when(app()->isProduction(), fn($h) => $h, fn($h) => $h->withoutVerifying())
                ->timeout(20)
                ->withHeaders(['Authorization' => "Bearer {$key->key}", 'Content-Type' => 'application/json'])
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model'       => 'llama-3.3-70b-versatile',
                    'temperature' => 0.4,
                    'max_tokens'  => 2048,
                    'messages'    => [
                        ['role' => 'user', 'content' => self::REVISION_PROMPT . $text]
                    ]
                ]);

            if ($response->successful()) {
                $key->update(['last_used_at' => now()]);
                return $response->json()['choices'][0]['message']['content'] ?? null;
            }
        } catch (\Exception $e) {
            Log::warning("TextReviser Groq Exception: " . $e->getMessage());
        }

        return null;
    }

    private function callWithOpenRouter(string $text): ?string
    {
        $key = \App\Models\AiKey::where('is_active', true)
            ->where('provider', 'openrouter')
            ->where(function ($q) {
                $q->whereNull('cooldown_until')->orWhere('cooldown_until', '<=', now());
            })
            ->orderBy('last_used_at', 'asc')
            ->first();

        if (!$key) return null;

        try {
            $response = Http::when(app()->isProduction(), fn($h) => $h, fn($h) => $h->withoutVerifying())
                ->timeout(25)
                ->withHeaders([
                    'Authorization' => "Bearer {$key->key}",
                    'Content-Type'  => 'application/json',
                    'HTTP-Referer'  => config('app.url'),
                    'X-Title'       => config('app.name'),
                ])
                ->post('https://openrouter.ai/api/v1/chat/completions', [
                    'model'       => 'google/gemini-2.0-flash-001',
                    'temperature' => 0.4,
                    'max_tokens'  => 2048,
                    'messages'    => [
                        ['role' => 'user', 'content' => self::REVISION_PROMPT . $text]
                    ]
                ]);

            if ($response->successful()) {
                $key->update(['last_used_at' => now()]);
                return $response->json()['choices'][0]['message']['content'] ?? null;
            }
        } catch (\Exception $e) {
            Log::warning("TextReviser OpenRouter Exception: " . $e->getMessage());
        }

        return null;
    }

    private function callWithGemini(string $text): ?string
    {
        $key = \App\Models\AiKey::where('is_active', true)
            ->where('provider', 'gemini')
            ->where(function ($q) {
                $q->whereNull('cooldown_until')->orWhere('cooldown_until', '<=', now());
            })
            ->orderBy('last_used_at', 'asc')
            ->first();

        if (!$key) return null;

        try {
            $model    = 'gemini-2.0-flash-lite';
            $url      = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key->key}";
            $response = Http::when(app()->isProduction(), fn($h) => $h, fn($h) => $h->withoutVerifying())
                ->timeout(20)
                ->post($url, [
                    'contents'        => [['parts' => [['text' => self::REVISION_PROMPT . $text]]]],
                    'generationConfig' => ['temperature' => 0.4, 'maxOutputTokens' => 2048]
                ]);

            if ($response->successful()) {
                $key->update(['last_used_at' => now()]);
                return $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? null;
            }
        } catch (\Exception $e) {
            Log::warning("TextReviser Gemini Exception: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Garante que o texto revisado tenha pelo menos 3 parágrafos.
     * Se não tiver, usa o original como fallback.
     */
    private function enforceThreeParagraphs(string $revised, string $original): string
    {
        $paragraphs = array_filter(array_map('trim', explode("\n\n", $revised)));

        if (count($paragraphs) >= 3) {
            return $revised;
        }

        // Tenta dividir por \n simples
        $lines = array_filter(array_map('trim', explode("\n", $revised)));
        if (count($lines) >= 3) {
            $chunkSize = (int) ceil(count($lines) / 3);
            $chunks    = array_chunk(array_values($lines), $chunkSize);
            return implode("\n\n", array_map(fn($c) => implode(' ', $c), $chunks));
        }

        // Fallback: divide o original em 3 partes
        $source   = !empty($revised) ? $revised : $original;
        $len      = mb_strlen($source);
        $partSize = (int) ceil($len / 3);
        $parts    = [];
        for ($i = 0; $i < 3; $i++) {
            $parts[] = trim(mb_substr($source, $i * $partSize, $partSize));
        }

        return implode("\n\n", array_filter($parts));
    }
}
