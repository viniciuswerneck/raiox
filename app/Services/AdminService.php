<?php

namespace App\Services;

use App\Models\AiKey;
use App\Models\LlmLog;
use App\Models\LocationReport;
use App\Models\RegionComparison;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class AdminService
{
    private array $defaultPeriods = [
        '7d' => 7,
        '30d' => 30,
        '90d' => 90,
    ];

    public function getPeriodDays(?string $period): int
    {
        return $this->defaultPeriods[$period] ?? 7;
    }

    public function getOverviewStats(int $days = 7): array
    {
        $startOfPeriod = Carbon::now()->subDays($days)->startOfDay();
        $previousPeriodStart = Carbon::now()->subDays($days * 2)->startOfDay();
        $now = Carbon::now();

        $currentStats = LlmLog::where('created_at', '>=', $startOfPeriod)
            ->select(
                DB::raw('COUNT(*) as total_requests'),
                DB::raw('SUM(total_tokens) as total_tokens'),
                DB::raw('AVG(response_time_ms) as avg_response_time'),
                DB::raw('SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as success_count'),
                DB::raw('SUM(CASE WHEN status != "success" THEN 1 ELSE 0 END) as fail_count')
            )
            ->first();

        $previousStats = LlmLog::whereBetween('created_at', [$previousPeriodStart, $startOfPeriod])
            ->select(
                DB::raw('COUNT(*) as total_requests'),
                DB::raw('SUM(total_tokens) as total_tokens')
            )
            ->first();

        $requestsChange = $this->calculateChange(
            $currentStats->total_requests ?? 0,
            $previousStats->total_requests ?? 0
        );

        $tokensChange = $this->calculateChange(
            $currentStats->total_tokens ?? 0,
            $previousStats->total_tokens ?? 0
        );

        return [
            'total_requests' => $currentStats->total_requests ?? 0,
            'total_tokens' => $currentStats->total_tokens ?? 0,
            'avg_response_time' => round($currentStats->avg_response_time ?? 0),
            'success_count' => $currentStats->success_count ?? 0,
            'fail_count' => $currentStats->fail_count ?? 0,
            'error_rate' => $this->calculateErrorRate($currentStats),
            'requests_change' => $requestsChange,
            'tokens_change' => $tokensChange,
        ];
    }

    public function getLifetimeStats(): array
    {
        $now = Carbon::now();

        return [
            'total_reports' => LocationReport::count(),
            'total_duels' => RegionComparison::count(),
            'reports_today' => LocationReport::where('created_at', '>=', $now->copy()->startOfDay())->count(),
            'duels_today' => RegionComparison::where('created_at', '>=', $now->copy()->startOfDay())->count(),
            'total_tokens_ever' => LlmLog::sum('total_tokens'),
            'estimated_cost_usd' => $this->calculateEstimatedCost(),
        ];
    }

    public function getModelUsage(int $days = 7): array
    {
        $startOfPeriod = Carbon::now()->subDays($days)->startOfDay();

        return LlmLog::where('created_at', '>=', $startOfPeriod)
            ->select('model', 'provider', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_tokens) as tokens'))
            ->groupBy('model', 'provider')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();
    }

    public function getAgentPerformance(int $days = 7): array
    {
        $startOfPeriod = Carbon::now()->subDays($days)->startOfDay();

        return LlmLog::where('created_at', '>=', $startOfPeriod)
            ->select(
                'agent_name',
                DB::raw('COUNT(*) as count'),
                DB::raw('AVG(response_time_ms) as avg_time'),
                DB::raw('SUM(total_tokens) as tokens'),
                DB::raw('SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as success'),
                DB::raw('SUM(CASE WHEN status != "success" THEN 1 ELSE 0 END) as fails')
            )
            ->groupBy('agent_name')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();
    }

    public function getDailyRequests(int $days = 7): array
    {
        $startOfPeriod = Carbon::now()->subDays($days)->startOfDay();
        $now = Carbon::now();

        $dailyRequests = LlmLog::where('created_at', '>=', $startOfPeriod)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as requests'),
                DB::raw('SUM(total_tokens) as tokens'),
                DB::raw('AVG(response_time_ms) as avg_time')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('requests', 'date')
            ->toArray();

        $chartData = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = $now->copy()->subDays($i)->format('Y-m-d');
            $chartData[$d] = $dailyRequests[$d] ?? 0;
        }

        return $chartData;
    }

    public function getHourlyDistribution(int $days = 7): array
    {
        $startOfPeriod = Carbon::now()->subDays($days)->startOfDay();

        $driver = config('database.default');

        if ($driver === 'sqlite') {
            $logs = LlmLog::where('created_at', '>=', $startOfPeriod)->get();
            $hourly = [];
            foreach ($logs as $log) {
                $hour = (int) $log->created_at->format('H');
                $hourly[$hour] = ($hourly[$hour] ?? 0) + 1;
            }
        } else {
            $hourly = LlmLog::where('created_at', '>=', $startOfPeriod)
                ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('COUNT(*) as count'))
                ->groupBy('hour')
                ->orderBy('hour')
                ->get()
                ->pluck('count', 'hour')
                ->map(fn ($v) => (int) $v)
                ->toArray();
        }

        $distribution = [];
        for ($h = 0; $h < 24; $h++) {
            $distribution[$h] = $hourly[$h] ?? 0;
        }

        return $distribution;
    }

    public function getTopLocations(int $limit = 10): array
    {
        return LocationReport::select('cidade', 'uf', DB::raw('COUNT(*) as count'))
            ->where('status', 'completed')
            ->groupBy('cidade', 'uf')
            ->orderBy('count', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function getTopCeps(int $limit = 10): array
    {
        return LocationReport::select('cep', 'cidade', 'uf', DB::raw('COUNT(*) as views'))
            ->where('status', 'completed')
            ->groupBy('cep', 'cidade', 'uf')
            ->orderBy('views', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function getReportStatusDistribution(): array
    {
        $distribution = LocationReport::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'completed' => $distribution['completed'] ?? 0,
            'processing' => $distribution['processing'] ?? 0,
            'failed' => $distribution['failed'] ?? 0,
        ];
    }

    public function getApiKeysStatus(): array
    {
        $keys = AiKey::all()->map(function ($key) {
            $cooldown = $key->cooldown_until && $key->cooldown_until->isFuture();
            $lastUsage = LlmLog::where('ai_key_id', $key->id)
                ->where('created_at', '>=', Carbon::now()->subHours(24))
                ->count();

            return [
                'id' => $key->id,
                'provider' => $key->provider,
                'email' => $key->email,
                'key_preview' => substr($key->key, -4),
                'is_active' => $key->is_active,
                'is_on_cooldown' => $cooldown,
                'cooldown_until' => $key->cooldown_until?->toIso8601String(),
                'usage_24h' => $lastUsage,
                'status' => $key->is_active ? ($cooldown ? 'cooldown' : 'online') : 'offline',
            ];
        });

        return $keys->toArray();
    }

    public function getCacheMetrics(): array
    {
        $stats = [
            'driver' => config('cache.default'),
            'stats' => [],
        ];

        if (config('cache.default') === 'redis') {
            try {
                $info = Redis::info();
                $stats['stats'] = [
                    'used_memory' => $info['used_memory_human'] ?? 'N/A',
                    'connected_clients' => $info['connected_clients'] ?? 0,
                    'total_commands' => $info['total_commands_processed'] ?? 0,
                    'keyspace_hits' => $info['keyspace_hits'] ?? 0,
                    'keyspace_misses' => $info['keyspace_misses'] ?? 0,
                ];

                if (isset($info['keyspace_hits']) && isset($info['keyspace_misses'])) {
                    $total = $info['keyspace_hits'] + $info['keyspace_misses'];
                    $stats['hit_rate'] = $total > 0 ? round(($info['keyspace_hits'] / $total) * 100, 2) : 0;
                }
            } catch (\Exception $e) {
                $stats['error'] = 'Redis não disponível: '.$e->getMessage();
            }
        }

        return $stats;
    }

    public function getQueueMetrics(): array
    {
        $metrics = [
            'pending_jobs' => 0,
            'failed_jobs' => 0,
            'recent_failures' => [],
        ];

        try {
            if (config('queue.default') === 'database') {
                $metrics['pending_jobs'] = DB::table('jobs')->count();
                $metrics['failed_jobs'] = DB::table('failed_jobs')->count();
                $metrics['recent_failures'] = DB::table('failed_jobs')
                    ->orderBy('failed_at', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(function ($job) {
                        return [
                            'id' => $job->id,
                            'queue' => $job->queue,
                            'failed_at' => $job->failed_at,
                        ];
                    })
                    ->toArray();
            }
        } catch (\Exception $e) {
            $metrics['error'] = 'Queue não disponível: '.$e->getMessage();
        }

        return $metrics;
    }

    public function getSystemInfo(): array
    {
        return [
            'app_version' => config('app.version', '3.0.0'),
            'laravel_version' => app()->version(),
            'php_version' => phpversion(),
            'timezone' => config('app.timezone'),
            'debug_mode' => config('app.debug'),
            'environment' => config('app.env'),
            'database_driver' => config('database.default'),
            'cache_driver' => config('cache.default'),
            'queue_driver' => config('queue.default'),
        ];
    }

    public function getRateLimitStatus(): array
    {
        $limiter = new ExternalApiRateLimiter;

        return $limiter->getStatus();
    }

    public function getCostProjection(int $days = 7): array
    {
        $startOfPeriod = Carbon::now()->subDays($days)->startOfDay();

        $tokens = LlmLog::where('created_at', '>=', $startOfPeriod)->sum('total_tokens');
        $dailyAvg = $tokens / $days;

        return [
            'tokens_last_7d' => $tokens,
            'daily_average' => round($dailyAvg),
            'projected_monthly_tokens' => round($dailyAvg * 30),
            'projected_monthly_cost' => round(($dailyAvg * 30) / 1000000 * 0.30, 2),
            'cost_per_day' => round(($dailyAvg / 1000000) * 0.30, 4),
        ];
    }

    private function calculateErrorRate($stats): float
    {
        $total = ($stats->success_count ?? 0) + ($stats->fail_count ?? 0);
        if ($total === 0) {
            return 0;
        }

        return round(($stats->fail_count / $total) * 100, 2);
    }

    private function calculateChange(int $current, int $previous): float
    {
        if ($previous === 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function calculateEstimatedCost(): float
    {
        $totalTokens = LlmLog::sum('total_tokens');

        return round(($totalTokens / 1000000) * 0.30, 2);
    }
}
