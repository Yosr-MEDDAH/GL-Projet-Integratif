<?php

namespace App\Http\Controllers;

use App\Models\Facture;
use App\Models\TypesFactures;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class ValidationFactureController extends Controller
{
    function invoicesToValidate(Request $request)
    {

        $user = JWTAuth::user();
        $role = $user->role()->first();

        if ($role->id === 3) {
            return response()->json([
                'success' => false,
                'message' => "vous n'avez pas autorisé",
                'data' => [],
            ]);
        }

        $page = $request->query('page', 1);
        $nb = $request->query('nb', 10);


        //agent bof
        if ($role->id === 2) {
            $factures = Facture::select('id', 'number', 'billing_date', 'created_at', 'updated_at', 'etat_id', 'type_facture_id', 'fournisseur_id', 'agent_bof_id', 'created_by', 'validePar', 'payment_period')
                ->where('etat_id', 1);
            if ($request->input('numero') || $request->input('idFiscale') || $request->input('type') || $request->input('jours')) {
                $factures->where('number', 'LIKE', '%' . $request->input('numero') . '%');

                if ($request->input('type')) {
                    $factures->where('type_facture_id', $request->input('type'));
                }

                if ($request->input('idFiscale')) {
                    $factures->whereHas('fournisseur', function ($query) use ($request) {
                        $query->where('idFiscale', 'LIKE', '%' . $request->input('idFiscale') . '%');
                    });
                }

                if ($request->input('jours')) {
                    $factures->whereRaw('payment_period - DATEDIFF(NOW(), created_at)-1 <= ?', [$request->input('jours')]);
                }
            }
            $factures = $factures->paginate($nb, ['*'], 'page', $page);
            foreach ($factures as $facture) {
                $typeFacture = $facture->typeFacture()->first();
                if ($typeFacture === null || $typeFacture->typeName === null) {
                    $facture->typeFacture = null;
                } else {
                    $facture->typeFacture = $typeFacture;
                }

                $etat = $facture->etat()->first();

                if ($etat === null || $etat->name_etat === null) {
                    $facture->etat = null;
                } else {
                    $facture->etat = $etat;
                }

                if ($facture->fournisseur_id !== null) {
                    $user = User::select('role_id', 'name', 'idFiscale')->where('id', $facture->fournisseur_id)->first();
                    $facture->createdBy = $user;
                } else {
                    $user = User::select('role_id', 'name', 'idFiscale')->where('id', $facture->agent_bof_id)->first();
                    $facture->createdBy = $user;
                }

                $periodePaiement = intval(preg_replace('/[^0-9]/', '', $facture->payment_period));

                if ($periodePaiement === 0) {
                    $periodePaiement = 60;
                }
                $dateCreation = Carbon::parse($facture->created_at);
                $dateLimitePaiement = $dateCreation->addDays($periodePaiement);
                $joursRestants = $dateLimitePaiement->diffInDays(Carbon::now());
                $joursÉcoulés = Carbon::now()->diffInDays($dateCreation);
                $pourcentageJoursRestants = round(($joursÉcoulés / $periodePaiement) * 100, 2);
                $pourcentageJoursPassés = round(100 - $pourcentageJoursRestants, 2);
                $facture->progress = [
                    'joursRestantsPourPaiement' => $joursRestants,
                    'pourcentageJoursPassés' => $pourcentageJoursPassés,
                    'pourcentageJoursRestants' => $pourcentageJoursRestants
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'les factures à valider',
                'data' => [
                    'totalPages' => $factures->lastPage(),
                    'factures' => $factures->items(),
                ],
            ]);
        }




        //agent Ap
        if ($role->id === 4) {
            $factures = Facture::select('id', 'number', 'billing_date', 'created_at', 'updated_at', 'etat_id', 'type_facture_id', 'fournisseur_id', 'agent_bof_id', 'created_by', 'validePar', 'payment_period')
                ->where('etat_id', 2)
                ->where('validePar', 'Agent Bof')
                ->whereIn('type_facture_id', $user->type_facture_ids);


            if ($request->input('numero') || $request->input('idFiscale') || $request->input('type') || $request->input('jours')) {
                $factures->where('number', 'LIKE', '%' . $request->input('numero') . '%');

                if ($request->input('type')) {
                    $factures->where('type_facture_id', $request->input('type'));
                }

                if ($request->input('idFiscale')) {
                    $factures->whereHas('fournisseur', function ($query) use ($request) {
                        $query->where('idFiscale', 'LIKE', '%' . $request->input('idFiscale') . '%');
                    });
                }

                if ($request->input('jours')) {
                    $factures->whereRaw('payment_period - DATEDIFF(NOW(), created_at)-1 <= ?', [$request->input('jours')]);
                }
            }
            $factures = $factures->paginate($nb, ['*'], 'page', $page);
            foreach ($factures as $facture) {
                $typeFacture = $facture->typeFacture()->first();
                if ($typeFacture === null || $typeFacture->typeName === null) {
                    $facture->typeFacture = null;
                } else {
                    $facture->typeFacture = $typeFacture;
                }

                $etat = $facture->etat()->first();

                if ($etat === null || $etat->name_etat === null) {
                    $facture->etat = null;
                } else {
                    $facture->etat = $etat;
                }

                if ($facture->fournisseur_id !== null) {
                    $user = User::select('role_id', 'name', 'idFiscale')->where('id', $facture->fournisseur_id)->first();
                    $facture->createdBy = $user;
                } else {
                    $user = User::select('role_id', 'name', 'idFiscale')->where('id', $facture->agent_bof_id)->first();
                    $facture->createdBy = $user;
                }

                $periodePaiement = intval(preg_replace('/[^0-9]/', '', $facture->payment_period));

                if ($periodePaiement === 0) {
                    $periodePaiement = 60;
                }
                $dateCreation = Carbon::parse($facture->created_at);
                $dateLimitePaiement = $dateCreation->addDays($periodePaiement);
                $joursRestants = $dateLimitePaiement->diffInDays(Carbon::now());
                $joursÉcoulés = Carbon::now()->diffInDays($dateCreation);
                $pourcentageJoursRestants = round(($joursÉcoulés / $periodePaiement) * 100, 2);
                $pourcentageJoursPassés = round(100 - $pourcentageJoursRestants, 2);
                $facture->progress = [
                    'joursRestantsPourPaiement' => $joursRestants,
                    'pourcentageJoursPassés' => $pourcentageJoursPassés,
                    'pourcentageJoursRestants' => $pourcentageJoursRestants
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'les factures à valider',
                'data' => [
                    'totalPages' => $factures->lastPage(),
                    'factures' => $factures->items(),
                ],
            ]);
        }


        // Agent Fiscaliste 
        if ($role->id === 5) {
            $factures = Facture::select('id', 'number', 'billing_date', 'created_at', 'updated_at', 'etat_id', 'type_facture_id', 'fournisseur_id', 'agent_bof_id', 'created_by', 'validePar', 'payment_period')
                ->where('etat_id', 2)
                ->where('validePar', 'Agent Ap')
                ->whereIn('type_facture_id', $user->type_facture_ids);


            if ($request->input('numero') || $request->input('idFiscale') || $request->input('type') || $request->input('jours')) {
                $factures->where('number', 'LIKE', '%' . $request->input('numero') . '%');

                if ($request->input('type')) {
                    $factures->where('type_facture_id', $request->input('type'));
                }

                if ($request->input('idFiscale')) {
                    $factures->whereHas('fournisseur', function ($query) use ($request) {
                        $query->where('idFiscale', 'LIKE', '%' . $request->input('idFiscale') . '%');
                    });
                }

                if ($request->input('jours')) {
                    $factures->whereRaw('payment_period - DATEDIFF(NOW(), created_at)-1 <= ?', [$request->input('jours')]);
                }
            }
            $factures = $factures->paginate($nb, ['*'], 'page', $page);
            foreach ($factures as $facture) {
                $typeFacture = $facture->typeFacture()->first();
                if ($typeFacture === null || $typeFacture->typeName === null) {
                    $facture->typeFacture = null;
                } else {
                    $facture->typeFacture = $typeFacture;
                }

                $etat = $facture->etat()->first();

                if ($etat === null || $etat->name_etat === null) {
                    $facture->etat = null;
                } else {
                    $facture->etat = $etat;
                }

                if ($facture->fournisseur_id !== null) {
                    $user = User::select('role_id', 'name', 'idFiscale')->where('id', $facture->fournisseur_id)->first();
                    $facture->createdBy = $user;
                } else {
                    $user = User::select('role_id', 'name', 'idFiscale')->where('id', $facture->agent_bof_id)->first();
                    $facture->createdBy = $user;
                }

                $periodePaiement = intval(preg_replace('/[^0-9]/', '', $facture->payment_period));

                if ($periodePaiement === 0) {
                    $periodePaiement = 60;
                }
                $dateCreation = Carbon::parse($facture->created_at);
                $dateLimitePaiement = $dateCreation->addDays($periodePaiement);
                $joursRestants = $dateLimitePaiement->diffInDays(Carbon::now());
                $joursÉcoulés = Carbon::now()->diffInDays($dateCreation);
                $pourcentageJoursRestants = round(($joursÉcoulés / $periodePaiement) * 100, 2);
                $pourcentageJoursPassés = round(100 - $pourcentageJoursRestants, 2);
                $facture->progress = [
                    'joursRestantsPourPaiement' => $joursRestants,
                    'pourcentageJoursPassés' => $pourcentageJoursPassés,
                    'pourcentageJoursRestants' => $pourcentageJoursRestants
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'les factures à valider',
                'data' => [
                    'totalPages' => $factures->lastPage(),
                    'factures' => $factures->items(),
                ],
            ]);
        }



        //agent trésorerie
        if ($role->id === 6) {
            $factures = Facture::select('id', 'number', 'billing_date', 'created_at', 'updated_at', 'etat_id', 'type_facture_id', 'fournisseur_id', 'agent_bof_id', 'created_by', 'validePar', 'payment_period')
                ->where('etat_id', 2)
                ->where('validePar', 'Agent Fiscaliste')
                ->whereIn('type_facture_id', $user->type_facture_ids);


            if ($request->input('numero') || $request->input('idFiscale') || $request->input('type') || $request->input('jours')) {
                $factures->where('number', 'LIKE', '%' . $request->input('numero') . '%');

                if ($request->input('type')) {
                    $factures->where('type_facture_id', $request->input('type'));
                }

                if ($request->input('idFiscale')) {
                    $factures->whereHas('fournisseur', function ($query) use ($request) {
                        $query->where('idFiscale', 'LIKE', '%' . $request->input('idFiscale') . '%');
                    });
                }

                if ($request->input('jours')) {
                    $factures->whereRaw('payment_period - DATEDIFF(NOW(), created_at)-1 <= ?', [$request->input('jours')]);
                }
            }
            $factures = $factures->paginate($nb, ['*'], 'page', $page);
            foreach ($factures as $facture) {
                $typeFacture = $facture->typeFacture()->first();
                if ($typeFacture === null || $typeFacture->typeName === null) {
                    $facture->typeFacture = null;
                } else {
                    $facture->typeFacture = $typeFacture;
                }

                $etat = $facture->etat()->first();

                if ($etat === null || $etat->name_etat === null) {
                    $facture->etat = null;
                } else {
                    $facture->etat = $etat;
                }

                if ($facture->fournisseur_id !== null) {
                    $user = User::select('role_id', 'name', 'idFiscale')->where('id', $facture->fournisseur_id)->first();
                    $facture->createdBy = $user;
                } else {
                    $user = User::select('role_id', 'name', 'idFiscale')->where('id', $facture->agent_bof_id)->first();
                    $facture->createdBy = $user;
                }

                $periodePaiement = intval(preg_replace('/[^0-9]/', '', $facture->payment_period));

                if ($periodePaiement === 0) {
                    $periodePaiement = 60;
                }
                $dateCreation = Carbon::parse($facture->created_at);
                $dateLimitePaiement = $dateCreation->addDays($periodePaiement);
                $joursRestants = $dateLimitePaiement->diffInDays(Carbon::now());
                $joursÉcoulés = Carbon::now()->diffInDays($dateCreation);
                $pourcentageJoursRestants = round(($joursÉcoulés / $periodePaiement) * 100, 2);
                $pourcentageJoursPassés = round(100 - $pourcentageJoursRestants, 2);
                $facture->progress = [
                    'joursRestantsPourPaiement' => $joursRestants,
                    'pourcentageJoursPassés' => $pourcentageJoursPassés,
                    'pourcentageJoursRestants' => $pourcentageJoursRestants
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'les factures à valider',
                'data' => [
                    'totalPages' => $factures->lastPage(),
                    'factures' => $factures->items(),
                ],
            ]);
        }
    }
}
