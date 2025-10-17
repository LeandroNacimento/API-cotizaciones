<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\DashboardCotizaciones;
use App\Livewire\Cotizar;

Route::get('/', DashboardCotizaciones::class)->name('dashboard');
Route::get('/cotizar', Cotizar::class)->name('cotizar');