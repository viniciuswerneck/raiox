<?php

namespace App\Services;

use App\Models\AiKey;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * LlmRouterService v2.0.0
 * O Cérebro de Roteamento de IA.
 * Faz rotação exaustiva entre todas as chaves de API e modelos configurados.
 */
class LlmRouterService
{
    protected array $profiles = [
        'fast' => [
            ['provider' => 'groq', 'model' => 'llama-3.3-70b-versatile', 'url' => 'https://api.groq.com/openai/v1/chat/completions', 'timeout' => 20],
            ['provider' => 'openrouter', 'model' => 'google/gemini-2.0-flash-001', 'url' => 'https://openrouter.ai/api/v1/chat/completions', 'timeout' => 30],
            ['provider' => 'google', 'model' => 'gemini-1.5-flash', 'url' => null, 'timeout' => 30],
        ],
        'creative' => [
            ['provider' => 'google', 'model' => 'gemini-1.5-pro', 'url' => null, 'timeout' => 60],
            ['provider' => 'google', 'model' => 'gemini-1.5-flash', 'url' => null, 'timeout' => 60],
            ['provider' => 'openrouter', 'model' => 'google/gemini-2.0-flash-001', 'url' => 'https://openrouter.ai/api/v1/chat/completions', 'timeout' => 45],
            ['provider' => 'groq', 'model' => 'llama-3.3-70b-versatile', 'url' => 'https://api.groq.com/openai/v1/chat/completions', 'timeout' => 30],
        ],
        'reasoning' => [
            ['provider' => 'openrouter', 'model' => 'google/gemini-2.0-flash-thinking-exp-1219:free', 'url' => 'https://openrouter.ai/api/v1/chat/completions', 'timeout' => 60],
            ['provider' => 'openrouter', 'model' => 'deepseek/deepseek-r1:free', 'url' => 'https://openrouter.ai/api/v1/chat/completions', 'timeout' => 90],
        ],
    ];

    public function chat(array $messages, string $profile = 'fast', array $context = [])
    {
        $configs = $this->profiles[$profile] ?? $this->profiles['fast'];
        $agentName = $context['agent_name'] ?? 'LlmRouter';
        $agentVersion = $context['agent_version'] ?? '2.0.0';

        // 1. Cache para economia de tokens
        $cacheKey = 'llm_router_res_'.md5(json_encode($messages).$profile);
        if ($cached = Cache::get($cacheKey)) {
            Log::info("LlmRouter: Cache Hit para {$agentName}");

            return $cached;
        }

        // 2. Tentar cada configuração (Modelo/Provedor)
        foreach ($configs as $cfg) {
            $provider = $cfg['provider'];
            $model = $cfg['model'];

            // 3. Rotação exaustiva de chaves para este provedor
            $keys = $this->getAvailableKeys($provider);

            if ($keys->isEmpty()) {
                Log::warning("LlmRouter: Nenhuma chave disponível para o provedor [{$provider}]");

                continue;
            }

            foreach ($keys as $keyRecord) {
                // Check if this specific key+model pair is in cooldown
                $cooldownKey = 'llm_cool_'.md5($keyRecord->id.$model);
                if (Cache::has($cooldownKey)) {
                    continue;
                }

                try {
                    Log::info("LlmRouter: Tentando [{$model}] via [{$provider}] com chave ID #{$keyRecord->id}");
                    $startTime = microtime(true);

                    $response = $this->executeRequest($cfg, $messages, $keyRecord->key);
                    $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

                    if ($response && isset($response['choices'][0]['message']['content'])) {
                        // Sucesso! Atualiza telemetria e última utilização da chave
                        $keyRecord->update(['last_used_at' => now()]);
                        $this->logTelemetry($cfg, $response, $responseTimeMs, 'success', null, $agentName, $agentVersion, $keyRecord->id);

                        Cache::put($cacheKey, $response, now()->addHours(6));

                        return $response;
                    }

                    // Resposta inválida mas sem exceção
                    $this->setCooldown($keyRecord->id, $model, 30);
                    $this->logTelemetry($cfg, null, $responseTimeMs, 'invalid', 'Resposta vazia ou inválida', $agentName, $agentVersion, $keyRecord->id);

                } catch (\Exception $e) {
                    $errorMsg = $e->getMessage();
                    Log::error("LlmRouter Error [{$model}] (Key #{$keyRecord->id}): ".$errorMsg);

                    // Se for limite de quota (429), aplica cooldown maior na chave
                    $cooldownTime = str_contains($errorMsg, '429') ? 300 : 60;
                    $this->setCooldown($keyRecord->id, $model, $cooldownTime);

                    $this->logTelemetry($cfg, null, 0, 'error', $errorMsg, $agentName, $agentVersion, $keyRecord->id);

                    // Continua para a próxima chave ou próximo modelo
                    continue;
                }
            }
        }

        Log::error("LlmRouter: Falha exaustiva. Todos os modelos e chaves falharam para o perfil [{$profile}].");

        return null;
    }

