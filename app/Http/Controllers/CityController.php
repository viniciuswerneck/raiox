<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\LocationReport;
use App\Services\CityDashboard\CityDashboardService;
use Illuminate\Http\Request;

class CityController extends Controller
{
    protected $cityService;

    public function __construct(CityDashboardService $cityService)
    {
        $this->cityService = $cityService;
    }

    public function show($slug)
    {
        $city = City::where('slug', $slug)->firstOrFail();

        // Gatilho de auto-reprocessamento: Se o cache estiver velho (>24h) ou faltar info essencial
        $hasMissingInfo = !$city->history_extract 
            || !$city->image_url 
            || empty($city->stats_cache) 
            || !isset($city->stats_cache['radar']) 
            || !isset($city->stats_cache['top_conveniencias']);

        if (!$city->last_calculated_at || $city->last_calculated_at->diffInHours(now()) > 24 || $hasMissingInfo) {
            $this->cityService->updateCityData($city);
            $city->refresh(); // Garante que a view receba os dados novos
        }

        $recentReports = LocationReport::where('cidade', $city->name)
            ->where('uf', $city->uf)
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get();

        return view('city.show', compact('city', 'recentReports'));
    }

    public function reprocess($slug)
    {
        $city = City::where('slug', $slug)->firstOrFail();

        // Limpa os dados de cache para forçar a regeneração
        $city->update([
            'history_extract' => null,
            'image_url' => null,
            'stats_cache' => null,
            'last_calculated_at' => null,
            'wiki_json' => null
        ]);

        return redirect()->route('city.show', $slug);
    }
}
