<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BonDeCommandeConsultation;
use App\Http\Controllers\BordoreauConsultation;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\FactureConsultation;
use App\Http\Controllers\FactureController;
use App\Http\Controllers\FactureExportController;
use App\Http\Controllers\FournisseurAccessController;
use App\Http\Controllers\InfosController;
use App\Http\Controllers\ReclamationController;
use App\Http\Controllers\ResetPassword;
use App\Http\Controllers\SelectionFactureController;
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

Route::controller(InfosController::class)->group((function () {
    Route::get('user/', 'getUser')->middleware('jwt.auth');
    Route::put('user/update', 'updateGeneralInfo')->middleware('jwt.auth');
    Route::post('user/image', 'updateImage')->middleware('jwt.auth');
    Route::put('user/password', 'updatePassword')->middleware('jwt.auth');
    Route::get('users/{userID}/userUploads/img/{imageName}', 'getImage')->middleware('jwt.auth');
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
    Route::get('/rechercheIN', 'rechercheFacture')->middleware('jwt.auth');
    Route::get('/user/facture/{date}/{fileName}', 'getFileInvoice')->middleware('jwt.auth');
});

//Route::get('/factures/export', [FactureExportController::class, 'export'])->middleware('jwt.auth');
Route::get('/factures/export', [FactureExportController::class, 'export']);

Route::controller(ReclamationController::class)->group(function () {
    Route::post('/reclamation/create', 'create')->middleware('jwt.auth');
    Route::get('/reclamations', 'getAllReclamation')->middleware('jwt.auth');
    Route::get('/reclamation', 'getReclamation')->middleware('jwt.auth');
    Route::delete('/reclamation/delete', 'deleteReclamation')->middleware('jwt.auth');
    Route::get('/rechercheRec', 'rechercheRec')->middleware('jwt.auth');
    Route::get('/reclamationsSpec', 'getReclamationSpec')->middleware('jwt.auth');
    Route::get('/users/{userID}/userUploads/reclamation/{fileName}', 'getFileReclamation')->middleware('jwt.auth');
});

Route::controller(BonDeCommandeConsultation::class)->group(function () {
    Route::get('/purchaseOrders', 'getAllPo')->middleware('jwt.auth');
    Route::get('/purchaseOrdersNumbers', 'getAllPoNumbers')->middleware('jwt.auth');
    Route::get('/purchaseOrder', 'getPo')->middleware('jwt.auth');
    Route::get('/recherchePO', 'recherchePo')->middleware('jwt.auth');
});


Route::controller(SelectionFactureController::class)->group(function () {
    Route::get('/objets', 'getObjects')->middleware('jwt.auth');
    Route::get('/PJs', 'getPJs')->middleware('jwt.auth');
});


Route::controller(ConfigurationController::class)->group(function () {
    Route::put('edit/mailer/config', 'editMailer')->middleware('jwt.auth');
});

Route::controller(FournisseurAccessController::class)->group(function () {
    Route::post('create/account', 'accessFournisseur')->middleware('jwt.auth');
    Route::put('update/email/adress', 'updateEmail')->middleware('jwt.auth');
});

Route::controller(BordoreauConsultation::class)->group(function () {
    Route::get('Bordoreaux', 'getAllBordoreau')->middleware('jwt.auth');
    Route::get('Bordoreau/listFacture', 'getBordoreauListFacture')->middleware('jwt.auth');
});
