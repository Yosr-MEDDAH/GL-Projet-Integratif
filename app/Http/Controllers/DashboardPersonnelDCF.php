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
        $anneesFacture = Facture::selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        // $anneesFacture contiendra maintenant les années uniques présentes dans la colonne 'created_at' des factures, triées par ordre décroissant.

        // $anneesFacture contiendra maintenant uniquement les années uniques présentes dans la colonne 'created_at' des factures.

        // dd($anneesFacture);
        $anneesReclamation = [];
        $anneesReclamation = Reclamation::selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');






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
        $recentFactures = Facture::select('number', 'etat_id', 'validePar')->orderBy('updated_at', 'desc')
            ->take(4)
            ->get();
        foreach ($recentFactures as $facture) {
            if ($facture->etat_id === 2 && $facture->validePar !== "Agent Trésorerie") {
                $facture->etat_id = 4;
            }
        }
        $recentFactures->makeHidden('validePar');





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
        $otherAgents = User::select('name', 'email', 'role_id')->where('role_id', $role->id)
            ->whereNotIn('id', $ids)
            ->get();
        foreach ($otherAgents as $agent) {
            $agent->nbFacTraitees = 0;
            $agents[] = $agent;
            $agent->makeHidden(['role_id']);
        }
        $agents = collect($agents)->sortByDesc('nbFacTraitees')->values()->all();



        // Agent Bof et RecentReclamations  
        if ($role->id === 2) {
            //RecentReclamations 
            $recentRecalamation = Reclamation::select('title', 'etat', 'idFiscale')->orderBy('updated_at', 'desc')
                ->take(4)
                ->get();
            foreach ($recentRecalamation as $reclamation) {
                if ($reclamation->etat === "Recu") {
                    $reclamation->etat = "Reçue";
                }
            }
            // ReclamationsData 
            $totaleReclamation = Reclamation::count();
            $recalamtionRecue = Reclamation::where('etat',  'Recu')->count();
            $recalamtionEnAttente = Reclamation::where('etat',  'En Attente')->count();
            //fournisseurs totale
            $nbFourTotaleAvecCompte = User::where('role_id', 3)->count();
            $nbFourTotaleSansCompte = FournisseursSansCompte::count();
            $nombrefacturesAtraiter = Facture::where('validePar', null)->count();
            $nombrefacturesRejetes = Facture::where('etat_id', 3)->count();
            $nombrefacturesValidees = Facture::where('etat_id', 2)
                ->where('validePar', 'Agent Trésorerie')
                ->count();



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
                        "nombrefacturesRejetes" => $nombrefacturesRejetes,
                        "nombrefacturesValidees" => $nombrefacturesValidees,
                        "fournisseur" => [
                            "nbFournisseurSansCompte" => $nbFourTotaleSansCompte,
                            "nbFournisseurAvecCompte" => $nbFourTotaleAvecCompte,
                        ],
                        "reclamationsData" => [
                            'totaleReclamation' => $totaleReclamation,
                            'recalamtionEnAttente' => $recalamtionEnAttente,
                            'recalamtionRecue' => $recalamtionRecue,
                        ]
                    ],
                    "isDashboardLive" => $user->isRealTimeDashboardEnabled,
                ]
            ]);
        }
        if ($role->id === 4) {
            $nombrefacturesAtraiter = Facture::where('validePar', "Agent Bof")->count();
            $nombrefacturesRejetes = Facture::where('etat_id', 3)->where('validePar', 'Agent Ap')->count();
            $nombrefacturesValidees = Facture::where('etat_id', 2)
                ->where('validePar', 'Agent Ap')
                ->count();
        }

        if ($role->id === 5) {
            $nombrefacturesAtraiter = Facture::where('validePar', "Agent Ap")->count();
            $nombrefacturesRejetes = Facture::where('etat_id', 3)->where('validePar', 'Agent Fiscaliste')->count();
            $nombrefacturesValidees = Facture::where('etat_id', 2)
                ->where('validePar', 'Agent Fiscaliste')
                ->count();
        }

        if ($role->id === 6) {
            $nombrefacturesAtraiter = Facture::where('validePar', "Agent Fiscaliste")->count();
            $nombrefacturesRejetes = Facture::where('etat_id', 3)->where('validePar', 'Agent Trésorerie')->count();
            $nombrefacturesValidees = Facture::where('etat_id', 2)
                ->where('validePar', 'Agent Trésorerie')
                ->count();
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
                    "nombrefacturesRejetes" => $nombrefacturesRejetes,
                    "nombrefacturesValidees" => $nombrefacturesValidees,
                    "fournisseur" => [
                        "nbFournisseurSansCompte" => null,
                        "nbFournisseurAvecCompte" => null,
                    ],
                ],
                "isDashboardLive" => $user->isRealTimeDashboardEnabled,
            ]
        ]);
    }
}
