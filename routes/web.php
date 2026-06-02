<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ConnectivityController;
use App\Http\Controllers\CriticalStoresController;
use App\Http\Controllers\MapaController;
use App\Http\Controllers\DirectorioController;
use App\Http\Controllers\AperturaController;
use App\Http\Controllers\AuditoriaController;

Route::get('/', [DashboardController::class, 'index']);
Route::post('/refresh', [DashboardController::class, 'refresh']);

Route::get('/conectividad', [ConnectivityController::class, 'index']);
Route::get('/informacion-tiendas', [CriticalStoresController::class, 'index']);
Route::get('/mapa', [MapaController::class, 'index']);
Route::get('/directorio', [DirectorioController::class, 'index']);
Route::get('/aperturas', [AperturaController::class, 'index']);
Route::get('/auditoria', [AuditoriaController::class, 'index']);

Route::post('/set-region', function (Illuminate\Http\Request $r) {
    $region = $r->input('region', '');
    return back()->withCookie(cookie('region_filter', $region ?? '', 43800));
})->middleware('web');
