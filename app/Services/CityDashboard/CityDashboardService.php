<?php

namespace App\Services\CityDashboard;

use App\Models\City;
use App\Models\LocationReport;
use App\Services\GeminiService;
use App\Services\GroqService;
use App\Services\OpenRouterService;
use App\Services\TextReviserService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CityDashboardService
{
    protected $gemini;
    protected $groq;
    protected $openRouter;
    protected $reviser;

    public function __construct(
        GeminiService $gemini,
        GroqService $groq,
        OpenRouterService $openRouter,
        TextReviserService $reviser
    ) {
        $this->gemini = $gemini;
        $this->groq = $groq;
        $this->openRouter = $openRouter;
        $this->reviser = $reviser;
    }

    /**
     * Gera ou atualiza os dados da cidade.
     */
    public function updateCityData(City $city)
    {
        // 1. Agregar estatísticas dos CEPs buscados
        $this->aggregateStats($city);

        // 2. Tentar buscar foto se não tiver
        if (empty($city->image_url)) {
            $city->image_url = $this->fetchCityImage($city);
        }

        // 3. Gerar história se não tiver
        if (empty($city->history_extract)) {
            $this->generateHistory($city);
        }

        $city->save();
    }

    private function aggregateStats(City $city)
    {
        $reports = LocationReport::where('cidade', $city->name)
            ->where('uf', $city->uf)
            ->where('status', 'completed')
            ->get();

        if ($reports->isEmpty()) return;

        $avgIncome = $reports->avg('average_income');
        
        $totalPois = 0;
        foreach ($reports as $report) {
            if (is_array($report->pois_json)) {
                $totalPois += count($report->pois_json);
            }
        }
        
        // Contagem de categorias para o "Mix de Uso"
        $categories = $reports->pluck('territorial_classification')->countBy();
        $predominant = $categories->sortDesc()->keys()->first();

        // Ranking de Bairros (Nome e Score Médio)
        $neighborhoodRanking = $reports->groupBy('bairro')
            ->map(function ($items) {
                return [
                    'name' => $items->first()->bairro ?: 'Centro',
                    'avg_score' => round($items->avg('general_score'), 1)
                ];
            })
            ->sortByDesc('avg_score')
            ->values()
            ->toArray();

        // Média de pontuação final municipal
        $avgScore = $reports->avg('general_score');

        $city->stats_cache = [
            'avg_income' => round($avgIncome, 2),
            'total_mapped_ceps' => $reports->count(),
            'neighborhood_count' => count($neighborhoodRanking),
            'neighborhood_list' => $neighborhoodRanking, // Agora contém o ranking estruturado
            'total_pois' => $totalPois,
            'avg_score' => round($avgScore, 1),
            'predominant_class' => $predominant,
            'mix_usage' => $categories->toArray()
        ];
        
        $city->last_calculated_at = now();
    }

    private function fetchCityImage(City $city): string
    {
        // 1. Tentar pegar a imagem oficial da Wikipedia
        $wikiImage = $this->fetchWikipediaImage($city);
        if ($wikiImage) return $wikiImage;

        // Fallback: Imagens urbanas premium genéricas
        $genericUrbanImages = [
            'https://images.unsplash.com/photo-1449824913935-59a10b8d2000?auto=format&fit=crop&w=1200&q=80',
            'https://images.unsplash.com/photo-1477959858617-67f85cf4f1df?auto=format&fit=crop&w=1200&q=80',
            'https://images.unsplash.com/photo-1480714378408-67cf0d13bc1b?auto=format&fit=crop&w=1200&q=80'
        ];
        
        return $genericUrbanImages[array_rand($genericUrbanImages)];
    }

    private function fetchWikipediaImage(City $city): ?string
    {
        try {
            // Tenta nomes variados: "Nome, UF", "Nome SP", "Nome"
            $queries = [
                "{$city->name}, {$city->uf}",
                "{$city->name} {$city->uf}",
                $city->name
            ];

            foreach ($queries as $query) {
                $response = Http::withoutVerifying()->withHeaders([
                    'User-Agent' => 'RaioX Territorial/1.0 (https://raiox.com.br; contato@raiox.com.br)'
                ])->get("https://pt.wikipedia.org/w/api.php", [
                    'action' => 'query',
                    'format' => 'json',
                    'prop' => 'pageimages',
                    'pithumbsize' => 1200,
                    'titles' => $query,
                    'redirects' => 1
                ]);

                if ($response->successful()) {
                    $pages = $response->json()['query']['pages'] ?? [];
                    $page = reset($pages);
                    
                    if (isset($page['thumbnail']['source'])) {
                        $imgUrl = $page['thumbnail']['source'];
                        // Evita brasões e bandeiras se possível (geralmente vêm como primeira imagem)
                        if (!str_contains(strtolower($imgUrl), 'bandeira') && !str_contains(strtolower($imgUrl), 'brasao')) {
                            return $imgUrl;
                        }
                        // Se for brasão/bandeira, guarda mas continua tentando outras queries/estatégias
                        $fallbackImg = $imgUrl;
                    }
                }
            }
            
            return $fallbackImg ?? null;
        } catch (\Exception $e) {
            Log::warning("Wiki Image Error: " . $e->getMessage());
        }
        return null;
    }

    private function generateHistory(City $city)
    {
        // Busca na Wikipedia com SSL desativado
        $wikiText = $this->fetchWikipediaData($city);
        
        $context = [
            'categoria' => 'Resumo da Cidade',
            'renda' => $city->average_income ?? 0,
            'safety_level' => $city->safety_level ?? 'MODERADO'
        ];

        // Tenta Gemini -> Groq -> OpenRouter
        $summary = $this->gemini->generateNeighborhoodSummary($wikiText, $city->name, $context)
            ?? $this->groq->generateNeighborhoodSummary($wikiText, $city->name, $context)
            ?? $this->openRouter->generateNeighborhoodSummary($wikiText, $city->name, $context);

        if ($summary && isset($summary['historia'])) {
            // Aplica o revisor para garantir 3 parágrafos e humanização
            $revised = $this->reviser->reviseHistoria($summary);
            $city->history_extract = $revised['historia'];
            $city->wiki_json = ['raw' => substr($wikiText, 0, 5000)];
        }
    }

    private function fetchWikipediaData(City $city): string
    {
        try {
            $queries = ["{$city->name}, {$city->uf}", "{$city->name} {$city->uf}", $city->name];
            
            foreach ($queries as $query) {
                $response = Http::withoutVerifying()->withHeaders([
                    'User-Agent' => 'RaioX Territorial/1.0 (https://raiox.com.br; contato@raiox.com.br)'
                ])->get("https://pt.wikipedia.org/w/api.php", [
                    'action' => 'query',
                    'format' => 'json',
                    'prop' => 'extracts',
                    'exintro' => 1,
                    'explaintext' => 1,
                    'titles' => $query,
                    'redirects' => 1
                ]);

                if ($response->successful()) {
                    $pages = $response->json()['query']['pages'] ?? [];
                    $page = reset($pages);
                    if (isset($page['extract']) && strlen($page['extract']) > 200) {
                        return $page['extract'];
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning("Wiki City Text Error: " . $e->getMessage());
        }
        return '';
    }
}
