<?php

namespace App\Http\Controllers;

use App\Models\BonDeCommande;
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
        $anneesFacture = Facture::selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');



        //dashboardData

        //nombre de facture 

        if ($request->input('anneeFacture')) {
            $nombreTotaleFac = Facture::whereYear('created_at', $request->input('anneeFacture'))->where('fournisseur_id', $user->id)->count();
        } else {
            $nombreTotaleFac = Facture::where('fournisseur_id', $user->id)->count();
        }

        // payment total pour les factures validées

        $montantTotale = 0;
        if ($request->input('anneeFacture')) {
            $factures = Facture::whereYear('updated_at', $request->input('anneeFacture'))
                ->where('validePar', 'Agent Trésorerie')
                ->where('etat_id', 2)
                ->where('fournisseur_id', $user->id)
                ->get();
            foreach ($factures as $facture) {
                $montantTotale += $facture->amount;
            }
        } else {
            $factures = Facture::where('validePar', 'Agent Trésorerie')
                ->where('etat_id', 2)
                ->where('fournisseur_id', $user->id)
                ->get();
            foreach ($factures as $facture) {
                $montantTotale += $facture->amount;
            }
        }




        // payment total pour les factures en Attente

        $montantTotaleEnAttente = 0;
        if ($request->input('anneeFacture')) {
            $factures = Facture::whereYear('updated_at', $request->input('anneeFacture'))
                ->where(function ($query) use ($user) {
                    $query->where('etat_id', 1)
                        ->orWhere(function ($query) use ($user) {
                            $query->where('etat_id', 2)
                                ->where('validePar', '!=', 'Agent Trésorerie');
                        });
                })
                ->where('fournisseur_id', $user->id)
                ->get();
            $facturesNb = $factures->count();
            foreach ($factures as $facture) {
                $montantTotaleEnAttente += $facture->amount;
            }
        } else {
            $factures = Facture::where(function ($query) use ($user) {
                $query->where('etat_id', 1)
                    ->orWhere(function ($query) use ($user) {
                        $query->where('etat_id', 2)
                            ->where('validePar', '!=', 'Agent Trésorerie');
                    });
            })->where('fournisseur_id', $user->id)
                ->get();
            foreach ($factures as $facture) {
                $montantTotaleEnAttente += $facture->amount;
            }
        }



        // InvoiceDonutData 

        $labels = ["En Attente", "En cours", "Validées", "Refusées"];
        if ($request->input('anneeFacture')) {
            $values = [
                Facture::where('etat_id', 1)->where('fournisseur_id', $user->id)->whereYear('updated_at', $request->input('anneeFacture'))->count(),
                Facture::where('etat_id', 2)->where('validePar', '!=', 'Agent Trésorerie')->where('fournisseur_id', $user->id)->whereYear('updated_at', $request->input('anneeFacture'))->count(),
                Facture::where('etat_id', 2)->where('validePar', 'Agent Trésorerie')->where('fournisseur_id', $user->id)->whereYear('updated_at', $request->input('anneeFacture'))->count(),
                Facture::where('etat_id', 3)->where('fournisseur_id', $user->id)->whereYear('updated_at', $request->input('anneeFacture'))->count(),
            ];
        } else {
            $values = [
                Facture::where('etat_id', 1)->where('fournisseur_id', $user->id)->count(),
                Facture::where('etat_id', 2)->where('validePar', '!=', 'Agent Trésorerie')->where('fournisseur_id', $user->id)->count(),
                Facture::where('etat_id', 2)->where('validePar', 'Agent Trésorerie')->where('fournisseur_id', $user->id)->count(),
                Facture::where('etat_id', 3)->where('fournisseur_id', $user->id)->count(),
            ];
        }


        //RecentInvoices

        $recentFactures = Facture::select('number', 'etat_id', 'amount', 'updated_at', 'validePar')->where('fournisseur_id', $user->id)->orderBy('updated_at', 'desc')
            ->take(4)
            ->get();
        foreach ($recentFactures as $facture) {
            if ($facture->etat_id === 2 && $facture->validePar !== "Agent Trésorerie") {
                $facture->etat_id = 4;
            }
        }
        $recentFactures->makeHidden('validePar');

        // Recent po

        //labels
        $labelsPo = [];
        $purOrders = BonDeCommande::where('four_idFiscale', $user->idFiscale)->orderBy('updated_at', 'desc')
            ->take(5)
            ->get();
        foreach ($purOrders as $purOrder) {
            $labelsPo[] = $purOrder->num_commande;
        }

        // values
        $valuesPo = [];
        foreach ($labelsPo as $labelPo) {
            $poId = BonDeCommande::where('num_commande', $labelPo)->first()->id;
            $montantFacPoTotale = Facture::where('bon_de_commande_id', $poId)
                ->where('fournisseur_id', $user->id)
                ->sum('amount');
            $valuesPo[] = $montantFacPoTotale;
        }


        //ReclamationsData 

        $totaleReclamation = Reclamation::where('idFiscale',  $user->idFiscale)->count();
        $recalamtionRecue = Reclamation::where('etat',  'Recu')->where('idFiscale',  $user->idFiscale)->count();
        $recalamtionEnAttente = Reclamation::where('etat',  'En Attente')->where('idFiscale',  $user->idFiscale)->count();
        //RecentReclamations 
        $recentRecalamation = Reclamation::select('title', 'etat')->orderBy('updated_at', 'desc')
            ->where('idFiscale',  $user->idFiscale)
            ->take(4)
            ->get();
        foreach ($recentRecalamation as $reclamation) {
            if ($reclamation->etat === "Recu") {
                $reclamation->etat = "Reçue";
            }
        }







        return response()->json([
            'success' => true,
            'message' => 'Dashboard Fournisseur',
            'data' => [
                'config' => [
                    'anneesFacture' => $anneesFacture,
                ],
                'dashboardData' => [
                    'nombreTotaleFac' => $nombreTotaleFac,
                    'payments' => [
                        'montantTotale' => $montantTotale,
                        'montantTotaleEnAttente' => $montantTotaleEnAttente,
                    ],
                    'invoiceDonutData' => [
                        'labels' => $labels,
                        'values' => $values,
                    ],
                    'recentInvoices' => $recentFactures,
                    'recentPO' => [
                        'labelsPo' => $labelsPo,
                        'valuesPo' => $valuesPo,
                    ],
                    'reclamationsData' => [
                        'nbreclamationTotale' => $totaleReclamation,
                        'nbrecalamtionRecue' =>  $recalamtionRecue,
                        'nbrecalamtionEnAttente' => $recalamtionEnAttente,
                    ],
                    'recentReclamations' => $recentRecalamation,
                ]
            ]
        ]);
    }
}
