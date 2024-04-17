<?php

namespace App\Http\Controllers;

use App\Models\BonDeCommande;
use App\Models\Facture;
use App\Models\Fournisseur;
use App\Models\FournisseursSansCompte;
use App\Models\Reclamation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class FiltreRechercheController extends Controller
{

    function filtragePoFournisseur(Request $request) //done
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        $page = $request->query('page', 1);
        $nb = $request->query('nb', 10);
        if ($role->id === 3) {
            if ($request->input('hasInvoice') === 1) {
                $purOrders = BonDeCommande::where('hasInvoice', 1)->where('four_idFiscale', $user->idFiscale)->paginate($nb, ['*'], 'page', $page);
            } elseif ($request->input('hasInvoice') === 0) {
                $purOrders = BonDeCommande::where('hasInvoice', 0)->where('four_idFiscale', $user->idFiscale)->paginate($nb, ['*'], 'page', $page);
            } else {
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






    function filtragePoBof(Request $request) //done
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


        if ($request->input('hasInvoice') === 1) {
            $purOrders = BonDeCommande::where('hasInvoice', 1)->paginate($nb, ['*'], 'page', $page);
        } elseif ($request->input('hasInvoice') === 0) {
            $purOrders = BonDeCommande::where('hasInvoice', 0)->paginate($nb, ['*'], 'page', $page);
        } else {
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










    function recherchePoFournisseur(Request $request) //done
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        $page = $request->query('page', 1);
        $nb = $request->query('nb', 10);
        if ($role->id === 3) {
            if ($request->input('hasInvoice') === 1) {
                $purOrders = BonDeCommande::where('num_commande', 'LIKE', '%' . $request->input('search') . '%')
                    ->where('four_idFiscale', $user->idFiscale)->where('hasInvoice', 1)->paginate($nb, ['*'], 'page', $page);
            } elseif ($request->input('hasInvoice') === 0) {
                $purOrders = BonDeCommande::where('num_commande', 'LIKE', '%' . $request->input('search') . '%')
                    ->where('four_idFiscale', $user->idFiscale)->where('hasInvoice', 0)->paginate($nb, ['*'], 'page', $page);
            } else {
                $purOrders = BonDeCommande::where('num_commande', 'LIKE', '%' . $request->input('search') . '%')
                    ->where('four_idFiscale', $user->idFiscale)->paginate($nb, ['*'], 'page', $page);
            }
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







    function recherchePoBof(Request $request) //done
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
        if ($request->input('hasInvoice') === 1) {
            $purOrders = BonDeCommande::where(function ($query) use ($request) {
                $query->where('num_commande', 'LIKE', '%' . $request->input('search') . '%')
                    ->orWhere('four_idFiscale', $request->input('search'));
            })
                ->where('hasInvoice', 1)
                ->paginate($nb, ['*'], 'page', $page);
        } elseif ($request->input('hasInvoice') === 0) {
            $purOrders = BonDeCommande::where(function ($query) use ($request) {
                $query->where('num_commande', 'LIKE', '%' . $request->input('search') . '%')
                    ->orWhere('four_idFiscale', $request->input('search'));
            })
                ->where('hasInvoice', 0)
                ->paginate($nb, ['*'], 'page', $page);
        } else {
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

        $page = $request->query('page', 1);
        $nb = $request->query('nb', 10);

        if ($request->has('etat')) {
            $factures = Facture::where('etat_id', $request->input('etat'))->where('fournisseur_id', $user->id)->paginate($nb, ['*'], 'page', $page);
        } else {
            $factures = Facture::where('fournisseur_id', $user->id)->paginate($nb, ['*'], 'page', $page);
            // obligatoire etat envoyé avec request (car ca donne toujeours les factures en attente)
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







    function filtrageFactureBof(Request $request) //done (écriture incorrecte dans les params)
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

        if ($request->input('cree_par', "tous") === "moi") {
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
        } else {
            $factures = Facture::where('type', $request->input('type', '3WM'))
                ->where('etat_id', $request->input('etat', 1))->paginate($nb, ['*'], 'page', $page);
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












    function rechercheFactureFournisseur(Request $request) //done
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







    function rechercheFactureBof(Request $request) //done (exemple par 3)
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




    //filtrage reclamation

    function filtrageReclamationFournisseur(Request $request)
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        $page = $request->query('page', 1);
        $nb = $request->query('nb', 10);
        if ($role->id === 3) {
            if ($request->input('etat', 'tous') === 'En Attente') {
                $reclamations = Reclamation::where('etat', 'En Attente')->where('idFiscale', $user->idFiscale)->paginate($nb, ['*'], 'page', $page);
            } elseif ($request->input('etat', 'tous') === 'Recu') {
                $reclamations = Reclamation::where('etat', 'Recu')->where('idFiscale', $user->idFiscale)->paginate($nb, ['*'], 'page', $page);
            } elseif ($request->input('etat', 'tous') === 'tous') {
                $reclamations = Reclamation::where('idFiscale', $user->idFiscale)->paginate($nb, ['*'], 'page', $page);
            }
            return response()->json([
                'success' => true,
                'message' => 'voici vos bons de commandes',
                'data' => [
                    'totalPages' => $reclamations->lastPage(),
                    'reclamations' => $reclamations->items(),
                ]
            ]);
        }
    }




    function filtrageReclamationBof(Request $request)
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        $page = $request->query('page', 1);
        $nb = $request->query('nb', 10);
        if ($role->id === 2) {
            if ($request->input('etat', 'En Attente') === 'En Attente') {
                $reclamations = Reclamation::where('etat', 'En Attente')->paginate($nb, ['*'], 'page', $page);
            } elseif ($request->input('etat', 'En Attente') === 'Recu') {
                $reclamations = Reclamation::where('etat', 'Recu')->paginate($nb, ['*'], 'page', $page);
            } elseif ($request->input('etat', 'En Attente') === 'tous') {
                $reclamations = Reclamation::paginate($nb, ['*'], 'page', $page);
            }
            return response()->json([
                'success' => true,
                'message' => 'voici vos bons de commandes',
                'data' => [
                    'totalPages' => $reclamations->lastPage(),
                    'reclamations' => $reclamations->items(),
                ]
            ]);
        }
    }












    function rechercheReclamationFournisseur(Request $request) // done
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        $page = $request->query('page', 1);
        $nb = $request->query('nb', 10);
        if ($role->id === 3) {
            $reclamations = Reclamation::where('numFacture', 'LIKE', '%' . $request->input('search') . '%')
                ->where('idFiscale', $user->idFiscale)->paginate($nb, ['*'], 'page', $page);
            if (!$reclamations) {
                return response()->json([
                    'success' => false,
                    'message' => "cette réclamtion n'existe pas",
                    'data' => [],
                ]);
            }
            return response()->json([
                'success' => true,
                'message' => 'voici vos réclamtions',
                'data' => [
                    'totalPages' => $reclamations->lastPage(),
                    'reclamations' => $reclamations->items(),
                ]
            ]);
        }
    }









    function rechercheReclamationBof(Request $request)
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

        $reclamations = Reclamation::where('numFacture', 'LIKE', '%' . $request->input('search') . '%')
            ->orWhere('idFiscale', $request->input('search'))->paginate($nb, ['*'], 'page', $page);


        return response()->json([
            'success' => true,
            'message' => 'voici vos bons de commandes',
            'data' => [
                'totalPages' => $reclamations->lastPage(),
                'reclamations' => $reclamations->items(),
            ]
        ]);
    }



    function rechercheFournisseurSansCompte(Request $request) // done
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

        $fournisseurs = FournisseursSansCompte::where('idFiscale', 'LIKE', '%' . $request->input('search') . '%')
            ->paginate($nb, ['*'], 'page', $page);


        return response()->json([
            'success' => true,
            'message' => 'les fournisseurs sans compte',
            'data' => [
                'totalPages' => $fournisseurs->lastPage(),
                'fournisseurs' => $fournisseurs->items(),
            ]
        ]);
    }

    function rechercheFournisseurAvecCompte(Request $request) // done
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

        $fournisseurs = User::where('idFiscale', 'LIKE', '%' . $request->input('search') . '%')
            ->paginate($nb, ['*'], 'page', $page);


        return response()->json([
            'success' => true,
            'message' => 'les fournisseurs avec comptes:',
            'data' => [
                'totalPages' => $fournisseurs->lastPage(),
                'fournisseurs' => $fournisseurs->items(),
            ]
        ]);
    }
}
