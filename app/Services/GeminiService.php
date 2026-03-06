<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected $apiKeys = [];
    protected $models = [
        'gemini-2.0-flash',
        'gemini-2.0-flash-lite',
        'gemini-2.0-flash-001',
        'gemini-2.0-flash-lite-001',
        'gemma-3-1b-it',
    ];
    protected $currentModelIndex = 0;

    public function __construct()
    {
        // Pega a chave principal
        if ($mainKey = env('GEMINI_API_KEY')) {
            $this->apiKeys[] = $mainKey;
        }

        // Tenta encontrar chaves numeradas (GEMINI_API_KEY_0, _1, _2, etc)
        for ($i = 0; $i <= 10; $i++) {
            $key = env("GEMINI_API_KEY_{$i}");
            if (!empty($key) && !in_array($key, $this->apiKeys)) {
                $this->apiKeys[] = $key;
            }
        }
    }

    private function getBaseUrl($model)
    {
        return "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";
    }

    public function generateNeighborhoodSummary(string $wikiText, string $location = '', array $aactContext = []): ?array
    {
        if (empty($this->apiKeys)) {
            Log::error('Gemini API Error: Nenhuma API Key configurada.');
            return null;
        }

        $locationContext = $location ? "sobre a cidade/bairro de **{$location}**" : '';
        
        // Estruturação do contexto AACT / NCC
        $categoria = $aactContext['categoria'] ?? 'Não classificada';
        $poisCount = $aactContext['pois_count'] ?? 0;
        $income = $aactContext['renda'] ?? 0;
        $safety = $aactContext['safety_level'] ?? 'MODERADO';
        
        $wikiSub = substr($wikiText, 0, 4000);

        $prompt = <<<PROMPT
AJA COMO UM AUDITOR TERRITORIAL. LOCAL: {$location}
DADOS REAIS: Categoria: {$categoria}, Renda: R$ {$income}, Sugurança: {$safety}.

INSTRUÇÕES:
1. Gere história real no campo "historia" (máx 2 parágrafos).
2. Campo "nivel_seguranca" deve ser exatamente "{$safety}".
3. RETORNE APENAS O JSON ABAIXO.

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

        $lastValidResult = null;

        foreach ($this->models as $model) {
            $baseUrl = $this->getBaseUrl($model);
            
            // Loop de failover nas chaves de API disponíveis
            foreach ($this->apiKeys as $index => $apiKey) {
                try {
                    Log::info("Gemini: Tentando requisicao com [{$model}] e API Key #" . ($index + 1));
                    
                    $response = Http::withoutVerifying()
                        ->timeout(45)
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

                            if (json_last_error()  !== JSON_ERROR_NONE) {
                                // Tenta limpar caracteres não-UTF8 e quebras de linha que quebram o JSON
                                $result = mb_convert_encoding($result, 'UTF-8', 'UTF-8'); 
                                
                                // Se o JSON parece truncado (não termina com }), tentamos fechar
                                if (!str_ends_with(trim($result), '}')) {
                                    $result = rtrim(trim($result), " ,\"\n\r\t");
                                    $openBraces = substr_count($result, '{') - substr_count($result, '}');
                                    for ($i = 0; $i < $openBraces; $i++) {
                                        $result .= '}';
                                    }
                                }

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
                                    Log::warning("Gemini Quality Fail no modelo [{$model}] na Key #" . ($index + 1) . ". Texto curto (" . mb_strlen($historia) . " chars).");
                                    continue; 
                                }

                                return $json;
                            } else {
                                Log::error("Gemini Parse JSON Error no modelo [{$model}] na Key #" . ($index + 1));
                                Log::warning("Raw Result: " . substr($result, 0, 500));
                                continue;
                            }
                        }
                    }

                    $errorBody = $response->body();
                    $status = $response->status();
                    Log::warning("Gemini API Falhou no modelo [{$model}] na Key #" . ($index + 1) . ". Status: {$status} | Error: " . substr($errorBody, 0, 150));

                    // Se for erro de quota (429), apenas continua para a próxima chave/modelo
                    // Se for erro de modelo não encontrado (404), pula logo para o próximo modelo
                    if ($status === 404) {
                        break; 
                    }

                } catch (\Exception $e) {
                    Log::warning("Gemini API Exception no modelo [{$model}] na Key #" . ($index + 1) . ": " . $e->getMessage());
                }
            }
            
            // Se conseguimos ao menos um resultado curto, e todos os proxs modelos/chaves falharem,
            // vamos usar o que temos. Mas aqui, se temos um result, retornamos.
            if ($lastValidResult) {
                Log::info("Gemini: Usando o melhor resultado disponivel do modelo [{$model}].");
                return $lastValidResult;
            }
        }

        Log::error("Gemini: Todos os modelos e chaves falharam totalmente.");
        return null;
    }

    public function generateComparisonAnalysis(array $dataA, array $dataB): ?string
    {
        if (empty($this->apiKeys)) return null;

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
            foreach ($this->apiKeys as $apiKey) {
                try {
                    $response = Http::withoutVerifying()->timeout(30)
                        ->post("{$baseUrl}?key={$apiKey}", [
                            'contents' => [['parts' => [['text' => $prompt]]]]
                        ]);

                    if ($response->successful()) {
                        $res = $response->json();
                        return $res['candidates'][0]['content']['parts'][0]['text'] ?? null;
                    }
                } catch (\Exception $e) {
                    Log::warning("Gemini Comparison Error no modelo [{$model}]: " . $e->getMessage());
                }
            }
        }

        return "As regiões apresentam perfis distintos. A Região A foca em {$dataA['class']} enquanto a Região B se destaca como {$dataB['class']}.";
    }
}
