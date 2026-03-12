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

        // Se nunca foi calculado ou faz mais de 24h, atualiza
        if (!$city->last_calculated_at || $city->last_calculated_at->diffInHours(now()) > 24) {
            $this->cityService->updateCityData($city);
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
