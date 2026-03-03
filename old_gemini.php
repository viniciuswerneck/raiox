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
     * Gera um resumo rico e humanizado sobre o local, a partir do conteúdo da Wikipedia.
     *
     * @param string $wikiText  Conteúdo completo extraído da página da Wikipedia
     * @param string $location  Nome do local para personalizar o texto (ex: "Vila Madalena, São Paulo")
     */
    public function generateNeighborhoodSummary(string $wikiText, string $location = ''): ?string
    {
        if (empty($this->apiKey)) {
            Log::error('Gemini API Error: API Key is missing.');
            return null;
        }

        $locationContext = $location ? "Local analisado: **{$location}**." : '';
        $inputLength = strlen($wikiText);

        // Modo "livre": Wikipedia sem conteúdo suficiente — Gemini usa conhecimento próprio
        if ($inputLength < 300) {
            $prompt = <<<PROMPT
Você é um redator especialista em conteúdo imobiliário e jornalismo local.

{$locationContext}

Escreva um texto **original**, **rico** e **envolvente** com **4 parágrafos completos** sobre este local para potenciais moradores e investidores. Use todo o seu conhecimento sobre este município, bairro ou região — inclua história, características geográficas, cultura local, vocação econômica, infraestrutura, qualidade de vida, proximidade de centros urbanos relevantes e por que é interessante morar ou investir lá.

**Regras obrigatórias:**
- Escreva em português brasileiro, fluente e natural, como um artigo de revista de alto padrão.
- NÃO use listas, bullets ou subtítulos — apenas parágrafos corridos.
- NÃO comece com "Localizado" ou "Situada" — varie o início.
- Cada parágrafo deve ter ao menos 4 frases longas. O texto completo deve ter entre 350 e 500 palavras.
- Seja rico em detalhes concretos: nomes de bairros, distâncias, referências regionais, marcos históricos.
PROMPT;
            Log::info("Gemini: modo LIVRE para [{$location}] (Wikipedia muito curta: {$inputLength} chars).");
        } else {
            // Modo "referência": Wikipedia tem conteúdo suficiente
            $supplementInstruction = $inputLength < 800
                ? "ATENÇÃO: O texto de referência é relativamente breve. Além dos fatos fornecidos, **use seu próprio conhecimento sobre este local** para enriquecer o texto com detalhes regionais, culturais e históricos adicionais."
                : "Use o texto de referência como base factual, mas reescreva completamente com suas próprias palavras.";

            $prompt = <<<PROMPT
Você é um redator especialista em conteúdo imobiliário e jornalismo local.

{$locationContext}

Escreva um texto **original**, **rico** e **envolvente** com **3 a 4 parágrafos completos** sobre este local para potenciais moradores ou investidores.

{$supplementInstruction}

**Regras obrigatórias:**
- Escreva em português brasileiro, fluente e natural, como um artigo de revista de alto padrão.
- Aborde: história e formação do local, características da região, infraestrutura, qualidade de vida e por que é interessante morar ou investir lá.
- NÃO mencione a Wikipedia, nem cite "de acordo com", "segundo fontes" ou expressões similares.
- NÃO use listas, bullets ou subtítulos — apenas parágrafos corridos e bem escritos.
- NÃO repita termos técnicos legais (leis, decretos, portarias).
- Cada parágrafo deve ter ao menos 4 frases. O texto completo deve ter entre 300 e 500 palavras.
- Seja rico em detalhes culturais, geográficos e sociais.

**Texto de referência:**
{$wikiText}
PROMPT;
            Log::info("Gemini: modo REFERÊNCIA para [{$location}] com {$inputLength} chars de input.");
        }

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
                        'temperature'     => 0.75,
                        'maxOutputTokens' => 1024,
                    ]
                ]);

            if ($response->successful()) {
                $data   = $response->json();
                $result = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                if ($result) {
                    Log::info("Gemini: resumo gerado com sucesso (" . strlen($result) . " chars).");
                    return trim($result);
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
