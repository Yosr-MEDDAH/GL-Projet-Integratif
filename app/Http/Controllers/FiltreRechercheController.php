<?php

namespace App\Http\Controllers;

use App\Models\BonDeCommande;
use App\Models\Bordereau;
use App\Models\Facture;
use App\Models\Fournisseur;
use App\Models\FournisseursSansCompte;
use App\Models\Reclamation;
use App\Models\User;
use Carbon\Carbon;
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
            if ($request->input('hasInvoice') === "1") {
                $purOrders = BonDeCommande::where('num_commande', 'LIKE', '%' . $request->input('search') . '%')
                    ->where('four_idFiscale', $user->idFiscale)->where('hasInvoice', 1)->paginate($nb, ['*'], 'page', $page);
            } elseif ($request->input('hasInvoice') === "0") {
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
            foreach ($purOrders as $purOrder) {
                $nbFactures = Facture::where('bon_de_commande_id', $purOrder->id)->count();
                $purOrder->nbFactures = $nbFactures;
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

        /*if ($role->id !== 2) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette ressource',
                'data' => []
            ], 403); // 403 accés refusé
        }*/

        if ($role->id === 3) {
            return response()->json([
                'success' => false,
                'message' => "vous n'avez pas autorisé",
                'data' => [],
            ]);
        }

        $page = $request->query('page', 1);
        $nb = $request->query('nb', 10);
        if ($request->input('hasInvoice') === "1") {
            $purOrders = BonDeCommande::where(function ($query) use ($request) {
                $query->where('num_commande', 'LIKE', '%' . $request->input('search') . '%')
                    ->orWhere('four_idFiscale', $request->input('search'));
            })
                ->where('hasInvoice', 1)
                ->paginate($nb, ['*'], 'page', $page);
        } elseif ($request->input('hasInvoice') === "0") {
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
        foreach ($purOrders as $purOrder) {
            $nbFactures = Facture::where('bon_de_commande_id', $purOrder->id)->count();
            $purOrder->nbFactures = $nbFactures;
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
                $etat = $facture->etat()->first();
                if ($etat === null || $etat->name_etat === null) {
                    $facture->etat = null;
                } elseif ($etat->id === 2 && $facture->validePar !== "Agent Trésorerie") {
                    $facture->etat->id = 4;
                    $facture->etat->name_etat = "En Cours";
                } else {
                    $facture->etat = $etat;
                }
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

        /*if ($role->id !== 2) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette ressource',
                'data' => []
            ], 403); // 403 accés refusé
        }*/

        if ($role->id === 3) {
            return response()->json([
                'success' => false,
                'message' => "vous n'avez pas autorisé",
                'data' => [],
            ]);
        }


        $page = $request->query('page', 1);
        $nb = $request->query('nb', 10);


        /*if ($request->input('cree_par', "tous") === "tous") {
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
        }*/

        /*$factures = Facture::where('number', 'LIKE', '%' . "FAC3" . '%')
            ->orWhereHas('fournisseur', function ($query) use ($request) {
                $query->where('idFiscale', 'LIKE', '%' . $request->input('search') . '%');
            })
            ->paginate($nb, ['*'], 'page', $page);*/


        /* if ($request->input('cree_par') === "1") {
            $factures =  Facture::where('number', 'LIKE', '%' . $request->input('search') . '%')
                ->orWhereHas('fournisseur', function ($query) use ($request) {
                    $query->where('idFiscale', 'LIKE', '%' . $request->input('search') . '%');
                })
                ->where('type_facture_id', $request->input('type'))
                ->where('etat_id', $request->input('etat'))
                ->where('fournisseur_id', '!=', null)->paginate($nb, ['*'], 'page', $page);
        } elseif ($request->input('cree_par') === "2") {
            $factures =  Facture::where('number', 'LIKE', '%' . $request->input('search') . '%')
                ->where('type_facture_id', $request->input('type'))
                ->where('etat_id', $request->input('etat'))
                ->where('agent_bof_id', '!=', null)->paginate($nb, ['*'], 'page', $page);
        } elseif ($request->input('cree_par') === "3") {
            $factures =  Facture::where('number', 'LIKE', '%' . $request->input('search') . '%')
                ->where('type_facture_id', $request->input('type'))
                ->where('etat_id', $request->input('etat'))
                ->where('agent_bof_id', $user->id)->paginate($nb, ['*'], 'page', $page);
        } else {
            $factures =  Facture::where('number', 'LIKE', '%' . $request->input('search') . '%')
                ->orWhereHas('fournisseur', function ($query) use ($request) {
                    $query->where('idFiscale', 'LIKE', '%' . $request->input('search') . '%');
                })
                ->where('type_facture_id', $request->input('type'))
                ->where('etat_id', $request->input('etat'))->paginate($nb, ['*'], 'page', $page);
        }*/


        $factures = Facture::select('id', 'number', 'billing_date', 'created_at', 'updated_at', 'etat_id', 'type_facture_id', 'fournisseur_id', 'agent_bof_id', 'created_by', 'validePar', 'payment_period');

        $factures->where(function ($query) use ($request, $user) {
            if ($request->input('cree_par') === "1") {
                $query->where('fournisseur_id', '!=', null);
            } elseif ($request->input('cree_par') === "2") {
                $query->where('agent_bof_id', '!=', null);
            } elseif ($request->input('cree_par') === "3") {
                $query->where('agent_bof_id', $user->id);
            }

            if ($request->input('search')) {
                $query->where(function ($query) use ($request) {
                    $query->where('number', 'LIKE', '%' . $request->input('search') . '%')
                        ->orWhereHas('fournisseur', function ($query) use ($request) {
                            $query->where('idFiscale', 'LIKE', '%' . $request->input('search') . '%');
                        });
                });
            }

            if ($request->input('type_facture')) {
                $query->where('type_facture_id', $request->input('type_facture'));
            }

            if ($request->input('etat')) {
                $query->where('etat_id', $request->input('etat'));
            }
        });

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
            } elseif ($etat->id === 2 && $facture->validePar !== "Agent Trésorerie") {
                $facture->etat->id = 4;
                $facture->etat->name_etat = "En Cours";
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
        $nb = $request->query('nb', 5);
        if ($role->id === 3) {
            if ($request->input('etat') === "0") {
                $reclamations = Reclamation::where('title', 'LIKE', '%' . $request->input('search') . '%')
                    ->where('idFiscale', $user->idFiscale)
                    ->whereDate('created_at', 'LIKE', '%' . $request->input('date') . '%')
                    ->where('etat', 'En Attente')
                    ->orderBy('created_at', 'desc')
                    ->paginate($nb, ['*'], 'page', $page);
            } elseif ($request->input('etat') === "1") {
                $reclamations = Reclamation::where('title', 'LIKE', '%' . $request->input('search') . '%')
                    ->where('idFiscale', $user->idFiscale)
                    ->whereDate('created_at', 'LIKE', '%' . $request->input('date') . '%')
                    ->where('etat', 'Recu')
                    ->orderBy('created_at', 'desc')
                    ->paginate($nb, ['*'], 'page', $page);
            } else {
                $reclamations = Reclamation::where('title', 'LIKE', '%' . $request->input('search') . '%')
                    ->where('idFiscale', $user->idFiscale)
                    ->whereDate('created_at', 'LIKE', '%' . $request->input('date') . '%')
                    ->orderBy('created_at', 'desc')
                    ->paginate($nb, ['*'], 'page', $page);
                    foreach ($reclamations as $reclamation) {
                        if($reclamation->etat === "Recu"){
                            $reclamation->etat = "Reçue";
                        }
                    }
            }
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
                    "nombreReclamation" => $reclamations->count(),
                    'reclamations' => $reclamations->items(),
                ]
            ]);
        }
    }









    function rechercheReclamationBof(Request $request) // éliminer text, numcommande et facture
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

        if ($request->input('etat') === "0") {
            $reclamations = Reclamation::where(function ($query) use ($request) {
                $query->where('idFiscale', 'LIKE', '%' . $request->input('search') . '%')
                    ->orWhere('title', 'LIKE', '%' . $request->input('search') . '%');
            })
                ->whereDate('created_at', 'LIKE', '%' . $request->input('date') . '%')
                ->where('etat', 'En Attente')
                ->orderBy('created_at', 'desc')
                ->paginate($nb, ['*'], 'page', $page);
        } elseif ($request->input('etat') === "1") {
            $reclamations = Reclamation::where(function ($query) use ($request) {
                $query->where('idFiscale', 'LIKE', '%' . $request->input('search') . '%')
                    ->orWhere('title', 'LIKE', '%' . $request->input('search') . '%');
            })
                ->whereDate('created_at', 'LIKE', '%' . $request->input('date') . '%')
                ->where('etat', 'Recu')
                ->orderBy('created_at', 'desc')
                ->paginate($nb, ['*'], 'page', $page);
            foreach ($reclamations as $reclamation) {
                $reclamation->etat = "Reçue";
            }
        } else {
            $reclamations = Reclamation::where(function ($query) use ($request) {
                $query->where('idFiscale', 'LIKE', '%' . $request->input('search') . '%')
                    ->orWhere('title', 'LIKE', '%' . $request->input('search') . '%');
            })
                ->whereDate('created_at', 'LIKE', '%' . $request->input('date') . '%')
                ->orderBy('created_at', 'desc')
                ->paginate($nb, ['*'], 'page', $page);
        }

        foreach ($reclamations as $reclamation) {
            $nomFournisseur = User::where('idFiscale', $reclamation->idFiscale)->first()->name;
            $reclamation->nomFournisseur = $nomFournisseur;
        }

        return response()->json([
            'success' => true,
            'message' => 'voici vos réclamations',
            'data' => [
                'totalPages' => $reclamations->lastPage(),
                "nombreReclamation" => $reclamations->count(),
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



    function rechercheBordoreau(Request $request)
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        /*if ($role->id !== 2) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette ressource',
                'data' => []
            ], 403); // 403 accés refusé
        }*/

        if ($role->id === 3) {
            return response()->json([
                'success' => false,
                'message' => "vous n'avez pas autorisé",
                'data' => [],
            ]);
        }

        $page = $request->query('page', 1);
        $nb = $request->query('nb', 10);
        if ($request->input('search')) {
            $bordoreaux = Bordereau::whereDate('created_at', 'LIKE', '%' . $request->input('search') . '%')->paginate($nb, ['*'], 'page', $page);
            foreach ($bordoreaux as $bordoreau) {
                $factures = Facture::where("borderau_id", $bordoreau->id)->count();
                $bordoreau->nombreFacture = $factures;
            }
        } else {
            $bordoreaux = Bordereau::paginate($nb, ['*'], 'page', $page);
            foreach ($bordoreaux as $bordoreau) {
                $factures = Facture::where("borderau_id", $bordoreau->id)->count();
                $bordoreau->nombreFacture = $factures;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'les bordoreaux',
            'data' => [
                'totalPages' => $bordoreaux->lastPage(),
                'bordoreaux' => $bordoreaux->items(),
            ]
        ]);
    }

    function rechercheBordoreauListFacture(Request $request)
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        /*if ($role->id !== 2) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette ressource',
                'data' => []
            ], 403); // 403 accés refusé
        }*/

        if ($role->id === 3) {
            return response()->json([
                'success' => false,
                'message' => "vous n'avez pas autorisé",
                'data' => [],
            ]);
        }


        $page = $request->query('page', 1);
        $nb = $request->query('nb', 10);
        $listFacture = Facture::select('id', 'number', 'invoice_name', 'type_facture_id', 'invoice_file_path', 'fournisseur_id', 'agent_bof_id', 'created_by', 'bon_de_commande_id', 'etat_id')
            ->where('number', 'LIKE', '%' . $request->input('search') . '%')
            ->where('borderau_id', $request->input('id'))
            ->orderBy('created_at', 'desc')
            ->paginate($nb, ['*'], 'page', $page);
        foreach ($listFacture as $facture) {
            if ($facture->fournisseur_id !== null) {
                $user = User::select('name')->where('id', $facture->fournisseur_id)->first();
                $facture->nameCreatedBy = $user->name;
            } else {
                $user = User::select('name')->where('id', $facture->agent_bof_id)->first();
                $facture->nameCreatedBy = $user->name;
            }
            $etat = $facture->etat()->first();
            if ($etat === null || $etat->name_etat === null) {
                $facture->etat = null;
            } else {
                $facture->etat = $etat->name_etat;
            }
            /*$bonDeCommande = $facture->bonDeCommande()->first();
                if ($bonDeCommande === null || $bonDeCommande->num_commande === null) {
                    $facture->numBonCommande = null;
                } else {
                    $facture->numBonCommande = $bonDeCommande->num_commande;
                }*/
        }

        return response()->json([
            'success' => true,
            'message' => 'les factures',
            'data' => [
                'totalPages' => $listFacture->lastPage(),
                'factures' => $listFacture->items(),
            ]
        ]);
    }
}
