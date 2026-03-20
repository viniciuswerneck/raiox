<?php

namespace App\Http\Controllers;

use App\Models\LocationReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RankingController extends Controller
{
    public function index(Request $request)
    {
        $category = $request->query('category', 'all');
        $locationType = $request->query('type', 'bairro');
        $page = $request->query('page', 1);

        $cacheKey = "ranking_{$locationType}_{$category}_p{$page}";

        $results = \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addHours(1), function () use ($category, $locationType) {
            $query = LocationReport::query();

            // Cálculos base em SQL para permitir ordenação e paginação nativa
            $selects = [
                'uf',
                'cidade',
                DB::raw('AVG(air_quality_index) as avg_aqi'),
                DB::raw('AVG(sanitation_rate) as avg_sanitation'),
                DB::raw('COUNT(*) as search_count'),
                DB::raw('MAX(created_at) as last_updated'),
                DB::raw("AVG(CASE WHEN walkability_score = 'A' THEN 100 WHEN walkability_score = 'B' THEN 70 ELSE 40 END) as score_walk"),
                DB::raw("AVG(CASE WHEN safety_level LIKE '%ALTO%' OR safety_level LIKE '%ALTA%' THEN 100 WHEN safety_level LIKE '%MODERADO%' THEN 70 ELSE 40 END) as score_safety"),
            ];

            if ($locationType === 'bairro') {
                $selects[] = 'bairro';
                $query->whereNotNull('bairro')->where('bairro', '!=', '');
                $query->groupBy('uf', 'cidade', 'bairro');
            } else {
                $query->groupBy('uf', 'cidade');
            }

            // Cálculo do Final Score em SQL
            // Formula: (Safety * 0.4) + (Walk * 0.3) + ((100-AQI) * 0.2) + (Sanitation * 0.1)
            $sqlFinalScore = "(
                (AVG(CASE WHEN safety_level LIKE '%ALTO%' OR safety_level LIKE '%ALTA%' THEN 100 WHEN safety_level LIKE '%MODERADO%' THEN 70 ELSE 40 END) * 0.4) + 
                (AVG(CASE WHEN walkability_score = 'A' THEN 100 WHEN walkability_score = 'B' THEN 70 ELSE 40 END) * 0.3) + 
                ((100 - AVG(air_quality_index)) * 0.2) + 
                (COALESCE(AVG(sanitation_rate), 50) * 0.1)
            )";

            $query->select($selects)->addSelect(DB::raw("HEX($sqlFinalScore) as final_score_hex")); // Usando HEX/Alias para evitar conflitos de AVG em alguns drivers

            // Atribuindo o valor limpo para o order by
            $orderBy = match ($category) {
                'safety' => DB::raw("AVG(CASE WHEN safety_level LIKE '%ALTO%' OR safety_level LIKE '%ALTA%' THEN 100 WHEN safety_level LIKE '%MODERADO%' THEN 70 ELSE 40 END)"),
                'walk' => DB::raw("AVG(CASE WHEN walkability_score = 'A' THEN 100 WHEN walkability_score = 'B' THEN 70 ELSE 40 END)"),
                'air' => DB::raw('(100 - AVG(air_quality_index))'),
                default => DB::raw($sqlFinalScore)
            };

            return $query->orderByDesc($orderBy)->paginate(15)->withQueryString();
        });

        // Mapeamento pós-cache para garantir que os scores sejam arredondados e limpos
        $results->getCollection()->transform(function ($item) {
            $aqiScore = max(0, 100 - ($item->avg_aqi));
            $item->final_score = round(
                ($item->score_safety * 0.4) +
                ($item->score_walk * 0.3) +
                ($aqiScore * 0.2) +
                ((($item->avg_sanitation ?? 50)) * 0.1)
            );

            return $item;
        });

        return view('report.ranking', compact('results', 'category', 'locationType'));
    }

    public function cityRanking(string $slug, Request $request)
    {
        $cityModel = \App\Models\City::where('slug', $slug)->firstOrFail();
        $category = $request->query('category', 'all');

        $query = LocationReport::where('cidade', $cityModel->name)
            ->where('uf', $cityModel->uf)
            ->whereNotNull('bairro')
            ->where('bairro', '!=', '');

        // Cálculos base em SQL
        $selects = [
            'uf', 'cidade', 'bairro', 'cep',
            DB::raw('AVG(air_quality_index) as avg_aqi'),
            DB::raw('AVG(sanitation_rate) as avg_sanitation'),
            DB::raw("AVG(CASE WHEN walkability_score = 'A' THEN 100 WHEN walkability_score = 'B' THEN 70 ELSE 40 END) as score_walk"),
            DB::raw("AVG(CASE WHEN safety_level LIKE '%ALTO%' OR safety_level LIKE '%ALTA%' THEN 100 WHEN safety_level LIKE '%MODERADO%' THEN 70 ELSE 40 END) as score_safety"),
        ];

        $sqlFinalScore = "(
            (AVG(CASE WHEN safety_level LIKE '%ALTO%' OR safety_level LIKE '%ALTA%' THEN 100 WHEN safety_level LIKE '%MODERADO%' THEN 70 ELSE 40 END) * 0.4) + 
            (AVG(CASE WHEN walkability_score = 'A' THEN 100 WHEN walkability_score = 'B' THEN 70 ELSE 40 END) * 0.3) + 
            ((100 - AVG(air_quality_index)) * 0.2) + 
            (COALESCE(AVG(sanitation_rate), 50) * 0.1)
        )";

        $results = $query->select($selects)
            ->addSelect(DB::raw("$sqlFinalScore as final_score_calc"))
            ->groupBy('uf', 'cidade', 'bairro', 'cep')
            ->orderByDesc(match ($category) {
                'safety' => DB::raw('score_safety'),
                'walk' => DB::raw('score_walk'),
                default => DB::raw('final_score_calc')
            })
            ->limit(20)
            ->get();

        $results->transform(function ($item) {
            $item->final_score = round($item->final_score_calc);

            return $item;
        });

        return view('report.city-ranking', compact('results', 'cityModel', 'category'));
    }
}
