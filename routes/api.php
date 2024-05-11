<?php

use App\Http\Controllers\AdministrateurController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BonDeCommandeConsultation;
use App\Http\Controllers\BordoreauConsultation;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\DashboardFournisseur;
use App\Http\Controllers\DashboardPersonnelDCF;
use App\Http\Controllers\FactureConsultation;
use App\Http\Controllers\FactureController;
use App\Http\Controllers\FactureExportController;
use App\Http\Controllers\FilterController;
use App\Http\Controllers\FilterRechercheController;
use App\Http\Controllers\FiltreRechercheController;
use App\Http\Controllers\FournisseurAccessController;
use App\Http\Controllers\InfosController;
use App\Http\Controllers\ReclamationController;
use App\Http\Controllers\ResetPassword;
use App\Http\Controllers\SelectionFactureController;
use App\Http\Controllers\TwoFactorAuthController;
use App\Http\Controllers\ValidationFactureController;
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
    //Route::post('/create', 'createInvoicetest')->middleware('jwt.auth');
    Route::delete('/delete', 'deleteInvoice')->middleware('jwt.auth');
    Route::put('/update', 'updateInvoice')->middleware('jwt.auth');
});

Route::controller(FactureConsultation::class)->group(function () {
    Route::get('/facture', 'getInvoice')->middleware('jwt.auth');
    Route::get('/factures', 'getInvoices')->middleware('jwt.auth');
    Route::get('/facturesParBonDeCommande', 'getfacturesParBonDeCommande')->middleware('jwt.auth');
    Route::get('/storage/userUploads/pdf/invoice/{date}/{fileName}', 'getFileInvoice')->middleware('jwt.auth');
});

//Route::get('/factures/export', [FactureExportController::class, 'export'])->middleware('jwt.auth');
Route::get('/factures/export', [FactureExportController::class, 'export']);

Route::controller(ReclamationController::class)->group(function () {
    Route::post('/reclamation/create', 'create')->middleware('jwt.auth');
    Route::get('/reclamations', 'getAllReclamation')->middleware('jwt.auth');
    Route::get('/reclamation', 'getReclamation')->middleware('jwt.auth');
    Route::delete('/reclamation/delete', 'deleteReclamation')->middleware('jwt.auth');
    Route::get('/reclamationsSpec', 'getReclamationSpec')->middleware('jwt.auth');
    Route::put('/changerEtatReclamation', 'changerEtatReclamation')->middleware('jwt.auth');
    Route::get('/users/{userID}/userUploads/reclamation/{fileName}', 'getFileReclamation')->middleware('jwt.auth');
});

Route::controller(BonDeCommandeConsultation::class)->group(function () {
    Route::get('/purchaseOrders', 'getAllPo')->middleware('jwt.auth');
    Route::get('/purchaseOrdersNumbers', 'getAllPoNumbers')->middleware('jwt.auth');
    Route::get('/purchaseOrder', 'getPo')->middleware('jwt.auth');
});


Route::controller(SelectionFactureController::class)->group(function () {
    Route::get('/objets', 'getObjects')->middleware('jwt.auth');
    Route::get('/PJs', 'getPJs')->middleware('jwt.auth');
});


Route::controller(ConfigurationController::class)->group(function () {
    Route::put('edit/mailer/config', 'editMailer')->middleware('jwt.auth');
});

Route::controller(FournisseurAccessController::class)->group(function () {
    Route::post('/approuverFournisseur', 'accessFournisseur')->middleware('jwt.auth');
    Route::put('update/email/adress', 'updateEmail')->middleware('jwt.auth');
    Route::get('/fournisseursSansCompte', 'getFournisseurSansCompte')->middleware('jwt.auth');
    Route::get('/fournisseursAvecCompte', 'getFournisseurAvecCompte')->middleware('jwt.auth'); // avec compte
});

