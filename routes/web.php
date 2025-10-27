<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Http\Controllers\RegistrationController;

Route::get('/', [RegistrationController::class, 'show'])->name('home');
Route::get('/signup', [RegistrationController::class, 'show'])->name('signup');
Route::post('/register', [RegistrationController::class, 'register'])->name('register.submit');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
