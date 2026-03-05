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
        $locationType = $request->query('type', 'bairro'); // bairro or cidade

        $query = LocationReport::query();

        // Agrupamento básico para evitar duplicidade de CEPs na mesma região
        if ($locationType === 'cidade') {
            $query->select(
                'cidade', 
                'uf',
                DB::raw('AVG(air_quality_index) as avg_aqi'),
                DB::raw('AVG(sanitation_rate) as avg_sanitation'),
                DB::raw('COUNT(*) as search_count'),
                DB::raw('MAX(created_at) as last_updated'),
                // Agregação de scores categóricos
                DB::raw("SUM(CASE WHEN walkability_score = 'A' THEN 100 WHEN walkability_score = 'B' THEN 70 ELSE 40 END) / COUNT(*) as score_walk"),
                DB::raw("SUM(CASE WHEN safety_level LIKE '%ALTO%' OR safety_level LIKE '%ALTA%' THEN 100 WHEN safety_level LIKE '%MODERADO%' THEN 70 ELSE 40 END) / COUNT(*) as score_safety")
            )->groupBy('cidade', 'uf');
        } else {
            $query->select(
                'cidade',
                'bairro',
                'uf',
                DB::raw('AVG(air_quality_index) as avg_aqi'),
                DB::raw('AVG(sanitation_rate) as avg_sanitation'),
                DB::raw('COUNT(*) as search_count'),
                DB::raw('MAX(created_at) as last_updated'),
                DB::raw("SUM(CASE WHEN walkability_score = 'A' THEN 100 WHEN walkability_score = 'B' THEN 70 ELSE 40 END) / COUNT(*) as score_walk"),
                DB::raw("SUM(CASE WHEN safety_level LIKE '%ALTO%' OR safety_level LIKE '%ALTA%' THEN 100 WHEN safety_level LIKE '%MODERADO%' THEN 70 ELSE 40 END) / COUNT(*) as score_safety")
            )->whereNotNull('bairro')
             ->where('bairro', '!=', '')
             ->groupBy('cidade', 'bairro', 'uf');
        }

        $results = $query->get()->map(function($item) {
            // Cálculo do Score Final (Ponderado)
            // AQI invertido: menor é melhor. 20 é bom (100), 100 é péssimo (0).
            $aqiScore = max(0, 100 - ($item->avg_aqi)); 
            
            $item->final_score = round(
                ($item->score_safety * 0.4) + 
                ($item->score_walk * 0.3) + 
                ($aqiScore * 0.2) + 
                (($item->avg_sanitation ?? 50) * 0.1)
            );
            
            return $item;
        });

        // Ordenação por categoria
        $results = $results->sortByDesc(function($item) use ($category) {
            return match($category) {
                'safety' => $item->score_safety,
                'walk' => $item->score_walk,
                'air' => 100 - $item->avg_aqi,
                default => $item->final_score
            };
        })->values();

        return view('report.ranking', compact('results', 'category', 'locationType'));
    }
}
