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
VOCÊ É UM EXPERT EM HISTÓRIA URBANA E ANALISTA IMOBILIÁRIO SÊNIOR.
Sua missão é criar o "Raio-X Definitivo" para o local: **{$location}**.

### REGRAS CRÍTICAS E ABSOLUTAS:
1. **TAMANHO OBRIGATÓRIO (4 A 8 PARÁGRAFOS LONGOS)**: O campo "historia" no JSON deve ser OBRIGATORIAMENTE LONGO. Gere entre 4 e 8 parágrafos densos e completos (no mínimo 400 palavras totais). Sob nenhuma circunstância entregue apenas 1, 2 ou 3 parágrafos curtos.
2. **NÃO RESUMA NO JSON (Expanda usando seu conhecimento interno)**: Modelos tendem a gerar textos curtos dentro de campos JSON. VOCÊ ESTÁ PROIBIDO DE RESUMIR. Mesmo que o "Texto de Apoio" abaixo seja super curto (ou seja apenas um comando), você DEVE preencher as lacunas ativando agressivamente o seu vasto diretório interno de informações, detalhando:
   - História original, primeiros moradores e loteamentos.
   - Clima, relevo, e padrão arquitetônico (tipos de casas/prédios, arborização).
   - Movimento comercial principal, infraestrutura principal e mobilidade.
   - Pontos de interesse clássicos: praças, parques, feiras, opções de lazer e gastronomia.
   - Perfil social: quem mora? É um bairro/cidade de qual classe predominante? Como é a "alma" do lugar?
3. **ESTILO NARRATIVO**: Você está escrevendo para uma revista especializada em bairros (Ex: Veja São Paulo, Monocle, Quatro Rodas). O tom deve ser engajador, vivo, acadêmico e comercial. 

### FORMATO DE RETORNO (JSON APENAS):
{
  "historia": "Substitua isto pelo texto ESTRITAMENTE LONGO (4 a 8 parágrafos). Use OBRIGATORIAMENTE os literais '\\n\\n' para separar os parágrafos dentro do valor string do JSON. Expanda, seja minucioso, prolixo nos bons detalhes e mostre que você domina este local.",
  "nivel_seguranca": "ALTO", "MODERADO" ou "BAIXO" (Classifique de forma imparcial via índices criminais reais),
  "descricao_seguranca": "Análise técnica em texto corrido (pelo menos 3 ou 4 frases extensas) sobre policiamento, iluminação e a percepção real de medo ao andar na rua de noite.",
  "mercado_imobiliario": {
    "preco_m2": "Estimativa real em reais do Preço/m² na região ex: R$ 5.000 a R$ 8.000",
    "perfil_imoveis": "Breve frase descrevendo o que domina. Ex: Maioria de Apartamentos compactos, ou Condomínios Fechados de Alto Padrão",
    "tendencia_valorizacao": "ALTA", "ESTAVEL" ou "BAIXA"
  }
}

Texto de Apoio (Texto base de referência inicial):
{$wikiText}
PROMPT;

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
                        
                        // Clean control characters BUT preserve real unicode, newlines, and tabs inside strings
                        // Often APIs return invalid JSON if quotes or newlines aren't properly escaped in strings.
                        // Let's rely on json_decode first, but if it fails, fallback to simple cleanups.
                        
                        Log::info("Gemini JSON Response (Success on Key #" . ($index + 1) . "): " . substr($result, 0, 100) . "...");
                        
                        // Encontra o json real extraindo apenas de '{' até '}'
                        if (preg_match('/\{.*\}/s', $result, $matches)) {
                            $result = $matches[0];
                        }
                        
                        // =========================================================================
                        // ⚠️ ATENÇÃO: NÃO ALTERE A LÓGICA DE PARSING DE JSON ABAIXO ⚠️
                        // O Gemini 2.5 Flash retorna blocos de JSON inválidos misturados com texto
                        // corrompido, ISO-8859-1 e novas linhas literais não escapadas.
                        // O fluxo abaixo (Regex robusto + Invalid_UTF8_Ignore) foi montado com muito
                        // suor para suportar o retorno da IA sem crashar no json_decode do PHP 8.
                        // Modificar qualquer parte abaixo pode quebrar a captura do histórico.
                        // =========================================================================
                        
                        $json = json_decode($result, true);

                        if (json_last_error() !== JSON_ERROR_NONE) {
                            // Erro típico do Gemini: enviar quebras de linha reais (literais) dentro de strings do JSON, o que quebra o parser.
                            // Substituímos qualquer quebra de linha literal ou carriage return por um espaço
                            $result = str_replace(["\r\n", "\r", "\n"], " ", $result);
                            
                            // Removemos caracteres de controle invisíveis (0 a 31) que também invalidam o JSON
                            $result = preg_replace('/[\x00-\x1F\x7F]+/', '', $result);
                            
                            $json = json_decode($result, true);
                        }

                        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                            return $json;
                        } else {
                            Log::error("Gemini Parse JSON Error: " . json_last_error_msg() . " | Code: " . json_last_error() . " | Text: " . mb_substr($result, 0, 300));
                            return null;
                        }
                    }
                }

                // Se houver erro, logar e continar o loop para tentar a próxima chave
                $errorBody = $response->body();
                Log::warning("Gemini API Falhou na Key #" . ($index + 1) . ". Status: " . $response->status() . " Body: " . substr($errorBody, 0, 200));

            } catch (\Exception $e) {
                Log::warning('Gemini API Exception na Key #' . ($index + 1) . ': ' . $e->getMessage());
            }
        }

        Log::error("Gemini: Todas as chaves (" . count($this->apiKeys) . ") falharam.");
        return null;
    }
}
