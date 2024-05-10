<?php

namespace App\Http\Controllers;

use App\Models\Etapes;
use App\Models\Facture;
use App\Models\FournisseursSansCompte;
use App\Models\Reclamation;
use App\Models\TypesFactures;
use App\Models\User;
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





        //InvoiceDonutData -- tous
        $facturesQuery = Facture::select();

        if ($request->input("dateDonut")) {
            $facturesQuery->whereDate('created_at', $request->input('dateDonut'));
        }

        $factures = $facturesQuery->get();
        $labels = [];
        foreach ($factures as $facture) {
            $type = $facture->typeFacture()->first();
            $typeName = $type->typeName;
            if (!in_array($typeName, $labels)) {
                $labels[] = $typeName;
            }
        }
        $values = [];
        foreach ($labels as $label) {
            $typeId = TypesFactures::select('id')->where('typeName', $label)->first();
            $factureQuery = Facture::where('type_facture_id', $typeId->id);
            if ($request->input("donutStatus")) {
                if ($request->input("donutStatus") === "4") {
                    $factureQuery->where('etat_id', 2)->where('validePar', '!=', 'Agent Trésorerie');
                } elseif ($request->input("donutStatus") === "2") {
                    $factureQuery->where('etat_id', 2)->where('validePar', 'Agent Trésorerie');
                } else {
                    $factureQuery->where('etat_id', $request->input("donutStatus"));
                }
            }
            $numberFacType = $factureQuery->count();
            $values[] = $numberFacType;
        }





        //RecentInvoices 
        $recentFactures = Facture::select('number', 'etat_id')->orderBy('updated_at', 'desc')
            ->take(4)
            ->get();
        foreach ($recentFactures as $facture) {
            if ($facture->etat_id === 2 && $facture->validePar !== "Agent Trésorerie") {
                $facture->etat_id = 4;
            }
        }





        // TopAgents
        if ($request->input('anneeTopAgent')) {
            $agentsIDs = Etapes::select("traitParId")->where('traitParRoleNom', $role->name)->whereYear('created_at', $request->input('anneeTopAgent'))->get();
        } else {
            $agentsIDs = Etapes::select("traitParId")->where('traitParRoleNom', $role->name)->get();
        }
        $ids = [];
        foreach ($agentsIDs as $agentId) {
            if (!in_array($agentId->traitParId, $ids)) {
                $ids[] =  $agentId->traitParId;
            }
        }
        $agents = [];
        foreach ($ids as $id) {
            if ($request->input('anneeTopAgent')) {
                $facTraitees = Etapes::where('traitParId', $id)->whereYear('created_at', $request->input('anneeTopAgent'))->count();
            } else {
                $facTraitees = Etapes::where('traitParId', $id)->count();
            }
            $agent = User::select('name', 'email')->where("id", $id)->first();
            $agent->nbFacTraitees = $facTraitees;
            $agents[] = $agent;
        }



        // Agent Bof et RecentReclamations  
        if ($role->id === 2) {
            //RecentReclamations 
            $recentRecalamation = Reclamation::select('title', 'etat', 'idFiscale')->orderBy('updated_at', 'desc')
                ->take(4)
                ->get();

            //fournisseurs totale
            $nbFourTotaleAvecCompte = User::where('role_id', 3)->count();
            $nbFourTotaleSansCompte = FournisseursSansCompte::count();
            $nombrefacturesAtraiter = Facture::where('validePar', null)->count();

            return response()->json([
                'success' => true,
                'message' => "Dashbord Personnel DCF",
                "data" => [
                    "config" => [
                        "anneesFacture" => $anneesFacture,
                        "anneesReclamation" => $anneesReclamation,
                    ],
                    "dashboardData" => [
                        "nombreTotaleFac" => $nombreTotaleFac,
                        "montantTotalFacPayee" => $montantTotale,
                        "invoicesDonutData" => [
                            "labels" => $labels,
                            "values" => $values,
                        ],
                        "recentInvoices" => $recentFactures,
                        "topAgents" => $agents,
                        "recentReclamations" => $recentRecalamation,
                        "nombrefacturesAtraiter" => $nombrefacturesAtraiter,
                        "fournisseur" => [
                            "nbFournisseurSansCompte" => $nbFourTotaleSansCompte,
                            "nbFournisseurAvecCompte" => $nbFourTotaleAvecCompte,
                        ]
                    ]
                ]
            ]);
        }
        if ($role->id === 4) {
            $nombrefacturesAtraiter = Facture::where('validePar', "Agent Bof")->count();
        }

        if ($role->id === 5) {
            $nombrefacturesAtraiter = Facture::where('validePar', "Agent Ap")->count();
        }

        if ($role->id === 6) {
            $nombrefacturesAtraiter = Facture::where('validePar', "Agent Fiscaliste")->count();
        }


        return response()->json([
            'success' => true,
            'message' => "Dashbord Personnel DCF",
            "data" => [
                "config" => [
                    "anneesFacture" => $anneesFacture,
                    "anneesReclamation" => $anneesReclamation,
                ],
                "dashboardData" => [
                    "nombreTotaleFac" => $nombreTotaleFac,
                    "montantTotalFacPayee" => $montantTotale,
                    "invoicesDonutData" => [
                        "labels" => $labels,
                        "values" => $values,
                    ],
                    "recentInvoices" => $recentFactures,
                    "topAgents" => $agents,
                    "recentReclamations" => null,
                    "nombrefacturesAtraiter" => $nombrefacturesAtraiter,
                    "fournisseur" => [
                        "nbFournisseurSansCompte" => null,
                        "nbFournisseurAvecCompte" => null,
                    ]
                ]
            ]
        ]);
    }
}
