<?php

namespace App\Http\Controllers;

use App\Services\NeighborhoodService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected $neighborhoodService;

    public function __construct(NeighborhoodService $neighborhoodService)
    {
        $this->neighborhoodService = $neighborhoodService;
    }

    public function search(Request $request)
    {
        $input = $request->input('cep');
        $cep = preg_replace('/\D/', '', $input);
        
        \Illuminate\Support\Facades\Log::info("SEARCH START: [{$input}]");

        // Se já é um CEP válido de 8 dígitos, redireciona direto
        if (strlen($cep) === 8) {
            \Illuminate\Support\Facades\Log::info("SEARCH: direct CEP redirect [{$cep}]");
            return redirect()->route('report.show', $cep);
        }

        // Se for um texto (endereço), tentamos descobrir o CEP via Nominatim
        if (strlen($input) >= 4) {
             // Limpeza básica para melhorar a busca
            $query = trim($input);
            
            try {
                // Tentativa 1: Busca Direta
                \Illuminate\Support\Facades\Log::info("SEARCH: strategy 1 (Direct) [{$query}]");
                $foundCep = $this->geocodeAddress($query);
                if ($foundCep) return redirect()->route('report.show', $foundCep);

                // Tentativa 2: Tirar vírgulas e tentar novamente
                $queryAlt = str_replace(',', ' ', $query);
                \Illuminate\Support\Facades\Log::info("SEARCH: strategy 2 (No Commas) [{$queryAlt}]");
                $foundCep = $this->geocodeAddress($queryAlt);
                if ($foundCep) return redirect()->route('report.show', $foundCep);

                // Tentativa 3: Adicionar vírgula antes da última palavra (provável cidade)
                $parts = explode(' ', $query);
                if (count($parts) > 1) {
                    $city = array_pop($parts);
                    $street = implode(' ', $parts);
                    $queryComma = "{$street}, {$city}";
                    \Illuminate\Support\Facades\Log::info("SEARCH: strategy 3 (Comma Insert) [{$queryComma}]");
                    $foundCep = $this->geocodeAddress($queryComma);
                    if ($foundCep) return redirect()->route('report.show', $foundCep);

                    // Tentativa 4: Se houver "Jarinu" ou similar, tentar pegar só a cidade como fallback
                    \Illuminate\Support\Facades\Log::info("SEARCH: strategy 4 (City Only) [{$city}]");
                    $foundCep = $this->geocodeAddress($city);
                    if ($foundCep) return redirect()->route('report.show', $foundCep);
                }

            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Geocode error for [{$query}]: " . $e->getMessage());
            }
        }
        
        return redirect()->route('home')->withErrors(['cep' => 'Endereço não localizado. Tente digitar apenas o nome da cidade ou o CEP.']);
    }

    private function geocodeAddress($q)
    {
        // Headers reais para evitar 403 do Nominatim
        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Referer' => 'https://google.com'
        ];

        // Normalização agressiva: "Avenida Independencia Jarinu" -> "Independencia Jarinu"
        $qClean = $this->normalizeSearchQuery($q);
        \Illuminate\Support\Facades\Log::info("GEOCODE: hitting Nominatim for [{$q}] (Cleaned: [{$qClean}])");

        $response = \Illuminate\Support\Facades\Http::withoutVerifying()
            ->timeout(8)
            ->withHeaders($headers)
            ->get("https://nominatim.openstreetmap.org/search", [
                'q' => "{$qClean}, Brazil",
                'format' => 'json',
                'addressdetails' => 1,
                'limit' => 5,
                'countrycodes' => 'br'
            ]);

        if ($response->successful() && !empty($response->json())) {
            \Illuminate\Support\Facades\Log::info("GEOCODE: Nominatim found " . count($response->json()) . " results.");
            foreach ($response->json() as $item) {
                // Estratégia 1: Postcode Direto (OSM)
                $postcode = $item['address']['postcode'] ?? null;
                if ($postcode) {
                    $found = preg_replace('/\D/', '', $postcode);
                    if (strlen($found) === 8) {
                         \Illuminate\Support\Facades\Log::info("GEOCODE: Found postcode in OSM [{$found}]");
                         return $found;
                    }
                }

                // Estratégia 2: Fallback via ViaCEP (Brasil-Only)
                $road = $item['address']['road'] ?? $item['address']['pedestrian'] ?? $item['address']['suburb'] ?? null;
                $city = $item['address']['city'] ?? $item['address']['town'] ?? $item['address']['village'] ?? null;
                $state = $item['address']['state'] ?? null;

                // Limpamos o road name para o ViaCEP (remover "Avenida", "Rua", etc)
                $roadClean = $this->normalizeSearchQuery($road ?? '');
                
                \Illuminate\Support\Facades\Log::info("GEOCODE: Components - Road: [{$road}] (Cleaned: {$roadClean}), City: [{$city}], State: [{$state}]");

                // Mapeamento de Estados
                $statesMap = [
                    'Acre' => 'AC', 'Alagoas' => 'AL', 'Amapá' => 'AP', 'Amazonas' => 'AM', 
                    'Bahia' => 'BA', 'Ceará' => 'CE', 'Distrito Federal' => 'DF', 
                    'Espírito Santo' => 'ES', 'Goiás' => 'GO', 'Maranhão' => 'MA', 
                    'Mato Grosso' => 'MT', 'Mato Grosso do Sul' => 'MS', 'Minas Gerais' => 'MG', 
                    'Pará' => 'PA', 'Paraíba' => 'PB', 'Paraná' => 'PR', 'Pernambuco' => 'PE', 
                    'Piauí' => 'PI', 'Rio de Janeiro' => 'RJ', 'Rio Grande do Norte' => 'RN', 
                    'Rio Grande do Sul' => 'RS', 'Rondônia' => 'RO', 'Roraima' => 'RR', 
                    'Santa Catarina' => 'SC', 'São Paulo' => 'SP', 'Sergipe' => 'SE', 'Tocantins' => 'TO'
                ];
                $uf = $state ? ($statesMap[$state] ?? null) : null;

                if ($roadClean && $city && $uf) {
                    \Illuminate\Support\Facades\Log::info("GEOCODE: Trying ViaCEP Strategy 2 for {$uf}/{$city}/{$roadClean}");
                    $viacepResponse = \Illuminate\Support\Facades\Http::withoutVerifying()
                        ->timeout(5)
                        ->get("https://viacep.com.br/ws/{$uf}/" . urlencode($city) . "/" . urlencode($roadClean) . "/json/");

                    if ($viacepResponse->successful() && !empty($viacepResponse->json()) && is_array($viacepResponse->json())) {
                        $found = preg_replace('/\D/', '', $viacepResponse->json()[0]['cep']);
                        if (strlen($found) === 8) {
                            \Illuminate\Support\Facades\Log::info("GEOCODE: Found CEP in ViaCEP Strategy 2 [{$found}]");
                            return $found;
                        }
                    }
                }

                // Estratégia 3: Fallback por bairro ou "Centro"
                if ($city && $uf) {
                    $suburb = $item['address']['suburb'] ?? 'Centro';
                    \Illuminate\Support\Facades\Log::info("GEOCODE: Trying ViaCEP Strategy 3 (Suburb/Centro) for {$uf}/{$city}/{$suburb}");
                    $viacepCityResponse = \Illuminate\Support\Facades\Http::withoutVerifying()
                        ->timeout(5)
                        ->get("https://viacep.com.br/ws/{$uf}/" . urlencode($city) . "/" . urlencode($suburb) . "/json/");
                    
                    if ($viacepCityResponse->successful() && !empty($viacepCityResponse->json()) && is_array($viacepCityResponse->json())) {
                        $found = preg_replace('/\D/', '', $viacepCityResponse->json()[0]['cep']);
                        if (strlen($found) === 8) {
                             \Illuminate\Support\Facades\Log::info("GEOCODE: Found CEP in ViaCEP Strategy 3 [{$found}]");
                             return $found;
                        }
                    }
                    
                    // Estratégia 4: Fallback Final (Cidade como Rua)
                    \Illuminate\Support\Facades\Log::info("GEOCODE: Trying ViaCEP Strategy 4 (City as Road) for {$uf}/{$city}/{$city}");
                    $viacepFinalResponse = \Illuminate\Support\Facades\Http::withoutVerifying()
                        ->timeout(5)
                        ->get("https://viacep.com.br/ws/{$uf}/" . urlencode($city) . "/" . urlencode($city) . "/json/");
                    
                    if ($viacepFinalResponse->successful() && !empty($viacepFinalResponse->json()) && is_array($viacepFinalResponse->json())) {
                        $found = preg_replace('/\D/', '', $viacepFinalResponse->json()[0]['cep']);
                        if (strlen($found) === 8) {
                             \Illuminate\Support\Facades\Log::info("GEOCODE: Found CEP in ViaCEP Strategy 4 [{$found}]");
                             return $found;
                        }
                    }
                }
            }
        } else {
             \Illuminate\Support\Facades\Log::info("GEOCODE: Nominatim returned empty for this query.");
        }
        return null;
    }

    private function normalizeSearchQuery($q)
    {
        $q = trim($q);
        if (!$q) return '';
        
        // Remove prefixos comuns de logradouro em PT-BR
        $prefixes = ['avenida', 'av.', 'av ', 'rua', 'r.', 'r ', 'travessa', 'trv ', 'praça', 'praca', 'pça', 'pza', 'alameda', 'alm.'];
        
        $lowercase = mb_strtolower($q);
        foreach ($prefixes as $prefix) {
            if (mb_strpos($lowercase, $prefix) === 0) {
                $q = trim(mb_substr($q, mb_strlen($prefix)));
                break;
            }
        }
        return $q;
    }

    public function suggestions(Request $request)
    {
        $q = $request->query('q');
        \Illuminate\Support\Facades\Log::info("SUGGESTIONS: query [{$q}]");
        if (strlen($q) < 4) return response()->json([]);

        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Referer' => 'https://google.com'
        ];

        try {
            \Illuminate\Support\Facades\Log::info("SUGGESTIONS: Recebendo query [{$q}]");

            $stateMap = [
                'ACRE' => 'AC', 'ALAGOAS' => 'AL', 'AMAPA' => 'AP', 'AMAZONAS' => 'AM', 'BAHIA' => 'BA',
                'CEARA' => 'CE', 'DISTRITO FEDERAL' => 'DF', 'ESPIRITO SANTO' => 'ES', 'GOIAS' => 'GO',
                'MARANHAO' => 'MA', 'MATO GROSSO' => 'MT', 'MATO GROSSO DO SUL' => 'MS', 'MINAS GERAIS' => 'MG',
                'PARA' => 'PA', 'PARAIBA' => 'PB', 'PARANA' => 'PR', 'PERNAMBUCO' => 'PE', 'PIAUI' => 'PI',
                'RIO DE JANEIRO' => 'RJ', 'RIO GRANDE DO NORTE' => 'RN', 'RIO GRANDE DO SUL' => 'RS',
                'RONDONIA' => 'RO', 'RORAIMA' => 'RR', 'SANTA CATARINA' => 'SC', 'SAO PAULO' => 'SP',
                'SERGIPE' => 'SE', 'TOCANTINS' => 'TO'
            ];

            if (preg_match('/^(.+),\s*(.+)\s*-\s*(.+)$/i', $q, $matches)) {
                $street = trim($matches[1]);
                $city = trim($matches[2]);
                $ufRaw = strtoupper(trim($matches[3]));
                
                // Normalização robusta de UF
                $uf = $ufRaw;
                if (strlen($ufRaw) > 2) {
                    $normState = strtr(utf8_decode($ufRaw), utf8_decode('ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýýþÿ'), 'AAAAAAACEEEEIIIIDNOOOOOOUUUUYBsaaaaaaaceeeeiiiionoooooouuuuyyby');
                    $uf = $stateMap[strtoupper($normState)] ?? $ufRaw;
                }

                $citiesToTry = [
                    $city,
                    strtr(utf8_decode($city), utf8_decode('ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýýþÿ'), 'AAAAAAACEEEEIIIIDNOOOOOOUUUUYBsaaaaaaaceeeeiiiionoooooouuuuyyby')
                ];

                foreach($citiesToTry as $cityTry) {
                    $viacepUrl = "https://viacep.com.br/ws/{$uf}/" . rawurlencode($cityTry) . "/" . rawurlencode($street) . "/json/";
                    \Illuminate\Support\Facades\Log::info("SUGGESTIONS: Consultando ViaCEP: [{$viacepUrl}]");
                    
                    $vResponse = \Illuminate\Support\Facades\Http::withoutVerifying()->timeout(12)->get($viacepUrl);
                    
                    \Illuminate\Support\Facades\Log::info("SUGGESTIONS: Resposta ViaCEP [{$vResponse->status()}]");

                    if ($vResponse->successful()) {
                        $vData = $vResponse->json();
                        if (!empty($vData) && is_array($vData) && !isset($vData['erro'])) {
                            \Illuminate\Support\Facades\Log::info("SUGGESTIONS: ViaCEP encontrou " . count($vData) . " resultados.");
                            $vResults = [];
                            foreach ($vData as $vItem) {
                                $complemento = !empty($vItem['complemento']) ? " ({$vItem['complemento']})" : "";
                                $vResults[] = [
                                    'label' => "{$vItem['logradouro']}{$complemento}, {$vItem['bairro']} - {$vItem['localidade']}/{$vItem['uf']}",
                                    'cep' => preg_replace('/\D/', '', $vItem['cep']),
                                    'details' => [
                                        'road' => $vItem['logradouro'],
                                        'neighborhood' => $vItem['bairro'],
                                        'city' => $vItem['localidade'],
                                        'state' => $vItem['uf'],
                                        'formatted_cep' => $vItem['cep'],
                                        'complement' => $vItem['complemento']
                                    ]
                                ];
                            }
                            return response()->json($vResults);
                        }
                    }
                }
                \Illuminate\Support\Facades\Log::info("SUGGESTIONS: ViaCEP não retornou dados úteis para {$street} em {$city}. Indo para fallback Nominatim.");
            }

            // FALLBACK OU BUSCA TEXTUAL: NOMINATIM
            $qLower = mb_strtolower($q);
            $params = [
                'format' => 'json',
                'addressdetails' => 1,
                'limit' => 5,
                'countrycodes' => 'br'
            ];

            if (str_contains($qLower, 'jarinu')) {
                $qSearch = trim(str_ireplace('jarinu', '', $q));
                $params['q'] = "{$qSearch}";
                $params['city'] = 'Jarinu';
                $params['state'] = 'São Paulo';
            } else {
                $params['q'] = "{$q}, Brazil";
            }

            $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->timeout(5)
                ->withHeaders($headers)
                ->get("https://nominatim.openstreetmap.org/search", $params);

            if (!$response->successful()) {
                return response()->json([]);
            }

            $results = [];
            foreach ($response->json() as $item) {
                $addr = $item['address'];
                $postcode = $addr['postcode'] ?? null;
                $label = $item['display_name'];
                
                $road = $addr['road'] ?? $addr['pedestrian'] ?? $addr['suburb'] ?? 'Logradouro não identificado';
                $neighborhood = $addr['neighbourhood'] ?? $addr['suburb'] ?? $addr['hamlet'] ?? 'Bairro não identificado';
                $city = $addr['city'] ?? $addr['town'] ?? $addr['village'] ?? 'Cidade não identificada';
                $state = $addr['state'] ?? '';

                $results[] = [
                    'label' => $label,
                    'cep' => $postcode ? preg_replace('/\D/', '', $postcode) : $label, 
                    'details' => [
                        'road' => $road,
                        'neighborhood' => $neighborhood,
                        'city' => $city,
                        'state' => $state,
                        'formatted_cep' => $postcode ? preg_replace('/^(\d{5})(\d{3})$/', '$1-$2', preg_replace('/\D/', '', $postcode)) : 'CEP sob consulta'
                    ]
                ];
            }
            return response()->json($results);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("SUGGESTIONS: Exception " . $e->getMessage());
            return response()->json([]);
        }
    }

    public function show($cep)
    {
        $report = $this->neighborhoodService->getCachedReport($cep);

        if (!$report) {
            return redirect()->route('home')->withErrors(['cep' => 'CEP não encontrado ou erro nas APIs de terceiros.']);
        }

        // Lazy Update para novos POIs (V1 -> V2) e Scores
        $this->ensureDataIsFresh($report);

        // Ajuste de Proxy/Cache de Imagem da Wikipedia para Produção
        $wiki = $report->wiki_json ?? [];
        if (!empty($wiki['image']) && str_contains($wiki['image'], 'upload.wikimedia.org')) {
            // Normalizar qualquer thumbnail (ex: /3456px- ou /320px-) para /640px-
            // Usamos '#' como delimitador para não precisar escapar a barra da URL
            if (str_contains($wiki['image'], '/thumb/')) {
                $wiki['image'] = preg_replace('#/\d+px-#', '/640px-', $wiki['image']);
            } elseif (str_contains($wiki['image'], '/commons/')) {
                // Se for original, converter para miniatura de 640px
                $filename = basename($wiki['image']);
                $wiki['image'] = str_replace('/commons/', '/commons/thumb/', $wiki['image']) . "/640px-" . $filename;
            }
        }

        return view('report.show', compact('report', 'wiki'));
    }

    private function ensureDataIsFresh(\App\Models\LocationReport $report)
    {
        $needsUpdate = false;
        if ($report->data_version < 3) {
            \Illuminate\Support\Facades\Log::info("ReportController: Reidratando POIs (V2 -> V3) para o CEP {$report->cep}");
            try {
                $poiAgent = app(\App\Services\Agents\POIAgent::class);
                $adaptiveData = $poiAgent->fetchPOIsAdaptive($report->lat, $report->lng);
                $newPois = $adaptiveData['pois'];
                
                if (!empty($newPois)) {
                    $report->pois_json = $newPois;
                    $report->search_radius = $adaptiveData['radius'];
                    $report->data_version = 3;
                    
                    // Recalcular scores com os novos dados
                    $compareAgent = app(\App\Services\Agents\CompareAgent::class);
                    $metrics = $compareAgent->getRegionMetrics($newPois);
                    $report->infra_score = $metrics['infra'];
                    $report->mobility_score = $metrics['mobility'];
                    $report->leisure_score = $metrics['leisure'];
                    $report->general_score = $metrics['total_score'];
                    
                    $needsUpdate = true;
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("ReportController: Erro no Lazy Update para {$report->cep}: " . $e->getMessage());
            }
        }
        if ($needsUpdate) $report->save();
    }

    public function compare($cep1, $cep2)
    {
        $report1 = $this->neighborhoodService->getCachedReport($cep1);
        $report2 = $this->neighborhoodService->getCachedReport($cep2);

        if (!$report1 || !$report2) {
            return redirect()->route('home')->withErrors(['cep' => 'Um ou ambos os CEPs não foram localizados para comparação.']);
        }

        return view('report.compare', compact('report1', 'report2'));
    }
    public function reprocessNarrative($cep)
    {
        $report = \App\Models\LocationReport::where('cep', $cep)->firstOrFail();
        
        // Resetar para 'processing_text' para que o polling do frontend funcione
        $report->status = 'processing_text';
        $report->history_extract = null;
        $report->save();

        // Disparar o Job de Narrativa
        $wikiContext = [
            'bairro' => $report->bairro,
            'city' => $report->cidade,
            'state' => $report->uf
        ];
        
        // Usamos o LLMAgent para manter o padrão
        $llmAgent = app(\App\Services\Agents\LLMAgent::class);
        $llmAgent->dispatchTextGeneration($report->cep, $report->id, $wikiContext);

        return response()->json(['success' => true]);
    }
}
