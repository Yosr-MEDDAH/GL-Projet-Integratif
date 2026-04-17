<?php

namespace App\Services;

use App\Models\Facture;
use App\Models\BonDeCommande;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\OCL\BordereauOCL;

class GestionDocumentsFacade
{
    protected FactureService $factureService;

    public function __construct()
    {
        $this->factureService = new FactureService();
    }

    /**
     * Supprimer une facture
     */
    public function supprimerFacture(Request $request): array
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        if ($role->id !== 3 && $role->id !== 2) {
            return ['success' => false, 'message' => 'Accès refusé', 'code' => 403];
        }

        $facture = Facture::find($request->input('id'));
        if (!$facture) {
            return ['success' => false, 'message' => 'Facture introuvable', 'code' => 404];
        }

        $result = $this->factureService->supprimerFacture($facture, $user, $role);
        $result['code'] = $result['success'] ? 200 : 403;
        return $result;
    }

    /**
     * Créer une facture avec gestion bordereau + PDF + notifications
     */
    public function creerFacture(Request $request): array
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();
        $role_id = $role->id;

        if ($role_id !== 3 && $role_id !== 2) {
            return ['success' => false, 'message' => 'Accès refusé', 'code' => 403];
        }

        $purOrder = BonDeCommande::where('num_commande', $request->input('num_commande'))->first();
        if (!$purOrder) {
            return ['success' => false, 'message' => 'Bon de commande introuvable', 'code' => 404];
        }

        $bord = $this->factureService->gererBordereau('3WM');

        // vérification ocl 
        $oclBordereau = new BordereauOCL();
        if (!$oclBordereau->peutAjouterFacture($bord, 1)) {
            return [
                'success' => false,
                'message' => 'Contrainte OCL violée : le bordereau contient des factures de type différent',
                'code'    => 422
            ];
        }
        $fourName = User::where('idFiscale', $purOrder->four_idFiscale)->first()->name;
        $count = Facture::where('borderau_id', $bord->id)->count();
        $pdfResult = $this->factureService->fusionnerPdfs(
            $request->file('invoice_file_path'),
            $fourName,
            $count
        );

        $factureData = [
            'number'           => $request->input('number'),
            'invoice_name'     => $request->input('invoice_name'),
            'organization'     => $request->input('organization'),
            'billing_date'     => $request->input('billing_date'),
            'amount'           => $request->input('amount'),
            'type_facture_id'  => 1,
            'invoice_file_path'=> $pdfResult['filePath'],
            'reception_date'   => Carbon::now(),
            'isArchived'       => 0,
            'etat_id'          => 1,
            'objet_facture_id' => $request->input('objet_facture_id'),
            'pieces_jointes'   => json_decode($request->input('pieces_jointes'), true),
            'borderau_id'      => $bord->id,
            'bon_de_commande_id' => $purOrder->id,
            'created_by'       => $role->name,
            'fournisseur_id'   => $role_id === 3 ? $user->id : null,
            'agent_bof_id'     => $role_id === 2 ? $user->id : null,
        ];

        $facture = Facture::create($factureData);
        $purOrder->hasInvoice = 1;
        $purOrder->save();

        $this->factureService->notifierAgentsBof($facture, $user, $request->input('number'));

        return ['success' => true, 'message' => 'Facture créée avec succès', 'code' => 200];
    }
}