<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CompareController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/health', [HealthController::class, 'index'])->name('health');
Route::get('/health/simple', [HealthController::class, 'simple'])->name('health.simple');

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/sitemap.xml', [SitemapController::class, 'index']);
Route::get('/robots.txt', [SitemapController::class, 'robots']);

Route::get('/api/report-status/{cep}', function ($cep) {
    $report = \App\Models\LocationReport::where('cep', $cep)->first();

    return response()->json(['status' => $report ? $report->status : 'not_found']);
});

Route::get('/api/report-data/{cep}', function ($cep) {
    $report = \App\Models\LocationReport::where('cep', preg_replace('/\D/', '', $cep))->first();
    if (! $report) {
        return response()->json(['error' => 'not found'], 404);
    }

    return response()->json($report);
});

// Rota para disparar a fila manualmente no local (Simulando o Cron)
Route::get('/api/trigger-queue', function () {
    set_time_limit(180);

    $lockKey = 'queue_trigger_lock';
    if (\Illuminate\Support\Facades\Cache::has($lockKey)) {
        return response()->json(['message' => 'Fila já está sendo processada por outro gatilho.']);
    }

    try {
        \Illuminate\Support\Facades\Cache::put($lockKey, true, 90);

        \Illuminate\Support\Facades\Artisan::call('queue:work', [
            '--once' => true,
            '--stop-when-empty' => true,
            '--timeout' => 170,
        ]);

        \Illuminate\Support\Facades\Cache::forget($lockKey);

        return response()->json(['message' => 'Fila processada com sucesso (1 item).']);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Cache::forget($lockKey);

        return response()->json(['error' => $e->getMessage()], 500);
    }
});

Route::middleware(['throttle:60,1'])->group(function () {
    Route::get('/explorar', [RankingController::class, 'index'])->name('ranking.index');
    Route::get('/suggestions', [ReportController::class, 'suggestions'])->name('suggestions');
    Route::post('/search', [ReportController::class, 'search'])->name('search');
    Route::post('/report/{cep}/reprocess-narrative', [ReportController::class, 'reprocessNarrative'])->name('report.reprocess');
    Route::get('/cep/{cep}', [ReportController::class, 'show'])->name('report.show');
    Route::get('/cep/{cep}/reprocessar', [ReportController::class, 'reprocessFull'])->name('report.reprocess_full');
    Route::get('/cidade/{slug}', [CityController::class, 'show'])->name('city.show');
    Route::get('/cidade/{slug}/reprocessar', [CityController::class, 'reprocess'])->name('city.reprocess');
    Route::get('/duelos', [CompareController::class, 'index'])->name('duels.index');
    Route::get('/compare/{cepA}/{cepB}', [CompareController::class, 'show'])->name('report.compare');
    Route::get('/ranking/{slug}', [RankingController::class, 'cityRanking'])->name('ranking.city');
    Route::get('/compare/{cepA}/{cepB}/reprocessar', [CompareController::class, 'reprocess'])->name('report.compare_reprocess');
    Route::get('/cidade/{slug}/pois', [CityPOIController::class, 'getPOIsByCategory'])->name('city.pois');

    // Área Administrativa Protegida
    Route::prefix('admin')->middleware('auth')->group(function () {
        Route::get('/', [AdminController::class, 'dashboard'])->name('admin.dashboard');
        Route::get('/cep-scan', [CepScanController::class, 'index'])->name('admin.cep-scan');
        Route::post('/api-keys/{keyId}/reset', [AdminController::class, 'resetApiKey']);
        Route::post('/api-keys/{keyId}/toggle', [AdminController::class, 'toggleApiKey']);
        Route::post('/action/clear-cache', [AdminController::class, 'clearCache']);
        Route::post('/action/clear-cooldowns', [AdminController::class, 'clearAiCooldowns']);
        Route::post('/action/restart-queue', [AdminController::class, 'restartQueue']);
        Route::post('/action/clear-failed', [AdminController::class, 'clearFailedJobs']);
        Route::get('/export', [AdminController::class, 'exportLogs'])->name('admin.export');
    });
});

// Rota para Limpeza Geral (Útil para Produção/Hostinger)
Route::get('/clear-cache', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('cache:clear');
        \Illuminate\Support\Facades\Artisan::call('config:clear');
        \Illuminate\Support\Facades\Artisan::call('view:clear');
        \App\Models\AiKey::query()->update(['cooldown_until' => null]);

        return response()->json([
            'status' => 'success',
            'message' => 'Cache limpo e chaves de IA liberadas com sucesso!',
            'actions' => ['cache:clear', 'config:clear', 'ai_keys_reset'],
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
});
