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

        // DashbordData ***

        //nombreTotaleFac
        if ($request->input('anneeFacture')) {
            $nombreTotaleFac = Facture::whereYear('created_at', $request->input('anneeFacture'))->count();
        } else {
            $nombreTotaleFac = Facture::count();
        }

        //totalFacMontantPayes
        $montantTotale = 0;
        if ($request->input('dateMontant')) {
            $factures = Facture::whereDate('updated_at', $request->input('dateMontant'))
                ->where('validePar', 'Agent Trésorerie')
                ->where('etat_id', 2)
                ->get();
            $facturesNb = $factures->count();
            foreach ($factures as $facture) {
                $montantTotale += $facture->amount;
            }
        } else {
            $factures = Facture::where('validePar', 'Agent Trésorerie')
                ->where('etat_id', 2)
                ->get();
            foreach ($factures as $facture) {
                $montantTotale += $facture->amount;
            }
        }
    }
}
