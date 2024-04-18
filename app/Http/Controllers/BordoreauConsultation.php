<?php

namespace App\Http\Controllers;

use App\Models\Bordereau;
use App\Models\Facture;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class BordoreauConsultation extends Controller
{
    function getAllBordoreau(Request $request)
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        if ($role->id !== 2  && $role->id !== 1) {
            return response()->json([
                'success' => false,
                'message' => "vous n'avez pas d'autorisation",
                'data' => [],
            ]);
        }

        $page = $request->query('page', 1);
        $nb = $request->query('nb', 10);

        $bordoreaux = Bordereau::select('id', 'date_sent', 'folder', 'status', 'nature', 'reference')->orderBy('created_at', 'desc')->paginate($nb, ['*'], 'page', $page);
        foreach ($bordoreaux as $bordoreau) {
            $factures = Facture::where("borderau_id", $bordoreau->id)->count();
            $bordoreau->nombreFacture = $factures;
        }

        return response()->json([
            'success' => true,
            'message' => "tous les bordereaux",
            'data' => [
                'totalPages' => $bordoreaux->lastPage(),
                'bordereaux' => $bordoreaux->items(),
            ]
        ]);
    }


    function getBordoreauListFacture(Request $request)
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        if ($role->id !== 2  && $role->id !== 1) {
            return response()->json([
                'success' => false,
                'message' => "vous n'avez pas d'autorisation",
                'data' => [],
            ]);
        }

        $bordoreau = Bordereau::find($request->input('id'));
        if (!$bordoreau) {
            return response()->json([
                'success' => false,
                'message' => 'le bordoreau n \'existe pas',
                'data' => [],
            ]);
        }
        $page = $request->query('page', 1);
        $nb = $request->query('nb', 10);
        $listFacture = Facture::select('id', 'number', 'invoice_name', 'type', 'invoice_file_path', 'fournisseur_id', 'agent_bof_id', 'created_by', 'bon_de_commande_id', 'etat_id')
            ->where('borderau_id', $request->input('id'))
            ->orderBy('created_at', 'desc')
            ->paginate($nb, ['*'], 'page', $page);
        foreach ($listFacture as $facture) {
            foreach ($listFacture as $facture) {
                if ($facture->fournisseur_id !== null) {
                    $user = User::select('name')->where('id', $facture->fournisseur_id)->first();
                    $facture->nameCreatedBy = $user->name;
                } else {
                    $user = User::select('name')->where('id', $facture->agent_bof_id)->first();
                    $facture->nameCreatedBy = $user->name;
                }
                $etat = $facture->etat()->first();
                if ($etat === null || $etat->name_etat === null) {
                    $facture->etat = null;
                } else {
                    $facture->etat = $etat->name_etat;
                }
                $bonDeCommande = $facture->bonDeCommande()->first();
                if ($bonDeCommande === null || $bonDeCommande->num_commande === null) {
                    $facture->numBonCommande = null;
                } else {
                    $facture->numBonCommande = $bonDeCommande->num_commande;
                }
            }
            return response()->json([
                'success' => true,
                'message' => "les factures trouvées dans le bordoreau spécifié",
                'data' => [
                    'totalPages' => $listFacture->lastPage(),
                    'factures' => $listFacture->items(),
                ]
            ]);
        }
    }
}
