<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ReportController;
use App\Http\Controllers\RankingController;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/explorar', [RankingController::class, 'index'])->name('ranking.index');
Route::post('/search', [ReportController::class, 'search'])->name('search');
Route::get('/suggestions', [ReportController::class, 'suggestions'])->name('suggestions');
Route::get('/cep/{cep}', [ReportController::class, 'show'])->name('report.show');
Route::get('/compare/{cep1}/{cep2}', [ReportController::class, 'compare'])->name('report.compare');
