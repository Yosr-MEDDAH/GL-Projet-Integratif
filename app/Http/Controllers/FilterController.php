<?php

namespace App\Http\Controllers;

use App\Models\Facture;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class FilterController extends Controller
{
    function getFactureBof(Request $request)
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        if ($role->id !== 2) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette ressource',
                'data' => []
            ], 403); // 403 accés refusé
        }

        $messages = [
            'type_facture.required' => 'Le type de facture est requis.',
            'etat.required' => 'L\'état de la facture est requis.',
        ];


        $validator = Validator::make($request->all(), [
            'type_facture' => 'string',
            'etat' => 'numeric',
            'cree_par' => 'string',
        ], $messages);


        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $page = $request->query('page', 1);
        $nb = $request->query('nb', 10);

        if ($request->input('cree_par', "tous") === "tous") {
            $factures = Facture::where('type', $request->input('type', '3WM'))
                ->where('etat_id', $request->input('etat', 1))->paginate($nb, ['*'], 'page', $page);
        } elseif ($request->input('cree_par', "tous") === "moi") {
            $factures = Facture::where('type', $request->input('type', '3WM'))
                ->where('etat_id', $request->input('etat', 1))
                ->where('agent_bof_id', $user->id)->paginate($nb, ['*'], 'page', $page);
        } elseif ($request->input('cree_par', "tous") === "fournisseur") {
            $factures = Facture::where('type', $request->input('type', '3WM'))
                ->where('etat_id', $request->input('etat', 1))
                ->where('fournisseur_id', '!=', null)->paginate($nb, ['*'], 'page', $page);
        } elseif ($request->input('cree_par', "tous") === "agent bof") {
            $factures = Facture::where('type', $request->input('type', '3WM'))
                ->where('etat_id', $request->input('etat', 1))
                ->where('agent_bof_id', '!=', null)->paginate($nb, ['*'], 'page', $page);
        }



        return response()->json([
            'success' => false,
            'message' => "les factures",
            'data' => [
                'totalPages' => $factures->lastPage(),
                'factures' => $factures->items(),
            ],
        ]);
    }



    function rechercheBof(Request $request)
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        if ($role->id !== 2) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette ressource',
                'data' => []
            ], 403); // 403 accés refusé
        }


        $page = $request->query('page', 1);
        $nb = $request->query('nb', 10);

        $factures = Facture::where('number', 'LIKE', '%' . $request->input('search') . '%')
            ->orWhereHas('fournisseur', function ($query) use ($request) {
                $query->where('idFiscale', 'LIKE', '%' . $request->input('search') . '%');
            })
            ->paginate($nb, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'message' => "les factures",
            "data" => [
                'totalPages' => $factures->lastPage(),
                'factures' => $factures->items(),
            ]
        ]);
    }
}
