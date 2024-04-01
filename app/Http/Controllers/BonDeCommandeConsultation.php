<?php

namespace App\Http\Controllers;

use App\Models\BonDeCommande;
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
        if ($role->id === 3) {
            $purOrders = BonDeCommande::where('four_idFiscale', $user->idFiscale)->paginate(10, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'message' => "votre bons de commande",
                'data' => [
                    'totalPages' => $purOrders->lastPage(),
                    'bons de commande' => $purOrders->items(),
                ]
            ]);
        }


        // pour les agents bof qui sont capables d'ajouter des factures
        $totalPurOrders = BonDeCommande::paginate(10, ['*'], 'page', $page);
        return response()->json([
            'success' => true,
            'message' => "votre bons de commande",
            'data' => [
                'totalPages' => $totalPurOrders->lastPage(),
                'bons de commande' => $totalPurOrders->items(),
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
                'data' => $numbers,
            ]);
        }


        // si tu veux un tableau contient seulement les nombres
        $purOrdersNumbers = BonDeCommande::all('num_commande');
        foreach ($purOrdersNumbers as $purOrderNumber) {
            $numbers[] = $purOrderNumber->num_commande;
        }

        return response()->json([
            'success' => true,
            'message' => "votre bons de commande",
            /*'data' => [
                'bons de commande' => BonDeCommande::all('num_commande'),
            ]*/
            'data' => $numbers,
        ]);
    }


    
}
