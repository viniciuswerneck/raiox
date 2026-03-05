<?php

namespace App\Services\Agents;

use App\Models\LocationReport;
use Carbon\Carbon;

class CacheAgent
{
    /**
     * Recupera o cache via Banco (ou Redis) e lida com TTL
     */
    public function getCachedReport(string $cep): ?LocationReport
    {
        $cepClean = preg_replace('/\D/', '', $cep);
        $report = LocationReport::where('cep', $cepClean)->first();

        // Dados estruturais: 6 Meses
        if ($report && $report->updated_at && $report->updated_at->gt(Carbon::now()->subMonths(6))) {
            return $report;
        }

        return null;
    }

    /**
     * Atualiza dados silenciosamente para não onerar o usuário em reload
     */
    public function updateVolatileData(LocationReport $report, array $clima, $aqi): void
    {
        $report->update([
            'climate_json' => $clima,
            'air_quality_index' => $aqi
        ]);
    }

    /**
     * Salva ou atualiza a estrutura inicial
     */
    public function upsertBasicData(string $cep, array $data): LocationReport
    {
        $report = LocationReport::where('cep', $cep)->first();
        if ($report) {
            $report->update($data);
            return $report;
        }

        return LocationReport::create($data);
    }
}
