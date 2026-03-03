<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected $apiKey;
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key');
    }

    /**
     * Generate an AI summary for a neighborhood based on Wikipedia text.
     */
    public function generateNeighborhoodSummary(string $wikiText): ?string
    {
        if (empty($this->apiKey)) {
            Log::error('Gemini API Error: API Key is missing.');
            return null;
        }

        try {
            // Prompt mais focado em RESUMIR e não apenas repetir
            $prompt = "REESCREVA o seguinte texto de forma atraente e humana para um site imobiliário. Fale sobre a história, o clima e por que é bom morar nesta região. Use no máximo 2 parágrafos. NÃO repita termos técnicos de leis ou decretos. Texto: " . $wikiText;

            Log::info("Gemini Requesting Summary for: " . substr($wikiText, 0, 50) . "...");

            $response = Http::withoutVerifying()
                ->timeout(30)
                ->withHeaders(['User-Agent' => 'RaioXNeighborhood/1.0'])
                ->post("{$this->baseUrl}?key={$this->apiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ]
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $result = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                if ($result) {
                    Log::info("Gemini Summary Generated successfully.");
                    return $result;
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
