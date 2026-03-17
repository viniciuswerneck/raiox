<?php

namespace App\Http\Controllers;

use App\Models\LlmLog;
use App\Models\AiKey;
use App\Models\LocationReport;
use App\Models\RegionComparison;
use App\Services\LlmRouterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function dashboard()
    {
        $now = Carbon::now();
        $startOfPeriod = $now->copy()->subDays(7)->startOfDay();

        // 1. Métricas da Semana (Últimos 7 Dias)
        $stats = LlmLog::where('created_at', '>=', $startOfPeriod)
            ->select(
                DB::raw('COUNT(*) as total_requests'),
                DB::raw('SUM(total_tokens) as total_tokens'),
                DB::raw('AVG(response_time_ms) as avg_response_time'),
                DB::raw('SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as success_count'),
                DB::raw('SUM(CASE WHEN status != "success" THEN 1 ELSE 0 END) as fail_count')
            )
            ->first();
            
        // 1.5 Métricas Históricas de Produção
        $totalReports = LocationReport::count();
        $totalDuels = RegionComparison::count();
        $reportsToday = LocationReport::where('created_at', '>=', $now->copy()->startOfDay())->count();
        $duelsToday = RegionComparison::where('created_at', '>=', $now->copy()->startOfDay())->count();
        $totalTokensEver = LlmLog::sum('total_tokens');
        // Estimativa aproximada de custo (Misto de GPT-4o-mini e Gemini Flash): ~$0.30 por 1M tokens in+out
        $estimatedCostUsd = ($totalTokensEver / 1000000) * 0.30;
        
        // 1.6 Informações de Infraestrutura
        $appVersion = config('app.version', '3.0.0');
        $phpVersion = phpversion();
        $laravelVersion = app()->version();

        // 2. Uso por Modelo (Top 5 na semana)
        $modelUsage = LlmLog::where('created_at', '>=', $startOfPeriod)
            ->select('model', DB::raw('COUNT(*) as count'))
            ->groupBy('model')
            ->orderBy('count', 'desc')
            ->take(5)
            ->get();

        // 3. Status das Chaves de API
        $apiKeys = AiKey::all()->map(function($key) {
            $cooldown = $key->cooldown_until && $key->cooldown_until->isFuture();
            $key->status = $key->is_active ? ($cooldown ? 'cooldown' : 'online') : 'offline';
            return $key;
        });

        // 4. Logs Recentes
        $recentLogs = LlmLog::orderBy('created_at', 'desc')->take(15)->get();

        // 5. Gráfico de Requisições por Dia (Últimos 7 dias)
        $dailyRequests = LlmLog::where('created_at', '>=', $startOfPeriod)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();
        
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = $now->copy()->subDays($i)->format('Y-m-d');
            $chartData[$d] = $dailyRequests[$d] ?? 0;
        }

        // 6. Modelos Configurador no Router
        $router = app(LlmRouterService::class);
        $reflection = new \ReflectionClass($router);
        $profilesProperty = $reflection->getProperty('profiles');
        $profilesProperty->setAccessible(true);
        $allModels = $profilesProperty->getValue($router);

        return view('admin.dashboard', compact(
            'stats', 
            'modelUsage', 
            'apiKeys', 
            'recentLogs', 
            'chartData',
            'allModels',
            'totalReports',
            'totalDuels',
            'reportsToday',
            'duelsToday',
            'totalTokensEver',
            'estimatedCostUsd',
            'appVersion',
            'phpVersion',
            'laravelVersion'
        ));
    }
}
