<?php

namespace App\Http\Controllers;

use App\Models\Facture;
use App\Models\Reclamation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class DashboardPersonnelDCF extends Controller
{
    function DashboardPersonnelDCF(Request $request)
    {

        $user = JWTAuth::user();
        $role = $user->role()->first();

        if ($role->id === 3) {
            return response()->json([
                'success' => false,
                'message' => "vous n'avez pas l'autorisation",
                'data' => [],
            ], 403);
        }

        // Config 
        $anneesFacture = [];
        $anneesFacture = Facture::pluck('created_at')->map(function ($date) {
            return Carbon::parse($date)->year;
        })->unique();

        $anneesReclamation = [];
        $anneesReclamation = Reclamation::pluck('created_at')->map(function ($date) {
            return Carbon::parse($date)->year;
        })->unique();
        return response()->json([
            "anneesReclamation" => $anneesReclamation,
            "anneesFacture" => $anneesFacture
        ]);
    }
}
