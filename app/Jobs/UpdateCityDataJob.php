<?php

namespace App\Jobs;

use App\Models\City;
use App\Services\CityDashboard\CityDashboardService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateCityDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $city;
    public $timeout = 300; // 5 minutos

    /**
     * Create a new job instance.
     */
    public function __construct(City $city)
    {
        $this->city = $city;
    }

    /**
     * Execute the job.
     */
    public function handle(CityDashboardService $service): void
    {
        Log::info("Iniciando UpdateCityDataJob para: {$this->city->name} - {$this->city->uf}");
        
        try {
            $service->updateCityData($this->city);
            Log::info("UpdateCityDataJob concluído com sucesso para: {$this->city->name}");
        } catch (\Exception $e) {
            Log::error("Erro no UpdateCityDataJob para {$this->city->name}: " . $e->getMessage());
            throw $e;
        } finally {
            \Illuminate\Support\Facades\Cache::forget("update_city_lock_" . $this->city->id);
        }
    }
}
