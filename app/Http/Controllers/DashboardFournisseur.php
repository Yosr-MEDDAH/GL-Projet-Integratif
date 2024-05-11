<?php

namespace App\Http\Controllers;

use App\Models\Facture;
use App\Models\Reclamation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class DashboardFournisseur extends Controller
{
    function DashboardFournisseur(Request $request)
    {

        $user = JWTAuth::user();
        $role = $user->role()->first();

        if ($role->id !== 3) {
            return response()->json([
                'success' => false,
                'message' => "vous n'avez pas l'autorisation !!",
                'data' => [],
            ]);
        }

        // config 
        $anneesFacture = [];
        $anneesFacture = Facture::where('fournisseur_id', $user->id)->pluck('created_at')->map(function ($date) {
            return Carbon::parse($date)->year;
        })->unique();

        
    }
}
