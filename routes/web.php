<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ReportController;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::post('/search', [ReportController::class, 'search'])->name('search');
Route::get('/suggestions', [ReportController::class, 'suggestions'])->name('suggestions');
Route::get('/cep/{cep}', [ReportController::class, 'show'])->name('report.show');
