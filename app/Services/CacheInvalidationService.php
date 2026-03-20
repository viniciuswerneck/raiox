<?php

namespace App\Services;

use App\Models\LocationReport;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheInvalidationService
{
    private array $ttlConfig = [
        'structural' => 180, // 6 meses em dias
        'demographic' => 30, // 30 dias
        'volatile' => 1, // 1 dia
        'weather' => 1, // 1 dia
        'pois' => 7, // 7 dias
        'narrative' => 90, // 90 dias
    ];

    public function invalidateReport(string $cep): bool
    {
        $cepClean = preg_replace('/\D/', '', $cep);

        try {
            $keysToInvalidate = [
                "report:{$cepClean}",
                "report:full:{$cepClean}",
                "report:geo:{$cepClean}",
                "report:stats:{$cepClean}",
            ];

            foreach ($keysToInvalidate as $key) {
                Cache::forget($key);
            }

            Log::info("Cache invalidado para CEP: {$cepClean}");

            return true;
        } catch (\Exception $e) {
            Log::error("Erro ao invalidar cache para CEP {$cepClean}: ".$e->getMessage());

            return false;
        }
    }

    public function invalidateCity(string $citySlug): bool
    {
        try {
            $keysToInvalidate = [
                "city:{$citySlug}",
                "city:dashboard:{$citySlug}",
                "city:stats:{$citySlug}",
                "city:reports:{$citySlug}",
            ];

            foreach ($keysToInvalidate as $key) {
                Cache::forget($key);
            }

            Log::info("Cache invalidado para cidade: {$citySlug}");

            return true;
        } catch (\Exception $e) {
            Log::error("Erro ao invalidar cache para cidade {$citySlug}: ".$e->getMessage());

            return false;
        }
    }

    public function invalidateIbgeData(string $ibgeCode): bool
    {
        try {
            Cache::forget("ibge:data:{$ibgeCode}");
            Cache::forget("ibge:demographics:{$ibgeCode}");
            Cache::forget("ibge:municipality:{$ibgeCode}");

            $reports = LocationReport::where('codigo_ibge', $ibgeCode)->get();
            foreach ($reports as $report) {
                $this->invalidateReport($report->cep);
            }

            Log::info("Cache IBGE invalidado para código: {$ibgeCode} ({$reports->count()} relatórios afetados)");

            return true;
        } catch (\Exception $e) {
            Log::error("Erro ao invalidar cache IBGE {$ibgeCode}: ".$e->getMessage());

            return false;
        }
    }

    public function invalidateByIbgeUpdate(string $ibgeCode, string $dataType = 'all'): bool
    {
        if ($dataType === 'demographic' || $dataType === 'all') {
            Cache::forget("ibge:demographics:{$ibgeCode}");

            $reports = LocationReport::where('codigo_ibge', $ibgeCode)
                ->where('updated_at', '<', now()->subDays(7))
                ->get();

            foreach ($reports as $report) {
                $report->update(['populacao' => null, 'idhm' => null, 'raw_ibge_data' => null]);
            }

            Log::info("Dados demográficos IBGE invalidados para código: {$ibgeCode}");
        }

        return true;
    }

    public function invalidateAll(): bool
    {
        try {
            Cache::flush();
            Log::warning('Todo o cache foi invalidado');

            return true;
        } catch (\Exception $e) {
            Log::error('Erro ao invalidar todo o cache: '.$e->getMessage());

            return false;
        }
    }

    public function getReportTtl(string $type = 'structural'): int
    {
        return $this->ttlConfig[$type] ?? 30;
    }

    public function getCacheStats(): array
    {
        $stats = [];

        $stats['total_reports'] = LocationReport::count();
        $stats['completed_reports'] = LocationReport::where('status', 'completed')->count();
        $stats['failed_reports'] = LocationReport::where('status', 'failed')->count();
        $stats['processing_reports'] = LocationReport::whereIn('status', ['processing', 'processing_text'])->count();

        $stats['expired_reports'] = LocationReport::where('updated_at', '<', now()->subDays(180))->count();
        $stats['recent_reports'] = LocationReport::where('updated_at', '>=', now()->subDays(7))->count();

        return $stats;
    }

    public function cleanExpiredCache(): int
    {
        $count = 0;

        $expiredReports = LocationReport::where('updated_at', '<', now()->subDays(180))
            ->where('status', '!=', 'failed')
            ->get();

        foreach ($expiredReports as $report) {
            $this->invalidateReport($report->cep);
            $count++;
        }

        Log::info("Cache expirado limpo: {$count} relatórios");

        return $count;
    }
}
