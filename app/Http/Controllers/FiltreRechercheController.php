<?php

namespace App\Http\Controllers;

use App\Models\BonDeCommande;
use App\Models\Facture;
use App\Models\Reclamation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class FiltreRechercheController extends Controller
{

    //filtrage bon de commande
    function filtragePoFournisseur(Request $request)
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        $page = $request->query('page', 1);
        $nb = $request->query('nb', 10);
        if ($role->id === 3) {
            if ($request->input('FacturePresente', 'tous') === 'possède') {
                $purOrders = BonDeCommande::where('hasInvoice', 1)->where('four_idFiscale', $user->idFiscale)->paginate($nb, ['*'], 'page', $page);
            } elseif ($request->input('FacturePresente', 'tous') === 'non-possède') {
                $purOrders = BonDeCommande::where('hasInvoice', 0)->where('four_idFiscale', $user->idFiscale)->paginate($nb, ['*'], 'page', $page);
            } elseif ($request->input('FacturePresente', 'tous')) {
                $purOrders = BonDeCommande::where('four_idFiscale', $user->idFiscale)->paginate($nb, ['*'], 'page', $page);
            }
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






    function filtragePoBof(Request $request)
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


        if ($request->input('FacturePresente', 'tous') === 'possède') {
            $purOrders = BonDeCommande::where('hasInvoice', 1)->paginate($nb, ['*'], 'page', $page);
        } elseif ($request->input('FacturePresente', 'tous') === 'non-possède') {
            $purOrders = BonDeCommande::where('hasInvoice', 0)->paginate($nb, ['*'], 'page', $page);
        } elseif ($request->input('FacturePresente', 'tous')) {
            $purOrders = BonDeCommande::paginate($nb, ['*'], 'page', $page);
        }
        return response()->json([
            'success' => true,
            'message' => 'voici vos bons de commandes',
            'data' => [
                'totalPages' => $purOrders->lastPage(),
                'purOrders' => $purOrders->items(),
            ]
        ]);
    }










    function recherchePoFournisseur(Request $request)
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        $page = $request->query('page', 1);
        $nb = $request->query('nb', 10);
        if ($role->id === 3) {
            $purOrders = BonDeCommande::where('num_commande', 'LIKE', '%' . $request->input('search') . '%')
                ->where('four_idFiscale', $user->idFiscale)->paginate($nb, ['*'], 'page', $page);
            if (!$purOrders) {
                return response()->json([
                    'success' => false,
                    'message' => "ce bon de commande n'existe pas",
                    'data' => [],
                ]);
            }
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







    function recherchePoBof(Request $request)
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

        // $purOrders = BonDeCommande::where('num_commande', 'LIKE', '%' . $request->input('search') . '%')->orWhere('four_idFiscale', $request->input('search'))->paginate($nb, ['*'], 'page', $page);
        if ($request->input('FacturePresente', 'tous') === 'possède') {
            $purOrders = BonDeCommande::where('num_commande', 'LIKE', '%' . $request->input('search') . '%')->orWhere('four_idFiscale', $request->input('search'))
                ->where('hasInvoice', 1)->paginate($nb, ['*'], 'page', $page);
        } elseif ($request->input('FacturePresente', 'tous') === 'non-possède') {
            $purOrders = BonDeCommande::where('num_commande', 'LIKE', '%' . $request->input('search') . '%')->orWhere('four_idFiscale', $request->input('search'))
                ->where('hasInvoice', 0)->paginate($nb, ['*'], 'page', $page);
        } elseif ($request->input('FacturePresente', 'tous')) {
            $purOrders = BonDeCommande::where('num_commande', 'LIKE', '%' . $request->input('search') . '%')->orWhere('four_idFiscale', $request->input('search'))
                ->paginate($nb, ['*'], 'page', $page);
        }
        return response()->json([
            'success' => true,
            'message' => 'voici vos bons de commandes',
            'data' => [
                'totalPages' => $purOrders->lastPage(),
                'purOrders' => $purOrders->items(),
            ]
        ]);
    }







    //filtrage facture fournisseur

    function filtrageFactureFournisseur(Request $request)
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        if ($role->id !== 3) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette ressource',
                'data' => []
            ], 403); // 403 accés refusé
        }

        /*$messages = [
            'type_facture.required' => 'Le type de facture est requis.',
            'etat.required' => 'L\'état de la facture est requis.',
        ];


        $validator = Validator::make($request->all(), [
            'type_facture' => 'string',
            'etat' => 'numeric',
        ], $messages);


        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }*/

        $page = $request->query('page', 1);
        $nb = $request->query('nb', 10);

        $factures = Facture::where('etat_id', $request->input('etat', 1))->where('fournisseur_id', $user->id)->paginate($nb, ['*'], 'page', $page);// obligatoire etat envoyé avec request (car ca donne toujeours les factures en attente)
        return response()->json([
            'success' => false,
            'message' => "les factures",
            'data' => [
                'totalPages' => $factures->lastPage(),
                'factures' => $factures->items(),
            ],
        ]);
    }











    function rechercheFactureFournisseur(Request $request)
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        $page = $request->query('page', 1);
        $nb = $request->query('nb', 10);
        if ($role->id === 3) {
            $factures = Facture::where('number', 'LIKE', '%' . $request->input('search') . '%')
                ->where('fournisseur_id', $user->id)->paginate($nb, ['*'], 'page', $page);
            if (!$factures) {
                return response()->json([
                    'success' => false,
                    'message' => "cette facture n'existe pas",
                    'data' => [],
                ]);
            }
            foreach ($factures as $facture) {
                $facture->etat_name = $facture->etat()->first()->name_etat;
            }
            return response()->json([
                'success' => true,
                'message' => 'voici vos factures',
                'data' => [
                    'totalPages' => $factures->lastPage(),
                    'factures' => $factures->items(),
                ]
            ]);
        }
    }












    //filtrage reclamation

    function rechercheRec(Request $request)
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        $page = $request->query('page', 1);
        $nb = $request->query('nb', 10);
        if ($role->id === 3) {
            $reclamations = Reclamation::where('numFacture', 'LIKE', '%' . $request->input('numFacture') . '%')
                ->where('idFiscale', $user->idFiscale)->paginate($nb, ['*'], 'page', $page);
            if (!$reclamations) {
                return response()->json([
                    'success' => false,
                    'message' => "cette réclamtion n'existe pas",
                    'data' => [],
                ]);
            }
            /* foreach ($purOrders as $facture) {
                $facture->etat_name = $facture->etat()->first()->name_etat;
            }*/
            return response()->json([
                'success' => true,
                'message' => 'voici vos réclamtions',
                'data' => [
                    'totalPages' => $reclamations->lastPage(),
                    'réclamtions' => $reclamations->items(),
                ]
            ]);
        }

        //pour l'agent bof voir seulement les réclamation qui ne sont pas traitées
        $reclamations = Reclamation::where('numFacture', 'LIKE', '%' . $request->input('numFacture') . '%')
            ->where('etat', '=', "En Attente")->paginate($nb, ['*'], 'page', $page);
        /*foreach ($purOrders as $facture) {
            $facture->etat_name = $facture->etat()->first()->name_etat;
        }*/
        return response()->json([
            'success' => true,
            'message' => 'voici vos réclamtions',
            'data' => [
                'totalPages' => $reclamations->lastPage(),
                'réclamtions' => $reclamations->items(),
            ]
        ]);
    }






    function filtrageFactureBof(Request $request)
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











    function rechercheFactureBof(Request $request)
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


        if ($request->input('cree_par', "tous") === "tous") {
            $factures =  Facture::where('number', 'LIKE', '%' . $request->input('search') . '%')
                ->orWhereHas('fournisseur', function ($query) use ($request) {
                    $query->where('idFiscale', 'LIKE', '%' . $request->input('search') . '%');
                })
                ->where('type', $request->input('type', '3WM'))
                ->where('etat_id', $request->input('etat', 1))->paginate($nb, ['*'], 'page', $page);
        } elseif ($request->input('cree_par', "tous") === "moi") {
            $factures =  Facture::where('number', 'LIKE', '%' . $request->input('search') . '%')
                ->where('type', $request->input('type', '3WM'))
                ->where('etat_id', $request->input('etat', 1))
                ->where('agent_bof_id', $user->id)->paginate($nb, ['*'], 'page', $page);
        } elseif ($request->input('cree_par', "tous") === "fournisseur") {
            $factures =  Facture::where('number', 'LIKE', '%' . $request->input('search') . '%')
                ->orWhereHas('fournisseur', function ($query) use ($request) {
                    $query->where('idFiscale', 'LIKE', '%' . $request->input('search') . '%');
                })
                ->where('type', $request->input('type', '3WM'))
                ->where('etat_id', $request->input('etat', 1))
                ->where('fournisseur_id', '!=', null)->paginate($nb, ['*'], 'page', $page);
        } elseif ($request->input('cree_par', "tous") === "agent bof") {
            $factures =  Facture::where('number', 'LIKE', '%' . $request->input('search') . '%')
                ->where('type', $request->input('type', '3WM'))
                ->where('etat_id', $request->input('etat', 1))
                ->where('agent_bof_id', '!=', null)->paginate($nb, ['*'], 'page', $page);
        }

        /*$factures = Facture::where('number', 'LIKE', '%' . "FAC3" . '%')
            ->orWhereHas('fournisseur', function ($query) use ($request) {
                $query->where('idFiscale', 'LIKE', '%' . $request->input('search') . '%');
            })
            ->paginate($nb, ['*'], 'page', $page);*/

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