    protected function getAvailableKeys(string $provider)
    {
        $dbProvider = ($provider === 'google') ? 'gemini' : $provider;

        return AiKey::where('is_active', true)
            ->where('provider', $dbProvider)
            ->where(function ($query) {
                $query->whereNull('cooldown_until')
                    ->orWhere('cooldown_until', '<=', now());
            })
            ->orderBy('last_used_at', 'asc') // Menos usada primeiro
            ->get();
    }

    protected function executeRequest(array $cfg, array $messages, string $apiKey)
    {
        if ($cfg['provider'] === 'google') {
            return $this->executeGoogleRequest($cfg, $messages, $apiKey);
        }

        // OpenAI Compatible (Groq, OpenRouter)
        $response = Http::when(app()->isProduction(), fn ($h) => $h, fn ($h) => $h->withoutVerifying())
            ->withToken($apiKey)
            ->timeout($cfg['timeout'] ?? 30)
            ->post($cfg['url'], [
                'model' => $cfg['model'],
                'messages' => $messages,
                'temperature' => 0.7,
            ]);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception("HTTP Error [{$cfg['provider']}]: ".$response->status().' | '.$response->body());
    }

    protected function executeGoogleRequest(array $cfg, array $messages, string $apiKey)
    {
        $model = $cfg['model'];
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $contents = [];
        $systemInstruction = null;

        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $systemInstruction = ['parts' => [['text' => $msg['content']]]];

                continue;
            }
            $contents[] = [
                'role' => $msg['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $msg['content']]],
            ];
        }

        $payload = [
            'contents' => $contents,
            'generationConfig' => ['temperature' => 0.7, 'maxOutputTokens' => 2048],
            'systemInstruction' => $systemInstruction,
        ];

        $response = Http::when(app()->isProduction(), fn ($h) => $h, fn ($h) => $h->withoutVerifying())
            ->timeout($cfg['timeout'] ?? 30)
            ->post($url, $payload);

        if ($response->successful()) {
            $data = $response->json();

            return [
                'choices' => [
                    [
                        'message' => [
                            'content' => $data['candidates'][0]['content']['parts'][0]['text'] ?? '',
                        ],
                    ],
                ],
                'usage' => [
                    'prompt_tokens' => $data['usageMetadata']['promptTokenCount'] ?? 0,
                    'completion_tokens' => $data['usageMetadata']['candidatesTokenCount'] ?? 0,
                    'total_tokens' => $data['usageMetadata']['totalTokenCount'] ?? 0,
                ],
            ];
        }

        throw new \Exception('Google API Error: '.$response->status().' | '.$response->body());
    }

    protected function setCooldown(int $keyId, string $model, int $seconds)
    {
        Cache::put('llm_cool_'.md5($keyId.$model), true, $seconds);
    }

    protected function logTelemetry($cfg, $res, $time, $status, $error, $agent, $version, $keyId)
    {
        try {
            DB::table('llm_logs')->insert([
                'user_id' => auth()->id(),
                'ai_key_id' => $keyId,
                'provider' => $cfg['provider'],
                'model' => $cfg['model'],
                'prompt_tokens' => $res['usage']['prompt_tokens'] ?? ($res['usage']['prompt_token_count'] ?? 0),
                'completion_tokens' => $res['usage']['completion_tokens'] ?? ($res['usage']['completion_token_count'] ?? 0),
                'total_tokens' => $res['usage']['total_tokens'] ?? ($res['usage']['total_token_count'] ?? 0),
                'response_time_ms' => $time,
                'status' => $status,
                'error_message' => $error,
                'agent_name' => $agent,
                'agent_version' => $version,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Telemetry fail: '.$e->getMessage());
        }
    }
}
