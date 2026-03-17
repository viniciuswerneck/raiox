<?php

namespace App\Http\Controllers;

use App\Models\LlmLog;
use App\Models\AiKey;
use App\Services\LlmRouterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function dashboard()
    {
        $now = Carbon::now();
        $startOfDay = $now->copy()->startOfDay();

        // 1. Métricas Totais do Dia
        $statsToday = LlmLog::where('created_at', '>=', $startOfDay)
            ->select(
                DB::raw('COUNT(*) as total_requests'),
                DB::raw('SUM(total_tokens) as total_tokens'),
                DB::raw('AVG(response_time_ms) as avg_response_time'),
                DB::raw('SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as success_count'),
                DB::raw('SUM(CASE WHEN status != "success" THEN 1 ELSE 0 END) as fail_count')
            )
            ->first();

        // 2. Uso por Modelo (Top 5 hoje)
        $modelUsage = LlmLog::where('created_at', '>=', $startOfDay)
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

        // 5. Gráfico de Requisições por Hora (Últimas 24h)
        $hourlyRequests = LlmLog::where('created_at', '>=', $now->copy()->subHours(24))
            ->select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->pluck('count', 'hour')
            ->toArray();
        
        // Garantir que todas as horas do dia estejam presentes para o gráfico
        $chartData = [];
        for ($i = 0; $i < 24; $i++) {
            $chartData[$i] = $hourlyRequests[$i] ?? 0;
        }

        // 6. Modelos Configurador no Router
        $router = app(LlmRouterService::class);
        $reflection = new \ReflectionClass($router);
        $profilesProperty = $reflection->getProperty('profiles');
        $profilesProperty->setAccessible(true);
        $allModels = $profilesProperty->getValue($router);

        return view('admin.dashboard', compact(
            'statsToday', 
            'modelUsage', 
            'apiKeys', 
            'recentLogs', 
            'chartData',
            'allModels'
        ));
    }
}
