<?php

namespace App\Http\Controllers;

use App\Http\Controllers\CompareController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/sitemap.xml', [SitemapController::class, 'index']);
Route::get('/robots.txt', [SitemapController::class, 'robots']);

Route::get('/api/report-status/{cep}', function ($cep) {
    $report = \App\Models\LocationReport::where('cep', $cep)->first();
    return response()->json(['status' => $report ? $report->status : 'not_found']);
});

// Rota para disparar a fila manualmente no local (Simulando o Cron)
Route::get('/api/trigger-queue', function () {
    // Aumenta o tempo para o worker web não morrer prematuramente
    set_time_limit(180);

    // Evita múltiplos disparos simultâneos (lock simples de 60s)
    $lockKey = 'queue_trigger_lock';
    if (\Illuminate\Support\Facades\Cache::has($lockKey)) {
        return response()->json(['message' => 'Fila já está sendo processada por outro gatilho.']);
    }

    try {
        \Illuminate\Support\Facades\Cache::put($lockKey, true, 90);
        
        \Illuminate\Support\Facades\Artisan::call('queue:work', [
            '--once' => true,
            '--stop-when-empty' => true,
            '--timeout' => 170
        ]);
        
        \Illuminate\Support\Facades\Cache::forget($lockKey);
        return response()->json(['message' => 'Fila processada com sucesso (1 item).']);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Cache::forget($lockKey);
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

Route::middleware(['throttle:30,1'])->group(function () {
    Route::get('/explorar', [RankingController::class, 'index'])->name('ranking.index');
    Route::get('/suggestions', [ReportController::class, 'suggestions'])->name('suggestions');
});

Route::middleware(['throttle:10,1'])->group(function () {
    Route::post('/search', [ReportController::class, 'search'])->name('search');
    Route::post('/report/{cep}/reprocess-narrative', [ReportController::class, 'reprocessNarrative'])->name('report.reprocess');
    Route::get('/cep/{cep}', [ReportController::class, 'show'])->name('report.show');
    Route::get('/cep/{cep}/reprocessar', [ReportController::class, 'reprocessFull'])->name('report.reprocess_full');
    Route::get('/cidade/{slug}', [CityController::class, 'show'])->name('city.show');
    Route::get('/cidade/{slug}/reprocessar', [CityController::class, 'reprocess'])->name('city.reprocess');
    Route::get('/duelos', [CompareController::class, 'index'])->name('duels.index');
    Route::get('/compare/{cepA}/{cepB}', [CompareController::class, 'show'])->name('report.compare');
    Route::get('/compare/{cepA}/{cepB}/reprocessar', [CompareController::class, 'reprocess'])->name('report.compare_reprocess');
    Route::get('/cidade/{slug}/pois', [CityPOIController::class, 'getPOIsByCategory'])->name('city.pois');
});

// Rota para Limpeza Geral (Útil para Produção/Hostinger)
Route::get('/clear-cache', function() {
    try {
        // 1. Limpa o Cache da Aplicação
        \Illuminate\Support\Facades\Artisan::call('cache:clear');
        
        // 2. Limpa o Cache de Configuração
        \Illuminate\Support\Facades\Artisan::call('config:clear');
        
        // 3. Libera todas as chaves de IA do "castigo" (cooldown)
        \App\Models\AiKey::query()->update(['cooldown_until' => null]);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Cache limpo e chaves de IA liberadas com sucesso!',
            'actions' => ['cache:clear', 'config:clear', 'ai_keys_reset']
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});
