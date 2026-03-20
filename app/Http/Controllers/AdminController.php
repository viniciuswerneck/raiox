<?php

namespace App\Http\Controllers;

use App\Models\AiKey;
use App\Services\AdminService;
use App\Services\ExternalApiRateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function __construct(
        private AdminService $adminService,
        private ExternalApiRateLimiter $rateLimiter
    ) {}

    public function dashboard(Request $request)
    {
        $period = $request->get('period', '7d');
        $days = $this->adminService->getPeriodDays($period);

        $overviewStats = $this->adminService->getOverviewStats($days);
        $lifetimeStats = $this->adminService->getLifetimeStats();
        $modelUsage = $this->adminService->getModelUsage($days);
        $agentPerformance = $this->adminService->getAgentPerformance($days);
        $dailyRequests = $this->adminService->getDailyRequests($days);
        $hourlyDistribution = $this->adminService->getHourlyDistribution($days);
        $topLocations = $this->adminService->getTopLocations(10);
        $topCeps = $this->adminService->getTopCeps(10);
        $reportStatus = $this->adminService->getReportStatusDistribution();
        $apiKeysStatus = $this->adminService->getApiKeysStatus();
        $cacheMetrics = $this->adminService->getCacheMetrics();
        $queueMetrics = $this->adminService->getQueueMetrics();
        $systemInfo = $this->adminService->getSystemInfo();
        $rateLimits = $this->adminService->getRateLimitStatus();
        $costProjection = $this->adminService->getCostProjection($days);

        $recentLogs = \App\Models\LlmLog::orderBy('created_at', 'desc')->take(15)->get();
        $router = app(\App\Services\LlmRouterService::class);
        $reflection = new \ReflectionClass($router);
        $profilesProperty = $reflection->getProperty('profiles');
        $profilesProperty->setAccessible(true);
        $allModels = $profilesProperty->getValue($router);

        return view('admin.dashboard', compact(
            'period',
            'overviewStats',
            'lifetimeStats',
            'modelUsage',
            'agentPerformance',
            'dailyRequests',
            'hourlyDistribution',
            'topLocations',
            'topCeps',
            'reportStatus',
            'apiKeysStatus',
            'cacheMetrics',
            'queueMetrics',
            'systemInfo',
            'rateLimits',
            'costProjection',
            'recentLogs',
            'allModels'
        ));
    }

    public function resetApiKey(Request $request, int $keyId)
    {
        $key = AiKey::findOrFail($keyId);
        $key->update([
            'cooldown_until' => null,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Chave {$key->provider} resetada com sucesso",
        ]);
    }

    public function toggleApiKey(Request $request, int $keyId)
    {
        $key = AiKey::findOrFail($keyId);
        $key->update(['is_active' => ! $key->is_active]);

        return response()->json([
            'success' => true,
            'message' => "Chave {$key->provider} ".($key->is_active ? 'ativada' : 'desativada'),
        ]);
    }

    public function clearCache(Request $request)
    {
        Cache::flush();

        return response()->json([
            'success' => true,
            'message' => 'Cache limpo com sucesso',
        ]);
    }

    public function clearFailedJobs(Request $request)
    {
        if (config('queue.default') === 'database') {
            DB::table('failed_jobs')->truncate();
        }

        return response()->json([
            'success' => true,
            'message' => 'Jobs com falha removidos',
        ]);
    }

    public function retryFailedJobs(Request $request)
    {
        try {
            \Illuminate\Support\Facades\Artisan::call('queue:retry', ['--failed' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Jobs com falha sendo reprocessados',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro: '.$e->getMessage(),
            ], 500);
        }
    }

    public function restartQueue(Request $request)
    {
        try {
            \Illuminate\Support\Facades\Artisan::call('queue:restart');

            return response()->json([
                'success' => true,
                'message' => 'Fila reiniciada',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro: '.$e->getMessage(),
            ], 500);
        }
    }

    public function clearAiCooldowns(Request $request)
    {
        AiKey::query()->update(['cooldown_until' => null]);

        return response()->json([
            'success' => true,
            'message' => 'Todos os cooldowns de IA foram removidos',
        ]);
    }

    public function exportLogs(Request $request)
    {
        $period = $request->get('period', '7d');
        $days = $this->adminService->getPeriodDays($period);
        $startOfPeriod = now()->subDays($days);

        $logs = \App\Models\LlmLog::where('created_at', '>=', $startOfPeriod)
            ->orderBy('created_at', 'desc')
            ->get();

        $csv = "Data,Hora,Agente,Modelo,Provedor,Tempo (ms),Tokens,Status,Erro\n";
        foreach ($logs as $log) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%d,%d,%s,%s\n",
                $log->created_at->format('Y-m-d'),
                $log->created_at->format('H:i:s'),
                $log->agent_name,
                $log->model,
                $log->provider,
                $log->response_time_ms ?? 0,
                $log->total_tokens ?? 0,
                $log->status,
                str_replace(',', ';', $log->error_message ?? '')
            );
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="llm_logs_'.date('Y-m-d').'.csv"',
        ]);
    }
}
