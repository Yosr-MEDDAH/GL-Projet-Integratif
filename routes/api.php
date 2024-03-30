<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FactureConsultation;
use App\Http\Controllers\FactureController;
use App\Http\Controllers\InfosController;
use App\Http\Controllers\ResetPassword;
use App\Http\Controllers\TwoFactorAuthController;
use App\Models\Facture;
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
    Route::post('/refresh', 'refreshToken');
});

Route::controller(TwoFactorAuthController::class)->prefix('2fa')->group(function () {
    Route::put('/toggle_2fa', 'toggle_2fa')->middleware(['jwt.auth']);
    Route::post('/verify', 'verify');
});

Route::controller(InfosController::class)->prefix('user')->group((function () {
    Route::get('/', 'getUser')->middleware('jwt.auth');
    Route::put('/update', 'updateGeneralInfo')->middleware('jwt.auth');
    Route::post('/image', 'updateImage')->middleware('jwt.auth');
    Route::put('/password', 'updatePassword')->middleware('jwt.auth');
}));

Route::controller(ResetPassword::class)->prefix('reset')->group(function () {
    Route::post('/verifyEmail', 'verifyEmail');
    Route::post('/resetPassword', 'resetPassword');
});

Route::controller(FactureController::class)->prefix('facture')->group(function () {
    Route::post('/create', 'createInvoice')->middleware('jwt.auth');
    Route::delete('/delete', 'deleteInvoice')->middleware('jwt.auth');
    Route::put('/update', 'updateInvoice')->middleware('jwt.auth');
});

Route::controller(FactureConsultation::class)->group(function () {
    Route::get('/facture', 'getInvoice')->middleware('jwt.auth');
    Route::get('/factures', 'getInvoices')->middleware('jwt.auth');
});
