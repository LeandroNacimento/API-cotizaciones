<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\DashboardCotizaciones;

Route::get('/', DashboardCotizaciones::class)->name('dashboard');
