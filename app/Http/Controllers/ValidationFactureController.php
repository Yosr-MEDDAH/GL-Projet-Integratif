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
use App\States\FactureStateFactory;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Strategies\ValidationContext;
//New
use App\ChainOfResponsibility\ValidationChainBuilder;
use App\Models\Personnel_DCF;
use App\OCL\PersonnelDCFConstraints;


class ValidationFactureController extends Controller
{
    //Before
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

    //After
     function invoicesToValidateCOF(Request $request)
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

            if ($request->input('jours')) {
                $factures->whereRaw('(payment_period - DATEDIFF(CURRENT_DATE(), DATE(created_at))) <= ?', [$request->input('jours')]);
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
            'message' => "les factures",
            'data'    => [
                'totalPages' => $factures->lastPage(),
                'factures'   => $factures->items(),
            ],
        ]);
    }






    //Before
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

    //After
       function invoiceToValidateCOF(Request $request)
    {
        $user   = JWTAuth::user();
        $role   = $user->role()->first();
        $page   = $request->query('page', 1);
        $nb     = $request->query('nb', 10);

        if ($role->id === 3) {
            return response()->json([
                'success' => false,
                'message' => "vous n'avez pas autorisé",
                'data'    => [],
            ]);
        }

        $facture = Facture::find($request->input('id'));
        if (!$facture) {
            return response()->json([
                'success' => false,
                'message' => "la facture n'existe pas",
                'data'    => [],
            ]);
        }

        $steps = Etapes::where('facture_id', $request->input('id'))->get();

        $stepsInvoice = $steps->mapWithKeys(function ($step, $index) {
            return [$index => [
                'id'             => $step->id,
                'facture_id'     => $step->facture_id,
                'etat_id'        => $step->etat_id,
                'traitParRoleNom'=> $step->traitParRoleNom,
                'traitParId'     => $step->traitParId,
                'traitParNom'    => $step->traitParNom,
                'created_at'     => $step->created_at,
                'updated_at'     => $step->updated_at,
            ]];
        });

        $facture->etapes    = $stepsInvoice;
        $facture->typeFacture = $facture->typeFacture()->first();
        $facture->etat      = $facture->etat()->first();
        $facture->fournisseur = User::find($facture->fournisseur_id);

        // ── OCL peutTraiterFacture() — lecture seule ─────────────────
        // On indique au front si l'agent connecté est autorisé à
        // traiter cette facture selon son rôle (sans lever d'exception).
        //
        // context PersonnelDCF
        // inv RoleCorrespondTypeFacture:
        //   self.role.typesFactures->includes(facture.typeFacture)
        $facture->peutEtreTraiteeParAgent = false;
        if ($role->id !== 3) { // Exclure les fournisseurs
            $personnel = Personnel_DCF::find($user->id);
            if ($personnel) {
                // peutTraiterFacture() = version booléenne sans exception.
                // Utilisée ici pour enrichir la réponse JSON (lecture seule),
                // pas pour bloquer — le blocage est dans valideInvoice().
                $facture->peutEtreTraiteeParAgent = PersonnelDCFConstraints::peutTraiterFacture(
                    $personnel,
                    $facture
                );
            }
        }
        // ─────────────────────────────────────────────────────────────

        return response()->json([
            'success' => true,
            'message' => "la facture",
            'data'    => ['facture' => $facture],
        ]);
    }





    //Before
    function valideInvoice(Request $request)
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

function valideInvoice(Request $request)
{
    $user = JWTAuth::user();
    $role = $user->role()->first();

    // Vérification du rôle
    if ($role->id === 3) {
        return response()->json([
            'success' => false,
            'message' => "Vous n'avez pas l'autorisation",
            'data' => [],
        ]);
    }

    // Récupération de la facture
    $facture = Facture::find($request->input('id'));
    if (!$facture) {
        return response()->json([
            'success' => false,
            'message' => "La facture n'existe pas",
            'data' => [],
        ]);
    }

    // Vérification si la facture est déjà en cours par ce rôle
    if ($facture->validePar === $role->name) {
        return response()->json([
            'success' => false,
            'message' => "La facture est déjà en cours de traitement par ce rôle",
            'data' => [],
        ]);
    }

    // Vérification si une étape existe déjà pour ce rôle
    $etape = Etapes::where('facture_id', $facture->id)
        ->where('traitParRoleNom', $role->name)
        ->first();
    if ($etape) {
        return response()->json([
            'success' => false,
            'message' => "La facture est déjà en cours de traitement par ce rôle",
            'data' => [],
        ]);
    }

    // Résolution du State
    $state = FactureStateFactory::resolve($facture);

    // ----------------------
    // Validation de la facture
    // ----------------------
    if ($request->input('etat_id') === "2") {
        try {
            $facture->valider($role->name);

            Etapes::create([
                'facture_id' => $facture->id,
                'etat_id' => 2,
                'traitParRoleNom' => $role->name,
                'traitParId' => $user->id,
                'traitParNom' => $user->name,
            ]);

            // Notifications
            sendFactureNotifications($facture, $role, $user, 'valide');

            return response()->json([
                'success' => true,
                'message' => "La facture est validée par : " . $user->name,
                'data' => []
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => []
            ]);
        }
    }

    // ----------------------
    // Rejet de la facture
    // ----------------------
    if ($request->input('etat_id') === "3") {
        if (!$request->input('motif_rejet')) {
            return response()->json([
                'success' => false,
                'message' => "Le motif de rejet doit être ajouté",
                'data' => [],
            ]);
        }

        try {
            $facture->rejeter($role->name);

            $facture->motif_rejet = $request->input('motif_rejet');
            $facture->save();

            Etapes::create([
                'facture_id' => $facture->id,
                'etat_id' => 3,
                'traitParRoleNom' => $role->name,
                'traitParId' => $user->id,
                'traitParNom' => $user->name,
            ]);

            // Notifications
            sendFactureNotifications($facture, $role, $user, 'refuse');

            return response()->json([
                'success' => true,
                'message' => "La facture est refusée par : " . $user->name,
                'data' => []
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => []
            ]);
        }
    }

    return response()->json([
        'success' => false,
        'message' => "Action non autorisée pour cette facture",
        'data' => [],
    ]);
}


