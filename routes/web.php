<?php

use App\Http\Controllers\AperturaController;
use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\CasaPorCasaController;
use App\Http\Controllers\ConnectivityController;
use App\Http\Controllers\CriticalStoresController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DirectorioController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\MapaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::post('/refresh', [DashboardController::class, 'refresh'])->name('refresh');

Route::get('/conectividad', [ConnectivityController::class, 'index']);
Route::get('/informacion-tiendas', [CriticalStoresController::class, 'index']);
Route::get('/mapa', [MapaController::class, 'index']);
Route::get('/mapa/data', [MapaController::class, 'data'])->name('mapa.data');
Route::get('/directorio', [DirectorioController::class, 'index']);
Route::get('/aperturas', [AperturaController::class, 'index']);
Route::get('/auditoria', [AuditoriaController::class, 'index']);

Route::controller(ImportController::class)->prefix('carga-masiva')->name('imports.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/upload', 'upload')->name('upload');
    Route::post('/upload-casa-x-casa', 'uploadCasaPorCasa')->name('upload-casa-x-casa');
});

Route::prefix('casa-x-casa')->name('casa-x-casa.')->controller(CasaPorCasaController::class)->group(function () {
    Route::get('/', 'dashboard')->name('dashboard');
    Route::get('/directorio', 'directorio')->name('directorio');
    Route::get('/mapa', 'mapa')->name('mapa');
    Route::get('/tienda/{id}', 'show')->name('show');
});

Route::post('/set-region', function (Request $r) {
    $region = $r->input('region', '');
    $uo = $r->input('uo', '');
    $redirect = $r->input('redirect', url()->previous());

    return redirect($redirect)
        ->withCookie(cookie('region_filter', $region, 43800))
        ->withCookie(cookie('uo_filter', $uo, 43800));
})->middleware('web');
