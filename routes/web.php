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

    // Volt::route('register', 'auth.register')
    // ->name('register');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
 

    Volt::route('pessoas/servos/create', 'servos.create')->name('servos.create');
    Volt::route('pessoas/servos', 'servos.index')->name('servos.index');
    Volt::route('pessoas/servos/{id}', 'servos.show')->name('servos.show');
    Volt::route('pessoas/servos/{id}/edit', 'servos.edit')->name('servos.edit');

    Volt::route('pessoas/retirante/create', 'retirantes.create')->name('retirantes.create');
    Volt::route('pessoas/retirante', 'retirantes.index')->name('retirantes.index');
    Volt::route('pessoas/retirante/{id}', 'retirantes.show')->name('retirantes.show');
    Volt::route('pessoas/retirante/{id}/edit', 'retirantes.edit')->name('retirantes.edit');

    Volt::route('retiros/create', 'retiros.create')->name('retiros.create');
    Volt::route('retiros', 'retiros.index')->name('retiros.index');
    Volt::route('retiros/{id}', 'retiros.show')->name('retiros.show');
    Volt::route('retiros/{id}/edit', 'retiros.edit')->name('retiros.edit');

    Volt::route('servos-retiros/{retiroId}', 'servos-retiros.index')->name('servos-retiros.index');


});

require __DIR__ . '/auth.php';
