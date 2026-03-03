<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected $apiKey;
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key');
    }

    /**
     * Gera um resumo JSON rico sobre o local (história e nível de segurança), a partir do conteúdo da Wikipedia.
     *
     * @param string $wikiText  Conteúdo completo extraído da página da Wikipedia
     * @param string $location  Nome do local para personalizar o texto (ex: "Vila Madalena, São Paulo")
     * @return array|null       Array com chaves 'historia', 'nivel_seguranca', e 'descricao_seguranca'
     */
    public function generateNeighborhoodSummary(string $wikiText, string $location = ''): ?array
    {
        if (empty($this->apiKey)) {
            Log::error('Gemini API Error: API Key is missing.');
            return null;
        }

        $locationContext = $location ? "sobre a cidade/bairro de **{$location}**" : '';

        $prompt = <<<PROMPT
Atue como um especialista imobiliário e analista de segurança pública no Brasil. Baseado no texto da Wikipedia fornecido e no seu conhecimento {$locationContext}, gere um JSON com as seguintes chaves:
- "historia": Um texto envolvente e comercial de 2 parágrafos sobre a fundação e infraestrutura da região.
- "nivel_seguranca": Uma string curta, apenas "ALTO", "MODERADO" ou "BAIXO".
- "descricao_seguranca": Uma frase curta explicando o motivo dessa nota (ex: "Cidade com baixos índices de criminalidade" ou "Região metropolitana que exige atenção à noite").

IMPORTANTE: Retorne APENAS o JSON puro, sem formatação markdown (sem ```json), para que o PHP consiga fazer o parse com json_decode().

Texto da Wikipedia: {$wikiText}
PROMPT;

        try {
            $response = Http::withoutVerifying()
                ->timeout(45)
                ->withHeaders(['User-Agent' => 'RaioXNeighborhood/1.0'])
                ->post("{$this->baseUrl}?key={$this->apiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature'     => 0.7,
                        'maxOutputTokens' => 2048,
                        'responseMimeType' => 'application/json'
                    ]
                ]);

            if ($response->successful()) {
                $data   = $response->json();
                $result = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                if ($result) {
                    $result = trim($result);
                    // Retira a formatação de markdown se a IA ainda usar por engano
                    if (str_starts_with($result, '```json')) {
                        $result = substr($result, 7);
                    }
                    if (str_starts_with($result, '```')) {
                        $result = substr($result, 3);
                    }
                    if (str_ends_with($result, '```')) {
                        $result = substr($result, 0, -3);
                    }
                    
                    $result = trim($result);
                    Log::info("Gemini JSON Response: " . $result);
                    
                    $json = json_decode($result, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                        return $json;
                    } else {
                        Log::error("Gemini Parse JSON Error: " . json_last_error_msg() . " - " . $result);
                        return null;
                    }
                }
            }

            Log::error('Gemini API Error Response: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Gemini API Exception: ' . $e->getMessage());
            return null;
        }
    }
}
