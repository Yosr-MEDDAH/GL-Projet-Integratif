<?php

namespace App\Http\Controllers;

use App\Models\BonDeCommande;
use App\Models\Etapes;
use App\Models\Facture;
use App\Models\MotifDeRejet;
use App\Models\Notification;
use App\Models\ObjetFacture;
use App\Models\PieceJointeFacture;
use App\Models\Role;
use App\Models\TypesFactures;
use App\Models\User;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Strategies\ValidationContext;

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
            $factures = Facture::select('id', 'number', 'billing_date', 'created_at', 'updated_at', 'etat_id', 'type_facture_id', 'fournisseur_id', 'agent_bof_id', 'amount', 'created_by', 'validePar', 'payment_period')
                ->where('etat_id', 1);
        }

        //agent Ap
        if ($role->id === 4) {
            $factures = Facture::select('id', 'number', 'billing_date', 'created_at', 'updated_at', 'etat_id', 'type_facture_id', 'fournisseur_id', 'agent_bof_id', 'amount', 'created_by', 'validePar', 'payment_period')
                ->where('etat_id', 2)
                ->where('validePar', 'Agent Bof')
                ->whereIn('type_facture_id', $user->type_facture_ids);
        }

        // Agent Fiscaliste 
        if ($role->id === 5) {
            $factures = Facture::select('id', 'number', 'billing_date', 'created_at', 'updated_at', 'etat_id', 'type_facture_id', 'fournisseur_id', 'agent_bof_id', 'amount', 'created_by', 'validePar', 'payment_period')
                ->where('etat_id', 2)
                ->where('validePar', 'Agent Ap')
                ->whereIn('type_facture_id', $user->type_facture_ids);
        }

        // Agent Trésorerie
        if ($role->id === 6) {
            $factures = Facture::select('id', 'number', 'billing_date', 'created_at', 'updated_at', 'etat_id', 'type_facture_id', 'fournisseur_id', 'agent_bof_id', 'amount', 'created_by', 'validePar', 'payment_period')
                ->where('etat_id', 2)
                ->where('validePar', 'Agent Fiscaliste')
                ->whereIn('type_facture_id', $user->type_facture_ids);
        }



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

            /*if ($request->input('jours')) {
                $factures->whereRaw('(payment_period - DATEDIFF(NOW(), created_at) - 1) <= ?', [$request->input('jours')]);
            }*/
            if ($request->input('jours')) {
                $factures->whereRaw('(payment_period - DATEDIFF(CURRENT_DATE(), DATE(created_at))) <= ?', [$request->input('jours')]);
            }
        }
        $factures = $factures->paginate($nb, ['*'], 'page', $page);


        /*$factures = Facture::select('id', 'number', 'billing_date', 'created_at', 'updated_at', 'etat_id', 'type_facture_id', 'fournisseur_id', 'agent_bof_id', 'amount', 'created_by', 'validePar', 'payment_period')
            ->where('etat_id', 2)
            ->where('validePar', 'Agent Fiscaliste')
            ->whereIn('type_facture_id', $user->type_facture_ids);

        if ($request->input('numero') || $request->input('idFiscale') || $request->input('type') || $request->input('jours')) {
            $factures->where(function ($query) use ($request) {
                if ($request->input('numero')) {
                    $query->where('number', 'LIKE', '%' . $request->input('numero') . '%');
                }

                if ($request->input('type')) {
                    $query->where('type_facture_id', $request->input('type'));
                }

                if ($request->input('idFiscale')) {
                    $query->whereHas('fournisseur', function ($query) use ($request) {
                        $query->where('idFiscale', 'LIKE', '%' . $request->input('idFiscale') . '%');
                    });
                }

                if ($request->input('jours')) {
                    $query->whereRaw('payment_period - DATEDIFF(NOW(), created_at)-1 <= ?', [$request->input('jours')]);
                }
            });
        }

        $factures = $factures->paginate($nb, ['*'], 'page', $page);*/


        foreach ($factures as $facture) {
            $typeFacture = $facture->typeFacture()->first();
            if ($typeFacture === null || $typeFacture->typeName === null) {
                $facture->typeFacture = null;
            } else {
                $facture->typeFacture = $typeFacture;
            }

            /*$etat = $facture->etat()->first();

            if ($etat === null || $etat->name_etat === null) {
                $facture->etat = null;
            } else {
                $facture->etat = $etat;
            }*/


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


            if ($facture->fournisseur_id !== null) {
                $user = User::select('role_id', 'name', 'idFiscale')->where('id', $facture->fournisseur_id)->first();
                $user->role_name = $facture->created_by;
                $facture->createdBy = $user;
            } else {
                $user = User::select('role_id', 'name', 'idFiscale')->where('id', $facture->agent_bof_id)->first();
                $facture->createdBy = $user;
            }

            $periodePaiement = intval(preg_replace('/[^0-9]/', '', $facture->payment_period));

            if ($periodePaiement === 0) {
                $periodePaiement = 60;
            }
            //pour calculer le temps aussi
            /*$dateCreation = Carbon::parse($facture->created_at);
            $dateLimitePaiement = $dateCreation->addDays($periodePaiement);
            $joursRestants = $dateLimitePaiement->diffInDays(Carbon::now());
            $joursÉcoulés = Carbon::now()->diffInDays($dateCreation);
            $pourcentageJoursRestants = round(($joursÉcoulés / $periodePaiement) * 100, 2);
            $pourcentageJoursPassés = round(100 - $pourcentageJoursRestants, 2);
            $facture->progress = [
                'joursRestantsPourPaiement' => $joursRestants,
                'pourcentageJoursPassés' => $pourcentageJoursPassés,
                'pourcentageJoursRestants' => $pourcentageJoursRestants
            ];*/

            //pour calculer juste la date 
            $dateCreation = Carbon::parse($facture->created_at)->startOfDay();
            $dateLimitePaiement = $dateCreation->copy()->addDays($periodePaiement); // Utilisation de copy() pour éviter la modification de la date de création
            $joursRestants = $dateLimitePaiement->diffInDays(Carbon::now());
            $joursÉcoulés = Carbon::now()->diffInDays($dateCreation);
            $pourcentageJoursRestants = round(($joursRestants / $periodePaiement) * 100, 2);
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








    function invoiceToValidate(Request $request)
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

        $facture = Facture::find($request->input('id'));

        if (!$facture) {
            return response()->json([
                'success' => false,
                'message' => "la facture n'existe pas",
                'data' => [],
            ]);
        }

        $typeFacture = $facture->typeFacture()->first();
        if ($typeFacture === null || $typeFacture->typeName === null) {
            $facture->typeFacture = null;
        } else {
            $facture->typeFacture = $typeFacture;
        }

        /*$etat = $facture->etat()->first();

        if ($etat === null || $etat->name_etat === null) {
            $facture->etat = null;
        } else {
            $facture->etat = $etat;
        }*/


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


        if ($facture->fournisseur_id !== null) {
            $user = User::select('role_id', 'name', 'idFiscale')->where('id', $facture->fournisseur_id)->first();
            $user->role_name = $facture->created_by;
            $facture->createdBy = $user;
        } else {
            $user = User::select('role_id', 'name', 'idFiscale')->where('id', $facture->agent_bof_id)->first();
            $facture->createdBy = $user;
        }

        $periodePaiement = intval(preg_replace('/[^0-9]/', '', $facture->payment_period));

        if ($periodePaiement === 0) {
            $periodePaiement = 60;
        }
        //calculer aussi le temps 
        /*$dateCreation = Carbon::parse($facture->created_at);
        $dateLimitePaiement = $dateCreation->addDays($periodePaiement);
        $joursRestants = $dateLimitePaiement->diffInDays(Carbon::now());
        $joursÉcoulés = Carbon::now()->diffInDays($dateCreation);
        $pourcentageJoursRestants = round(($joursÉcoulés / $periodePaiement) * 100, 2);
        $pourcentageJoursPassés = round(100 - $pourcentageJoursRestants, 2);
        $facture->progress = [
            'joursRestantsPourPaiement' => $joursRestants,
            'pourcentageJoursPassés' => $pourcentageJoursPassés,
            'pourcentageJoursRestants' => $pourcentageJoursRestants
        ];*/

        //pour calculer juste la date 
        $dateCreation = Carbon::parse($facture->created_at)->startOfDay();
        $dateLimitePaiement = $dateCreation->copy()->addDays($periodePaiement); // Utilisation de copy() pour éviter la modification de la date de création
        $joursRestants = $dateLimitePaiement->diffInDays(Carbon::now());
        $joursÉcoulés = Carbon::now()->diffInDays($dateCreation);
        $pourcentageJoursRestants = round(($joursRestants / $periodePaiement) * 100, 2);
        $pourcentageJoursPassés = round(100 - $pourcentageJoursRestants, 2);
        $facture->progress = [
            'joursRestantsPourPaiement' => $joursRestants,
            'pourcentageJoursPassés' => $pourcentageJoursPassés,
            'pourcentageJoursRestants' => $pourcentageJoursRestants
        ];


        $piecesJointes = collect($facture->pieces_jointes)->values()->all();
        $piece_jointes_name = [];
        foreach ($piecesJointes as $piecesJointe) {
            $piece_jointes_name[] = PieceJointeFacture::find($piecesJointe)->namePJ;
        }

        $facture->pieceJointeNom = $piece_jointes_name;
        $objet = ObjetFacture::select('id', 'objet_name')->find($facture->objet_facture_id);
        if (!$objet || $objet->objet_name === null) {
            $facture->nomObjetFacture = null;
        } else {
            $facture->nomObjetFacture = $objet->objet_name;
        }
        $bc =  BonDeCommande::find($facture->bon_de_commande_id);
        if (!$bc || $bc->num_commande === null) {
            $facture->numBonDeCommande = null;
        } else {
            $facture->numBonDeCommande = $bc->num_commande;
        }
        if ($facture->fournisseur_id !== null) {
            $fournisseur = User::find($facture->fournisseur_id);
            $fournisseur->makeHidden(['refresh_token', 'refreshToken_created_at']);
            $agentBof = null;
        } else {
            $fournisseur = null;
            $agentBof = User::find($facture->agent_bof_id);
            $agentBof->makeHidden(['refresh_token', 'refreshToken_created_at']);
        }
        $facture->makeHidden(['objet_facture_id', 'bon_de_commande_id', 'etat_id']);
        $steps = Etapes::where('facture_id', $facture->id)
            ->orderBy('created_at', 'asc')
            ->get();
        $stepsInvoice = $steps->mapWithKeys(function ($step, $index) {
            $etat = optional($step->etat)->name_etat;
            return [
                $index + 1 => [
                    'etat' => $etat,
                    'ProcessedBy' => [
                        'roleName' => $step->traitParRoleNom,
                        'agentName' => $step->traitParNom,
                        'agentEmail' => User::select('email')->where('id', $step->traitParId)->first()->email,
                    ],
                    'created_at' => $step->created_at
                ]
            ];
        })->all();


        return response()->json([
            'success' => true,
            'message' => "voila la facture",
            "data" => [
                "facture" => $facture,
                "fournisseur" => $fournisseur,
                "agentBof" => $agentBof,
                "timeline" => $stepsInvoice,
            ]
        ]);
    }



    function valideInvoice(Request $request)
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

        $facture = Facture::find($request->input('id'));
        //dd($request->input('id'));
        if (!$facture) {
            return response()->json([
                'success' => false,
                'message' => "la facture n'existe pas",
                'data' => [],
            ]);
        }

        if ($role->id === 2 && $request->input('etat_id') === "1") {
            if ($facture->validePar === $role->name) {
                $facture->validePar = null;
                $facture->etat_id = 1;
                $facture->save();
                return  response()->json([
                    'success' => true,
                    'message' => "l'état de la facture est En Attente",
                    'data' => [],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "vous n'avez pas la possibilité de changer l'état de la facture" . $facture->id . "vers en Attente car elle est déja en cours de traitemebt par un autre agent",
                    'data' => [],
                ]);
            }
        }

        if ($facture->validePar === $role->name) {
            return  response()->json([
                'success' => false,
                'message' => "la facture est déja en cours de traitement par un autre agent",
                'data' => [],
            ]);
        }

        $etape = Etapes::where('facture_id', $request->input('id'))
            ->where('traitParRoleNom', $role->name)
            ->first();

        if ($etape) {
            return  response()->json([
                'success' => false,
                'message' => "la facture est déja en cours de traitement par un autre agent",
                'data' => [],
            ]);
        }

        if ($request->input('etat_id') === "2") {
            $facture->validePar = $role->name;
            $facture->etat_id = 2;
            $facture->save();
            Etapes::create([
                'facture_id' => $facture->id,
                'etat_id' => 2,
                'traitParRoleNom' => $role->name,
                'traitParId' => $user->id,
                'traitParNom' => $user->name,
            ]);
            $facture = Facture::find($request->input('id'));
            $typeFactureId = $facture->type_facture_id;

            try {
                if ($role->id === 2) {
                    $facture = Facture::find($request->input('id'));
                    $typeFactureId = $facture->type_facture_id;
                    $emails = User::where('role_id', 4)
                        ->whereJsonContains('type_facture_ids', $typeFactureId)
                        ->pluck('email')
                        ->toArray();
                } elseif ($role->id === 4) {
                    $facture = Facture::find($request->input('id'));
                    $typeFactureId = $facture->type_facture_id;
                    $emails = User::where('role_id', 5)
                        ->whereJsonContains('type_facture_ids', $typeFactureId)
                        ->pluck('email')
                        ->toArray();
                } elseif ($role->id === 5) {
                    $facture = Facture::find($request->input('id'));
                    $typeFactureId = $facture->type_facture_id;
                    $emails = User::where('role_id', 6)
                        ->whereJsonContains('type_facture_ids', $typeFactureId)
                        ->pluck('email')
                        ->toArray();
                } elseif ($role->id === 6) {
                    $facture = Facture::find($request->input('id'));
                    $typeFactureId = $facture->type_facture_id;
                    if ($facture->fournisseur_id) {
                        $fourId = $facture->fournisseur_id;
                        /*$emails = User::where('role_id', 6)
                            ->whereJsonContains('type_facture_ids', $typeFactureId)
                            ->pluck('email')
                            ->toArray();*/
                        $emails = User::where('id', $fourId)
                            ->pluck('email')
                            ->toArray();
                    }
                }

                $users = User::whereIn('email', $emails)->get();
                foreach ($users as $userAg) {
                    if ($userAg->isNotificationsEnabled && $userAg->role_id !== 3) {
                        $client = new Client();
                        $response = $client->post(env('NOTIFICATION_MAIL_URL'), [
                            'json' => [
                                'emails' => [$userAg->email],
                                'message' => 'Une nouvelle facture à valider.'
                            ]
                        ]);

                        Notification::create([
                            'user_id' => $userAg->id,
                            'type' => 'FactureAvalider',
                            'titre' => 'Une nouvelle facture a été envoyée',
                            'num_facture' => $facture->number,
                            'id_facture' => $facture->id,
                            'id_reclamation' => null,
                            'titre_reclamation' => null,
                            'nom_creator' => $user->name,
                        ]);
                    } else {
                        $client = new Client();
                        $response = $client->post(env('NOTIFICATION_MAIL_URL'), [
                            'json' => [
                                'emails' => [$userAg->email],
                                'message' => 'Une nouvelle facture validée.'
                            ]
                        ]);

                        Notification::create([
                            'user_id' => $userAg->id,
                            'type' => 'FactureValidee',
                            'titre' => 'Une nouvelle facture a été validée',
                            'num_facture' => $facture->number,
                            'id_facture' => $facture->id,
                            'id_reclamation' => null,
                            'titre_reclamation' => null,
                            'nom_creator' => $user->name,
                        ]);
                    }
                }

                $notificationsObsoletes = Notification::where('updated_at', '<', Carbon::now()->subHours(env('NOTIFICATION_DELETE_DELAY', 24)))
                    ->where('lu', true)
                    ->get();
                foreach ($notificationsObsoletes as $notification) {
                    $notification->delete();
                }

                return response()->json([
                    'success' => true,
                    'message' => "la facture est validé par : " . $user->name,
                    'data' => []
                ]);
            } catch (\Exception $e) {
                Notification::create([
                    'user_id' => $userAg->id,
                    'type' => 'FactureAvalider',
                    'titre' => 'Une nouvelle facture a été envoyée',
                    'num_facture' => $facture->number,
                    'id_facture' => $facture->id,
                    'id_reclamation' => null,
                    'titre_reclamation' => null,
                    'nom_creator' => $user->name,
                ]);
                $notificationsObsoletes = Notification::where('updated_at', '<', Carbon::now()->subHours(env('NOTIFICATION_DELETE_DELAY', 24)))
                    ->where('lu', true)
                    ->get();
                foreach ($notificationsObsoletes as $notification) {
                    $notification->delete();
                }
                return response()->json([
                    'success' => true,
                    'message' => "la facture est validé par : " . $user->name,
                    'data' => []
                ]);
            }
        }







        if ($request->input('etat_id') === "3") {
            if ($request->input('motif_rejet') === [] || !$request->input('motif_rejet')) {
                return  response()->json([
                    'success' => false,
                    'message' => "Le motif de rejet doit être ajouté",
                    'data' => [],
                ]);
            }
            $facture->validePar = $role->name;
            $facture->etat_id = 3;
            $facture->motif_rejet = $request->input('motif_rejet');
            $facture->save();
            Etapes::create([
                'facture_id' => $facture->id,
                'etat_id' => 3,
                'traitParRoleNom' => $role->name,
                'traitParId' => $user->id,
                'traitParNom' => $user->name,
            ]);
            $facture = Facture::find($request->input('id'));
            if ($facture->fournisseur_id) {
                $fourId = $facture->fournisseur_id;
                try {
                    $emails = User::where('id', $fourId)
                        ->pluck('email')
                        ->toArray();
                    $users = User::whereIn('email', $emails)->get();
                    foreach ($users as $userAg) {
                        if ($userAg->isNotificationsEnabled) {
                            $client = new Client();
                            $response = $client->post(env('NOTIFICATION_MAIL_URL'), [
                                'json' => [
                                    'emails' => [$userAg->email],
                                    'message' => 'Votre facture numéro ' . $facture->number . ' a été validée et est prête à être payée.'
                                ]
                            ]);
                        }
                        Notification::create([
                            'user_id' => $userAg->id,
                            'type' => 'FactureRefusee',
                            'titre' => 'Une nouvelle facture a été refusée',
                            'num_facture' => $facture->number,
                            'id_facture' => $facture->id,
                            'id_reclamation' => null,
                            'titre_reclamation' => null,
                            'nom_creator' => $user->name,
                        ]);
                    }
                    $notificationsObsoletes = Notification::where('updated_at', '<', Carbon::now()->subHours(env('NOTIFICATION_DELETE_DELAY', 24)))
                        ->where('lu', true)
                        ->get();
                    foreach ($notificationsObsoletes as $notification) {
                        $notification->delete();
                    }
                    return response()->json([
                        'success' => true,
                        'message' => "la facture est refusé par : " . $user->name,
                        'data' => []
                    ]);
                } catch (\Exception $e) {
                    // Gérer l'exception ici
                    // Ajouter la création de la notification et la suppression des notifications obsolètes
                    Notification::create([
                        'user_id' => $userAg->id,
                        'type' => 'FactureRefusee',
                        'titre' => 'Une nouvelle facture a été refusée',
                        'num_facture' => $facture->number,
                        'id_facture' => $facture->id,
                        'id_reclamation' => null,
                        'titre_reclamation' => null,
                        'nom_creator' => $user->name,
                    ]);
                    $notificationsObsoletes = Notification::where('updated_at', '<', Carbon::now()->subHours(env('NOTIFICATION_DELETE_DELAY', 24)))
                        ->where('lu', true)
                        ->get();
                    foreach ($notificationsObsoletes as $notification) {
                        $notification->delete();
                    }
                    return response()->json([
                        'success' => true,
                        'message' => "la facture est refusé par : " . $user->name,
                        'data' => []
                    ]);
                }
            }
        }





        function invoiceTypeToValidate(Request $request)
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

            $allTypes = TypesFactures::all('id', 'typeName');
            if ($role->id === 2) {
                $userTypes = TypesFactures::all('id', 'typeName');
            } else {
                $typesFacturesids = collect($user->type_facture_ids)->values()->toArray();
                $userTypes = [];
                foreach ($typesFacturesids as $typesFactureid) {
                    $userTypes[] = TypesFactures::select('id', 'typeName')->where('id', $typesFactureid)->get();
                }
                $userTypes = array_map('json_decode', $userTypes);
                $userTypes = array_merge(...$userTypes);
            }
            return response()->json([
                'success' => true,
                'message' => 'les types factures',
                'data' => [
                    'allTypes' => $allTypes,
                    'userTypes' => $userTypes,
                ]
            ]);
        }
    }


    function motifsDeRejet(Request $request)
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

        $motifsDeRejetsNom = MotifDeRejet::all('nomMotif');
        $motifsDeRejetsIds = MotifDeRejet::all('id');
        $motifsDeRejets = MotifDeRejet::all('id', 'nomMotif');

        return response()->json([
            'success' => true,
            'message' => 'les motifs de rejets',
            'data' => [
                'nomsMotifsDeRejets' => $motifsDeRejetsNom,
                'idsMotifsDeRejets' => $motifsDeRejetsIds,
                'motifsDeRejets' => $motifsDeRejets,
            ],
        ]);
    }
    public function validerFactureStrategy(Request $request){
    $user = JWTAuth::user();
    $facture = Facture::find($request->input('id'));

    if (!$facture) {
        return response()->json([
            'success' => false,
            'message' => 'Facture introuvable',
            'data'    => []
        ], 404);
    }

    $context = new ValidationContext($facture);
    $result = $context->valider($facture, $user);

    return response()->json([
        'success' => $result['success'],
        'message' => $result['message'],
        'data'    => []
    ]);
}

public function rejeterFactureStrategy(Request $request)
{
    $user = JWTAuth::user();
    $facture = Facture::find($request->input('id'));

    if (!$facture) {
        return response()->json([
            'success' => false,
            'message' => 'Facture introuvable',
            'data'    => []
        ], 404);
    }

    $motif = $request->input('motif', '');
    $context = new ValidationContext($facture);
    $result = $context->rejeter($facture, $user, $motif);

    return response()->json([
        'success' => $result['success'],
        'message' => $result['message'],
        'data'    => []
    ]);
}
}