Route::controller(BordoreauConsultation::class)->group(function () {
    Route::get('Bordereaux', 'getAllBordoreau')->middleware('jwt.auth');
    Route::get('Bordereau/listFacture', 'getBordoreauListFacture')->middleware('jwt.auth');
});

Route::controller(FiltreRechercheController::class)->group(function () {
    Route::get('/filtrageFactureBof', 'filtrageFactureBof')->middleware('jwt.auth');
    Route::get('/rechercheFactureBof', 'rechercheFactureBof')->middleware('jwt.auth');
    Route::get('/rechercheRec', 'rechercheReclamationFournisseur')->middleware('jwt.auth'); // à éléminer
    Route::get('/rechercheReclamationFournisseur', 'rechercheReclamationFournisseur')->middleware('jwt.auth');
    Route::get('/rechercheReclamationBof', 'rechercheReclamationBof')->middleware('jwt.auth');
    Route::get('/filtrageReclamationFournisseur', 'filtrageReclamationFournisseur')->middleware('jwt.auth');
    Route::get('/filtrageReclamationBof', 'filtrageReclamationBof')->middleware('jwt.auth');
    Route::get('/rechercheIN', 'rechercheFactureFournisseur')->middleware('jwt.auth'); // à éléminer
    Route::get('/rechercheFactureFournisseur', 'rechercheFactureFournisseur')->middleware('jwt.auth');
    Route::get('/filtrageFactureFournisseur', 'filtrageFactureFournisseur')->middleware('jwt.auth');
    Route::get('/recherchePoFournisseur', 'recherchePoFournisseur')->middleware('jwt.auth');
    Route::get('/recherchePoBof', 'recherchePoBof')->middleware('jwt.auth');
    Route::get('/filtragePoFournisseur', 'filtragePoFournisseur')->middleware('jwt.auth');
    Route::get('/filtragePoFournisseur', 'filtragePoFournisseur')->middleware('jwt.auth');
    Route::get('/filtragePoBof', 'filtragePoBof')->middleware('jwt.auth');
    Route::get('/rechercheFournisseurSansCompte', 'rechercheFournisseurSansCompte')->middleware('jwt.auth');
    Route::get('/rechercheFournisseurAvecCompte', 'rechercheFournisseurAvecCompte')->middleware('jwt.auth');
    Route::get('/rechercheBordereau', 'rechercheBordoreau')->middleware('jwt.auth');
    Route::get('/rechercheBordereauListFacture', 'rechercheBordoreauListFacture')->middleware('jwt.auth');
});

Route::controller(AdministrateurController::class)->group(function () {
    Route::post('update/default/picture_profile', 'storeDefaultProfilePicture')->middleware('jwt.auth');
    Route::post('createAgent', 'createAgent')->middleware('jwt.auth');
});


Route::controller(ValidationFactureController::class)->group(function () {
    Route::get('/invoicesToValidate', 'invoicesToValidate')->middleware('jwt.auth');
    Route::get('/invoiceToValidate', 'invoiceToValidate')->middleware('jwt.auth');
    Route::put('/valideInvoice', 'valideInvoice')->middleware('jwt.auth');
    Route::get('/invoiceTypeToValidate', 'invoiceTypeToValidate')->middleware('jwt.auth');
    Route::get('/motifsDeRejet', 'motifsDeRejet')->middleware('jwt.auth');
});

Route::controller(DashboardPersonnelDCF::class)->group((function () {
    Route::get("/DashboardPersonnelDCF", "DashboardPersonnelDCF")->middleware('jwt.auth');
}));

Route::controller(DashboardPersonnelDCF::class)->group((function () {
    Route::get("/DashboardPersonnelDCF", "DashboardPersonnelDCF")->middleware('jwt.auth');
}));

Route::controller(DashboardFournisseur::class)->group((function () {
    Route::get("/DashboardFournisseur", "DashboardFournisseur")->middleware('jwt.auth');
}));

