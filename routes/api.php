<?php

use App\Http\Controllers\CotizacionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('throttle:30,1')->group(function () {
    Route::get('/convertir', [CotizacionController::class, 'convertir']);
    Route::get('/promedio-mensual', [CotizacionController::class, 'promedioMensual']);
    Route::get('/historial', [CotizacionController::class, 'historialMensual']);
});