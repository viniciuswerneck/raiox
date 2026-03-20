<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\LocationReport;
use App\Services\CityDashboard\CityDashboardService;

class CityController extends Controller
{
    protected $cityService;

    protected $integrity;

    public function __construct(CityDashboardService $cityService, \App\Services\Agents\IntegrityAgent $integrity)
    {
        $this->cityService = $cityService;
        $this->integrity = $integrity;
    }

    public function show($slug)
    {
        $city = City::where('slug', $slug)->firstOrFail();

        $isFirstLoad = ! $city->history_extract || empty($city->stats_cache);

        if ($isFirstLoad) {
            // Primeiro carregamento: Auditoria profunda síncrona para não mostrar página vazia
            set_time_limit(180);
            $this->cityService->updateCityData($city);
            $city->refresh();
        } else {
            // Carregamentos subsequentes: Auditoria em background via IntegrityAgent
            $this->integrity->autoRepairByCity($city);
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
            'wiki_json' => null,
        ]);

        return redirect()->route('city.show', $slug);
    }
}
