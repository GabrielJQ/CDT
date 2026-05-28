<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ConnectivityController;
use App\Http\Controllers\CriticalStoresController;

Route::get('/', [DashboardController::class, 'index']);
Route::post('/refresh', [DashboardController::class, 'refresh']);

Route::get('/conectividad', [ConnectivityController::class, 'index']);
Route::get('/tiendas-criticas', [CriticalStoresController::class, 'index']);
