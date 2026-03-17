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

        // Gatilho de auto-reprocessamento: Se o cache estiver velho (>24h), faltar info essencial ou houver novos reports
        $totalReports = LocationReport::where('cidade', $city->name)->where('uf', $city->uf)->where('status', 'completed')->count();
        $cachedReports = $city->stats_cache['total_mapped_ceps'] ?? 0;

        $isFirstLoad = !$city->history_extract || empty($city->stats_cache);
        $neighborhoodCount = count($city->stats_cache['neighborhood_list'] ?? []);
        $needsUpdate = !$city->last_calculated_at 
            || $city->last_calculated_at->diffInHours(now()) > 24 
            || $totalReports > $cachedReports
            || ($neighborhoodCount < 30 && $city->bbox_json && $city->last_calculated_at->diffInMinutes(now()) > 10);

        if ($isFirstLoad || $needsUpdate) {
            if ($isFirstLoad) {
                // Para o primeiro carregamento, tentamos fazer síncrono mas com tempo expandido
                set_time_limit(120);
                $this->cityService->updateCityData($city);
                $city->refresh();
            } else {
                // Se já tem dados mas precisa atualizar, fazemos em background para não travar o usuário
                // Usamos um lock curto de 5 min para não encher a fila com Jobs repetidos da mesma cidade
                $lockKey = "update_city_lock_" . $city->id;
                if (!\Illuminate\Support\Facades\Cache::has($lockKey)) {
                    \Illuminate\Support\Facades\Cache::put($lockKey, true, 300);
                    \App\Jobs\UpdateCityDataJob::dispatch($city);
                }
            }
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
