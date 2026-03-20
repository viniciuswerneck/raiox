<?php

namespace App\Http\Controllers;

use App\Services\ExternalApiRateLimiter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function __construct(
        private ExternalApiRateLimiter $rateLimiter
    ) {}

    public function index(): JsonResponse
    {
        $health = [
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'services' => [
                'database' => $this->checkDatabase(),
                'cache' => $this->checkCache(),
                'apis' => $this->rateLimiter->checkAllApisHealth(),
                'rate_limits' => $this->rateLimiter->getStatus(),
            ],
        ];

        $hasUnhealthy = collect($health['services']['apis'])->contains('status', 'unreachable');

        if ($hasUnhealthy) {
            $health['status'] = 'degraded';
        }

        return response()->json($health, $hasUnhealthy ? 503 : 200);
    }

    public function simple(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    private function checkDatabase(): array
    {
        try {
            $startTime = microtime(true);
            DB::connection()->getPdo();
            $latency = round((microtime(true) - $startTime) * 1000);

            return [
                'status' => 'healthy',
                'latency_ms' => $latency,
                'driver' => config('database.default'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'latency_ms' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function checkCache(): array
    {
        try {
            $testKey = 'health_check_'.time();
            $testValue = 'ok';

            $startTime = microtime(true);
            Cache::put($testKey, $testValue, 10);
            $writeLatency = round((microtime(true) - $startTime) * 1000);

            $startTime = microtime(true);
            $result = Cache::get($testKey) === $testValue;
            $readLatency = round((microtime(true) - $startTime) * 1000);
            Cache::forget($testKey);

            return [
                'status' => $result ? 'healthy' : 'unhealthy',
                'write_latency_ms' => $writeLatency,
                'read_latency_ms' => $readLatency,
                'driver' => config('cache.default'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }
}
