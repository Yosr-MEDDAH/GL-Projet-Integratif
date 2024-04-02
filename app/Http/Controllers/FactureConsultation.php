<?php

namespace App\Http\Controllers;

use App\Models\Facture;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class FactureConsultation extends Controller
{
    function getInvoice(Request $request)
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        $facture = Facture::find($request->input('id'));
        if (!$facture) {
            return response()->json([
                'success' => false,
                'message' => 'la facture n \'existe pas',
                'data' => [],
            ]);
        }

        if ($role->id === 3 && ($facture->fournisseur_id !== $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'cette facture n\' est pas concerné pour vous',
                'data' => [],
            ]);
        } else {
            return response()->json([
                'success' => true,
                'message' => 'voici les informations de cette facture',
                'data' => $facture,
            ]);
        }


        // pour l'agent bof
        return response()->json([
            'success' => true,
            'message' => 'voici les informations de cette facture',
            'data' => $facture,
        ]);
    }

    function getInvoices(Request $request) // ***react Paginate*** (récupération les factures et le nombres de pages avec chaque call (set les informations nombre de pages et les factures))
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        if ($role->id !== 3 && $role->id !== 2) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette ressource',
                'data' => []
            ], 403); // 403 accés refusé
        }

        $page = $request->query('page', 1);
        $nb = $request->query('nb', 10);
        if ($role->id === 3) {
            $factures = Facture::where('fournisseur_id', $user->id)->paginate($nb, ['*'], 'page', $page);
            return response()->json([
                'success' => true,
                'message' => 'voici votre factures',
                'data' => [
                    'totalPages' => $factures->lastPage(),
                    'factures' => $factures->items(),
                ]
            ]);
        }
        //récupération des factures crée avec un agent bof spécifique
        $factures = Facture::where('agent_bof_id', $user->id)->paginate($nb, ['*'], 'page', $page);
        $totalPages = $factures->lastPage();
        return response()->json([
            'success' => true,
            'message' => 'voici votre factures',
            'data' => [
                'totalPages' => $totalPages,
                'factures' => $factures->items(),
            ]
        ]);
    }

    function rechercheFacture(Request $request)
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        $facture = Facture::where('number', $request->input('number'))->first();
        if (!$facture || ($role->id === 3 && ($facture->fournisseur_id !== $user->id))) {
            return response()->json([
                'success' => false,
                'message' => 'la facture n \'existe pas',
                'data' => [],
            ]);
        } else {
            return response()->json([
                'success' => true,
                'message' => 'voici les informations de cette facture',
                'data' => $facture,
            ]);
        }

        //pour agent bof
        return response()->json([
            'success' => true,
            'message' => 'voici les informations de cette facture',
            'data' => $facture,
        ]);
    }
}