function sendFactureNotifications($facture, $role, $user, $action)
{
    $typeFactureId = $facture->type_facture_id;
    $emails = [];

    // Détermination des destinataires selon le rôle
    if ($role->id === 2) {
        $emails = User::where('role_id', 4)
            ->whereJsonContains('type_facture_ids', $typeFactureId)
            ->pluck('email')
            ->toArray();
    } elseif ($role->id === 4) {
        $emails = User::where('role_id', 5)
            ->whereJsonContains('type_facture_ids', $typeFactureId)
            ->pluck('email')
            ->toArray();
    } elseif ($role->id === 5) {
        $emails = User::where('role_id', 6)
            ->whereJsonContains('type_facture_ids', $typeFactureId)
            ->pluck('email')
            ->toArray();
    } elseif ($role->id === 6 && $facture->fournisseur_id) {
        $emails = User::where('id', $facture->fournisseur_id)
            ->pluck('email')
            ->toArray();
    }

    $users = User::whereIn('email', $emails)->get();
    $client = new Client();

    foreach ($users as $userAg) {
        $message = $action === 'valide' ? 'Une nouvelle facture validée.' : 'Votre facture a été refusée.';
        $typeNotif = $action === 'valide' ? 'FactureValidee' : 'FactureRefusee';
        $titre = $action === 'valide' ? 'Une nouvelle facture a été validée' : 'Une nouvelle facture a été refusée';

        if ($userAg->isNotificationsEnabled && $userAg->role_id !== 3) {
            $client->post(env('NOTIFICATION_MAIL_URL'), [
                'json' => [
                    'emails' => [$userAg->email],
                    'message' => $message
                ]
            ]);
        }

        Notification::create([
            'user_id' => $userAg->id,
            'type' => $typeNotif,
            'titre' => $titre,
            'num_facture' => $facture->number,
            'id_facture' => $facture->id,
            'id_reclamation' => null,
            'titre_reclamation' => null,
            'nom_creator' => $user->name,
        ]);
    }

    // Suppression des notifications obsolètes
    Notification::where('updated_at', '<', Carbon::now()->subHours(env('NOTIFICATION_DELETE_DELAY', 24)))
        ->where('lu', true)
        ->delete();
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

    //After
    // =========================================================
    // MÉTHODE PRINCIPALE DE VALIDATION
    // Utilise le Pattern Chain of Responsibility :
    //   BOF → AP → Fiscaliste → Trésorerie
    //
    // Intègre aussi la contrainte OCL RoleCorrespondTypeFacture :
    //   Un PersonnelDCF ne peut traiter une Facture que si son
    //   rôle correspond au TypeFacture de la facture.
    // =========================================================
    function valideInvoiceCOF(Request $request)
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        // Fournisseurs non autorisés
        if ($role->id === 3) {
            return response()->json([
                'success' => false,
                'message' => "vous n'avez pas autorisé",
                'data'    => [],
            ]);
        }

        $facture = Facture::find($request->input('id'));
        if (!$facture) {
            return response()->json([
                'success' => false,
                'message' => "la facture n'existe pas",
                'data'    => [],
            ]);
        }

        // ── Cas spécial BOF : retour en "En Attente" (etat_id = 1) ──
        if ($role->id === 2 && $request->input('etat_id') === "1") {
            if ($facture->validePar === $role->name) {
                $facture->validePar = null;
                $facture->etat_id   = 1;
                $facture->save();
                return response()->json([
                    'success' => true,
                    'message' => "l'état de la facture est En Attente",
                    'data'    => [],
                ]);
            }
            return response()->json([
                'success' => false,
                'message' => "vous n'avez pas la possibilité de changer l'état de la facture {$facture->id} vers En Attente car elle est déjà en cours de traitement par un autre agent",
                'data'    => [],
            ]);
        }

        // ── CONTRAINTE OCL : RoleCorrespondTypeFacture ──────────────
        // context PersonnelDCF
        // inv RoleCorrespondTypeFacture:
        //   self.role.typesFactures->includes(facture.typeFacture)
        //
        // Seuls les agents non-BOF (AP, Fiscaliste, Trésorerie) ont
        // des restrictions de type ; le BOF (role_id=2) traite tout.
        if ($role->id !== 2) {
            $personnel = Personnel_DCF::find($user->id);
            if ($personnel) {
                try {
                    // ── CONTRAINTE OCL :checkRoleCorrespondTypeFacture
                    PersonnelDCFConstraints::checkRoleCorrespondTypeFacture($personnel, $facture);
                } catch (\InvalidArgumentException $e) {
                    return response()->json([
                        'success' => false,
                        'message' => $e->getMessage(),
                        'data'    => [],
                    ], 403);
                }
            }
        }

        // ── PATTERN CHAIN OF RESPONSIBILITY ─────────────────────────
        // Routing selon etat_id demandé :
        //   etat_id = "2"  → validation (avancer dans la chaîne)
        //   etat_id = "3"  → rejet
        if ($request->input('etat_id') === "2") {

            // Construire la chaîne : BOF → AP → Fiscaliste → Trésorerie
            $chain  = ValidationChainBuilder::build();
            $result = $chain->handle($facture, $user);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data'    => [],
            ], $result['success'] ? 200 : 422);
        }

        if ($request->input('etat_id') === "3") {
            $motif = $request->input('motif_rejet');

            if (empty($motif)) {
                return response()->json([
                    'success' => false,
                    'message' => "Le motif de rejet doit être ajouté",
                    'data'    => [],
                ]);
            }

            // Construire la chaîne et déléguer le rejet
            $chain  = ValidationChainBuilder::build();
            $result = $chain->handleRejet($facture, $user, $motif);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data'    => [],
            ], $result['success'] ? 200 : 422);
        }

        return response()->json([
            'success' => false,
            'message' => "etat_id invalide. Valeurs acceptées : 2 (valider), 3 (rejeter).",
            'data'    => [],
        ], 422);
    }

       function invoiceTypeToValidateCOF(Request $request)
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        if ($role->id === 3) {
            return response()->json([
                'success' => false,
                'message' => "vous n'avez pas autorisé",
                'data'    => [],
            ]);
        }

        $typesFactures = TypesFactures::all();

        return response()->json([
            'success' => true,
            'message' => "les types de factures",
            'data'    => ['types_factures' => $typesFactures],
        ]);
    }







    //Before
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

    //After
       function motifsDeRejetCOF(Request $request)
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        if ($role->id === 3) {
            return response()->json([
                'success' => false,
                'message' => "vous n'avez pas autorisé",
                'data'    => [],
            ]);
        }

        $motifs = MotifDeRejet::all();

        return response()->json([
            'success' => true,
            'message' => "les motifs de rejet",
            'data'    => ['motifs' => $motifs],
        ]);
    }





    //New
      // =========================================================
    // CONTRAINTE OCL — checkForAllFactures()
    // =========================================================
    // Vérifie qu'un agent peut traiter un lot de factures
    // (ex: toutes les factures d'un bordereau).
    //
    // Correspond à l'expression OCL :
    //   context PersonnelDCF
    //   inv RoleCorrespondTypeFacture:
    //     self.factures->forAll(f |
    //       self.role.typesFactures->includes(f.typeFacture)
    //     )
    //
    // Appelé par la route POST /validation/verifier-lot
    // Body JSON : { "facture_ids": [1, 2, 3] }
    // =========================================================
    public function verifierLotFactures(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        if ($role->id === 3) {
            return response()->json([
                'success' => false,
                'message' => "Les fournisseurs ne peuvent pas valider des factures.",
                'data'    => [],
            ], 403);
        }

        $ids      = $request->input('facture_ids', []);
        $factures = Facture::whereIn('id', $ids)->get();

        if ($factures->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => "Aucune facture trouvée pour les IDs fournis.",
                'data'    => [],
            ], 404);
        }

        $personnel = Personnel_DCF::find($user->id);

        if (!$personnel) {
            return response()->json([
                'success' => false,
                'message' => "Personnel DCF introuvable.",
                'data'    => [],
            ], 404);
        }

        try {
            // checkForAllFactures() = OCL ->forAll(f | ...) sur toute la collection.
            // Lève une InvalidArgumentException à la première facture non autorisée.
            // Utilisé ici pour valider un lot AVANT de lancer le workflow en masse.
            PersonnelDCFConstraints::checkForAllFactures($personnel, $factures);

            return response()->json([
                'success' => true,
                'message' => "L'agent '{$user->name}' est autorisé à traiter les " . count($factures) . " facture(s) du lot.",
                'data'    => ['facture_ids' => $ids],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => [],
            ], 403);
        }
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
