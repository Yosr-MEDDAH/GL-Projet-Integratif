<?php

namespace App\Http\Controllers;

use App\Models\BonDeCommande;
use App\Models\User;
use Database\Seeders\BonDeCommandeSeeder;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class BonDeCommandeConsultation extends Controller
{
    function getAllPo(Request $request)
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        if ($role->id !== 2 && $role->id !== 3) {
            return response()->json([
                'success' => false,
                'message' => "vous avez pas l'autorisation",
                'data' => [],
            ]);
        }

        $page = $request->query('page', 1);
        $nb = $request->query('nb', 10);
        if ($role->id === 3) {
            $purOrders = BonDeCommande::where('four_idFiscale', $user->idFiscale)->orderBy('created_at', 'desc')->paginate($nb, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'message' => "votre bons de commande",
                'data' => [
                    'totalPages' => $purOrders->lastPage(),
                    'bons_de_commande' => $purOrders->items(),
                ]
            ]);
        }


        // pour les agents bof qui sont capables d'ajouter des factures
        $totalPurOrders = BonDeCommande::orderBy('created_at', 'desc')->paginate($nb, ['*'], 'page', $page);
        return response()->json([
            'success' => true,
            'message' => "votre bons de commande",
            'data' => [
                'totalPages' => $totalPurOrders->lastPage(),
                'bons_de_commande' => $totalPurOrders->items(),
            ]
        ]);
    }









    function getAllPoNumbers(Request $request)
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        if ($role->id !== 2 && $role->id !== 3) {
            return response()->json([
                'success' => false,
                'message' => "vous avez pas l'autorisation",
                'data' => [],
            ]);
        }

        $numbers = [];

        if ($role->id === 3) {
            $purOrdersNumbers = BonDeCommande::select('num_commande')->where('four_idFiscale', $user->idFiscale)->get();
            // si tu veux un tableau contient seulement les nombres du bon de  commande
            foreach ($purOrdersNumbers as $purOrderNumber) {
                $numbers[] = $purOrderNumber->num_commande;
            }
            return response()->json([
                'success' => true,
                'message' => "votre bons de commande",
                /*'data' => [
                    'bons de commande' => $purOrdersNumbers, 
                ]*/
                'data' => [
                    "numbers" => $numbers
                ],
            ]);
        }


        // pour agent bof 
        // si tu veux un tableau contient seulement les nombres
        $purOrdersNumbers = BonDeCommande::select('num_commande')->get();
        foreach ($purOrdersNumbers as $purOrderNumber) {
            $numbers[] = $purOrderNumber->num_commande;
        }

        return response()->json([
            'success' => true,
            'message' => "votre bons de commande",
            /*'data' => [
                'bons de commande' => BonDeCommande::all('num_commande'),
            ]*/
            'data' => [
                "numbers" => $numbers
            ],
        ]);
    }






    function getPo(Request $request)
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        if ($role->id !== 2 && $role->id !== 3) {
            return response()->json([
                'success' => false,
                'message' => "vous n' avez pas l'autorisation",
                'data' => [],
            ]);
        }

        $purOrder = BonDeCommande::where('num_commande', $request->input('num_commande'))->first();
        if (!$purOrder) {
            return response()->json([
                'success' => false,
                'message' => "bon de commande n'existe pas",
                'data' => [],
            ]);
        }

        if ($role->id === 3 && $user->idFiscale !== $purOrder->four_idFiscale) {
            return response()->json([
                'success' => false,
                'message' => "pas d'autorisation",
                'data' => [],
            ]);
        } else {
            $purOrder->fournnisseurName = User::where('idFiscale', $purOrder->four_idFiscale)->first()->name;
            return response()->json([
                'success' => true,
                'message' => "votre bon de commande",
                'data' => [
                    "bon_de_commande" => $purOrder,
                ]
            ]);
        }

        // pour agent bof 
        $purOrder->fournnisseurName = User::where('idFiscale', $purOrder->four_idFiscale)->first()->name;
        return response()->json([
            'success' => true,
            'message' => "votre bon de commande",
            'data' => [
                "bon_de_commande" => $purOrder,
            ]
        ]);
    }


    function recherchePo(Request $request)
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        $page = $request->query('page', 1);
        $nb = $request->query('nb', 10);
        if ($role->id === 3) {
            $purOrders = BonDeCommande::where('num_commande', 'LIKE', '%' . $request->input('num_commande') . '%')
                ->where('four_idFiscale', $user->idDiscale)->paginate($nb, ['*'], 'page', $page);
            if (!$purOrders) {
                return response()->json([
                    'success' => false,
                    'message' => "ce bon de commande n'existe pas",
                    'data' => [],
                ]);
            }
            /* foreach ($purOrders as $facture) {
                $facture->etat_name = $facture->etat()->first()->name_etat;
            }*/
            return response()->json([
                'success' => true,
                'message' => 'voici vos bons de commandes',
                'data' => [
                    'totalPages' => $purOrders->lastPage(),
                    'purOrders' => $purOrders->items(),
                ]
            ]);
        }

        //pour l'agent bof
        $purOrders = BonDeCommande::where('num_commande', 'LIKE', '%' . $request->input('num_commande') . '%')->paginate($nb, ['*'], 'page', $page);
        /*foreach ($purOrders as $facture) {
            $facture->etat_name = $facture->etat()->first()->name_etat;
        }*/
        return response()->json([
            'success' => true,
            'message' => 'voici vos bons de commandes',
            'data' => [
                'totalPages' => $purOrders->lastPage(),
                'purOrders' => $purOrders->items(),
            ]
        ]);
    }
}
