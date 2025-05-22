<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('register', 'auth.register')
    ->name('register');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
 

    Volt::route('pessoas/servos/create', 'servos.create')->name('servos.create');
    Volt::route('pessoas/servos', 'servos.index')->name('servos.index');
    Volt::route('pessoas/retirante/create', 'retirantes.create')->name('retirantes.create');
    Volt::route('pessoas/retirante', 'retirantes.index')->name('retirantes.index');

});

require __DIR__ . '/auth.php';
