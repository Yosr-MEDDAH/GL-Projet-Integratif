<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TwoFactorAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::controller(AuthController::class)->prefix('auth')->group(function () {
    Route::post('/login', 'login');
    Route::post('/logout', 'logout')->middleware('jwt.auth');
});

Route::controller(TwoFactorAuthController::class)->prefix('2fa')->group(function () {
    Route::post('/toggle_2fa', 'toggle_2fa')->middleware('jwt.auth');
    Route::post('/verify', 'verify');
});
