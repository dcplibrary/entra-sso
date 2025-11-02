<?php

use Illuminate\Support\Facades\Route;
use Dcplibrary\EntraSSO\Http\Controllers\EntraSSOController;
use Dcplibrary\EntraSSO\Http\Controllers\DashboardController;

Route::middleware(['web'])->group(function () {
    Route::get('/auth/entra', [EntraSSOController::class, 'redirect'])->name('entra.login');
    Route::get('/auth/entra/callback', [EntraSSOController::class, 'callback'])->name('entra.callback');
    Route::post('/auth/entra/logout', [EntraSSOController::class, 'logout'])->name('entra.logout');
    Route::get('/login', function () {
        return view('entra-sso::auth.login');
    })->name('login');
    Route::get('/entra/dashboard', [DashboardController::class, 'index'])->middleware('auth')->name('entra.dashboard');
});
