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
    
    // Se o serviço ou um usuário anterior marcou como pending mas ninguém processou,
    // o primeiro polling que chegar vai disparar o "Pseudo-Worker"
    if ($report && ($report->status === 'pending' || $report->status === 'failed')) {
        try {
            \App\Jobs\ProcessLocationReport::dispatchSync($cep);
            $report->refresh();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Web-Worker Failed: " . $e->getMessage());
        }
    }

    return response()->json(['status' => $report ? $report->status : 'not_found']);
});

Route::get('/explorar', [RankingController::class, 'index'])->name('ranking.index');
Route::post('/search', [ReportController::class, 'search'])->name('search');
Route::get('/suggestions', [ReportController::class, 'suggestions'])->name('suggestions');
Route::get('/cep/{cep}', [ReportController::class, 'show'])->name('report.show');
Route::get('/compare/{cep1}/{cep2}', [ReportController::class, 'compare'])->name('report.compare');
