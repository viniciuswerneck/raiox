<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected $apiKeys = [];
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';

    public function __construct()
    {
        // Pega todas as chaves do .env (com failover automático)
        $keys = [
            env('GEMINI_API_KEY'),
            env('GEMINI_API_KEY_0'),
            env('GEMINI_API_KEY_2'),
            env('GEMINI_API_KEY_3'),
            env('GEMINI_API_KEY_4'),
        ];

        foreach ($keys as $key) {
            if (!empty($key) && !in_array($key, $this->apiKeys)) {
                $this->apiKeys[] = $key;
            }
        }
    }

    public function generateNeighborhoodSummary(string $wikiText, string $location = ''): ?array
    {
        if (empty($this->apiKeys)) {
            Log::error('Gemini API Error: Nenhuma API Key configurada.');
            return null;
        }

        $locationContext = $location ? "sobre a cidade/bairro de **{$location}**" : '';

        $prompt = <<<PROMPT
VOCÊ É UM AUDITOR NARRATIVO (AAN) E UM ANALISTA IMOBILIÁRIO SÊNIOR ESTABELECENDO UM "RAIO-X DEFINITIVO".
Local Base: **{$location}**.

O CEP É UMA UNIDADE MICRO. A CIDADE É APENAS CONTEXTO MACRO. O macro NUNCA pode sobrescrever o micro.

### REGRAS CRÍTICAS DE AUDITORIA NARRATIVA P0 (AAN):
1. **FATOR MICROTERRITORIAL ESTILOSAMENTE NARRATIVO (4+ Parágrafos)**: Gere uma "historia" descritiva densa, focada exclusivamente nas ruas do micro-espaço que compõe "{$location}". O texto deve ser informativo e não promocional. É expressamente PROIBIDA qualquer "glamourização" se o perfil for puramente popular/funcional.
2. **PROIBIÇÕES ABSOLUTAS**: 
   - ❌ NUNCA cite, compare ou mencione nomes de bairros alheios, famosos ou turísticos se não for o exato CEP pesquisado (ex: Não cite Copacabana se o bairro for Olaria, não cite Ponta Negra se o bairro for Pajuçara).
   - ❌ NUNCA generalize a narrativa puxando informações macro da capital inteira. A história e as descrições devem ser locais.
   - ❌ NUNCA assuma que a região tem edifícios verticalizados se a região indicar um padrão horizontal ou simples.
3. **MERCADO IMOBILIÁRIO RESTRITO**: Foque DENTRO de 1km a 2km. SE a região for carente / zona de comércio simples, o "preco_m2" e os perfis devem REJEITAR cotações de alto luxo ou bairro nobre. Preço deve ser estritamente coerente com uma classe média-modesta. 

### FORMATO DE RETORNO (JSON APENAS):
{
  "historia": "Substitua isto pelo texto ESTRITAMENTE LONGO (4 a 8 parágrafos). Use OBRIGATORIAMENTE os literais '\\n\\n' para separar os parágrafos dentro do valor string do JSON. Expanda, seja minucioso, prolixo nos bons detalhes e mostre que você domina este local.",
  "nivel_seguranca": "ALTO", "MODERADO" ou "BAIXO" (Classifique de forma imparcial via índices criminais reais),
  "descricao_seguranca": "Análise técnica em texto corrido (pelo menos 3 ou 4 frases extensas) sobre policiamento, iluminação e a percepção real de medo ao andar na rua de noite.",
  "mercado_imobiliario": {
    "preco_m2": "Estimativa MICROTERRITORIAL real em reais do Preço/m² na exata região informada (ex: R$ 6.000 a R$ 12.000). NUNCA use a média de toda a cidade.",
    "perfil_imoveis": "Classificação rígida do micro-terrotório (Comercial, Estudantil, Misto, Lajes Corporativas, Condomínios Fechados). Descreva as tipologias.",
    "tendencia_valorizacao": "ALTA", "ESTAVEL" ou "BAIXA"
  }
}

Texto de Apoio (Texto base de referência inicial):
{$wikiText}
PROMPT;

        $lastValidResult = null;

        // Loop de failover nas chaves de API disponíveis
        foreach ($this->apiKeys as $index => $apiKey) {
            try {
                Log::info("Gemini: Tentando requisicao com API Key #" . ($index + 1));
                
                $response = Http::withoutVerifying()
                    ->timeout(45)
                    ->withHeaders(['User-Agent' => 'RaioXNeighborhood/1.0'])
                    ->post("{$this->baseUrl}?key={$apiKey}", [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $prompt]
                                ]
                            ]
                        ],
                        'generationConfig' => [
                            'temperature'     => 0.9,
                            'maxOutputTokens' => 4096,
                            'responseMimeType' => 'application/json'
                        ]
                    ]);

                if ($response->successful()) {
                    $data   = $response->json();
                    $result = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                    if ($result) {
                        $result = trim($result);
                        if (str_starts_with($result, '```json')) $result = substr($result, 7);
                        if (str_starts_with($result, '```')) $result = substr($result, 3);
                        if (str_ends_with($result, '```')) $result = substr($result, 0, -3);
                        
                        $result = trim($result);
                        
                        // Encontra o json real extraindo apenas de '{' até '}'
                        if (preg_match('/\{.*\}/s', $result, $matches)) {
                            $result = $matches[0];
                        }
                        
                        $json = json_decode($result, true);

                        if (json_last_error() !== JSON_ERROR_NONE) {
                            $result = str_replace(["\r\n", "\r", "\n"], " ", $result);
                            $result = preg_replace('/[\x00-\x1F\x7F]+/', '', $result);
                            $json = json_decode($result, true);
                        }

                        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                            $historia = $json['historia'] ?? '';
                            $minChars = 800; // Mínimo de caracteres para um Raio-X de qualidade
                            
                            // Guardamos como último recurso
                            $lastValidResult = $json;

                            if (mb_strlen($historia) < $minChars) {
                                Log::warning("Gemini Quality Fail na Key #" . ($index + 1) . ". Texto curto (" . mb_strlen($historia) . " chars). Buscando qualidade maior...");
                                continue; 
                            }

                            return $json;
                        } else {
                            Log::error("Gemini Parse JSON Error na Key #" . ($index + 1));
                            continue;
                        }
                    }
                }

                $errorBody = $response->body();
                Log::warning("Gemini API Falhou na Key #" . ($index + 1) . ". Status: " . $response->status());

            } catch (\Exception $e) {
                Log::warning('Gemini API Exception na Key #' . ($index + 1) . ': ' . $e->getMessage());
            }
        }

        if ($lastValidResult) {
            Log::info("Gemini: Usando o melhor resultado curto disponivel como fallback final.");
            return $lastValidResult;
        }

        Log::error("Gemini: Todas as chaves (" . count($this->apiKeys) . ") falharam totalmente.");
        return null;
    }
}
