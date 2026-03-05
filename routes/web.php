<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ReportController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\SitemapController;

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
    // Evita múltiplos disparos simultâneos (lock simples de 60s)
    $lockKey = 'queue_trigger_lock';
    if (\Illuminate\Support\Facades\Cache::has($lockKey)) {
        return response()->json(['message' => 'Fila já está sendo processada por outro gatilho.']);
    }

    try {
        \Illuminate\Support\Facades\Cache::put($lockKey, true, 60);
        
        \Illuminate\Support\Facades\Artisan::call('queue:work', [
            '--once' => true,
            '--stop-when-empty' => true,
            '--timeout' => 120
        ]);
        
        \Illuminate\Support\Facades\Cache::forget($lockKey);
        return response()->json(['message' => 'Fila processada com sucesso (1 item).']);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Cache::forget($lockKey);
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

Route::get('/explorar', [RankingController::class, 'index'])->name('ranking.index');
Route::post('/search', [ReportController::class, 'search'])->name('search');
Route::get('/suggestions', [ReportController::class, 'suggestions'])->name('suggestions');
Route::get('/cep/{cep}', [ReportController::class, 'show'])->name('report.show');
Route::get('/compare/{cep1}/{cep2}', [ReportController::class, 'compare'])->name('report.compare');
