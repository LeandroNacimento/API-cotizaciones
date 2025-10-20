<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\DashboardCotizaciones;
use App\Livewire\Cotizar;
use App\Http\Controllers\CalendarController;

Route::get('/', DashboardCotizaciones::class)->name('dashboard');
Route::get('/cotizar', Cotizar::class)->name('cotizar');
Route::get('/calendar/ics', [CalendarController::class, 'ics'])->name('calendar.ics');