<?php

namespace App\Services;

use App\Interfaces\ValidationFactureInterface;
use App\Models\BonDeCommande;
use App\Models\Etapes;
use App\Models\Facture;
use App\Models\MotifDeRejet;
use App\Models\Notification;
use App\Models\ObjetFacture;
use App\Models\Personnel_DCF;
use App\Models\PieceJointeFacture;
use App\Models\Role;
use App\Models\TypesFactures;
use App\Models\User;
use App\OCL\PersonnelDCFConstraints;
use App\States\FactureStateFactory;
use App\Strategies\ValidationContext;
use App\ChainOfResponsibility\ValidationChainBuilder;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * ============================================================
 * SERVICE : ValidationFactureService
 * ============================================================
 *
 * SOLID — Dependency Inversion Principle (DIP) :
 *   Implémentation concrète de ValidationFactureInterface.
 *   ValidationFactureController dépend uniquement de l'interface,
 *   jamais de cette classe directement.
 *   → Injection via le conteneur IoC de Laravel (AppServiceProvider).
 *
 * SOLID — Single Responsibility Principle (SRP) :
 *   Ce service concentre toute la logique métier de validation
 *   de factures, libérant le Controller de cette responsabilité.
 *
 * GRASP — Low Coupling :
 *   Le Controller est découplé de ValidationChainBuilder,
 *   ValidationContext et PersonnelDCFConstraints.
 *   Ce service est le seul à les connaître.
 *
 * Patterns utilisés en interne :
 *   - Chain of Responsibility (ValidationChainBuilder)
 *   - Strategy              (ValidationContext)
 *   - State                 (FactureStateFactory)
 *   - OCL constraints       (PersonnelDCFConstraints)
 * ============================================================
 */
class ValidationFactureService implements ValidationFactureInterface
{
    // --------------------------------------------------------
    // CONSULTATION — Listes de factures
    // --------------------------------------------------------

    public function getInvoicesToValidate(Request $request): JsonResponse
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

        $page     = $request->query('page', 1);
        $nb       = $request->query('nb', 10);
        $factures = $this->buildFacturesQuery($role, $user);

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
            $this->enrichFactureForList($facture);
        }

        return response()->json([
            'success' => true,
            'message' => 'les factures à valider',
            'data'    => [
                'totalPages' => $factures->lastPage(),
                'factures'   => $factures->items(),
            ],
        ]);
    }

    public function getInvoicesToValidateCOF(Request $request): JsonResponse
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

        $page     = $request->query('page', 1);
        $nb       = $request->query('nb', 10);
        $factures = $this->buildFacturesQuery($role, $user);

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
            $facture->typeFacture = ($typeFacture === null || $typeFacture->typeName === null) ? null : $typeFacture;

            $facture->etat_name = $facture->etat()->first()->name_etat;
            $etat = $facture->etat()->first();
            if ($etat === null || $etat->name_etat === null) {
                $facture->etat = null;
            } elseif ($etat->id === 2 && $facture->validePar !== "Agent Trésorerie") {
                $facture->etat->id         = 4;
                $facture->etat->name_etat  = "En Cours";
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

    // --------------------------------------------------------
    // CONSULTATION — Détail d'une facture
    // --------------------------------------------------------

    public function getInvoiceToValidate(Request $request): JsonResponse
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

        $facture = Facture::find($request->input('id'));
        if (!$facture) {
            return response()->json([
                'success' => false,
                'message' => "la facture n'existe pas",
                'data'    => [],
            ]);
        }

        $this->enrichFactureForList($facture);

        $piecesJointes  = collect($facture->pieces_jointes)->values()->all();
        $pieceJointesNom = [];
        foreach ($piecesJointes as $pj) {
            $pieceJointesNom[] = PieceJointeFacture::find($pj)->namePJ;
        }
        $facture->pieceJointeNom = $pieceJointesNom;

        $objet = ObjetFacture::select('id', 'objet_name')->find($facture->objet_facture_id);
        $facture->nomObjetFacture = (!$objet || $objet->objet_name === null) ? null : $objet->objet_name;

        $bc = BonDeCommande::find($facture->bon_de_commande_id);
        $facture->numBonDeCommande = (!$bc || $bc->num_commande === null) ? null : $bc->num_commande;

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

        $steps        = Etapes::where('facture_id', $facture->id)->orderBy('created_at', 'asc')->get();
        $stepsInvoice = $steps->mapWithKeys(function ($step, $index) {
            return [
                $index + 1 => [
                    'etat'        => optional($step->etat)->name_etat,
                    'ProcessedBy' => [
                        'roleName'   => $step->traitParRoleNom,
                        'agentName'  => $step->traitParNom,
                        'agentEmail' => User::select('email')->where('id', $step->traitParId)->first()->email,
                    ],
                    'created_at'  => $step->created_at,
                ],
            ];
        })->all();

        return response()->json([
            'success' => true,
            'message' => "voila la facture",
            'data'    => [
                'facture'   => $facture,
                'fournisseur' => $fournisseur,
                'agentBof'  => $agentBof,
                'timeline'  => $stepsInvoice,
            ],
        ]);
    }

    public function getInvoiceToValidateCOF(Request $request): JsonResponse
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

        $facture = Facture::find($request->input('id'));
        if (!$facture) {
            return response()->json([
                'success' => false,
                'message' => "la facture n'existe pas",
                'data'    => [],
            ]);
        }

        $steps        = Etapes::where('facture_id', $request->input('id'))->get();
        $stepsInvoice = $steps->mapWithKeys(function ($step, $index) {
            return [$index => [
                'id'              => $step->id,
                'facture_id'      => $step->facture_id,
                'etat_id'         => $step->etat_id,
                'traitParRoleNom' => $step->traitParRoleNom,
                'traitParId'      => $step->traitParId,
                'traitParNom'     => $step->traitParNom,
                'created_at'      => $step->created_at,
                'updated_at'      => $step->updated_at,
            ]];
        });

        $facture->etapes     = $stepsInvoice;
        $facture->typeFacture = $facture->typeFacture()->first();
        $facture->etat        = $facture->etat()->first();
        $facture->fournisseur = User::find($facture->fournisseur_id);

        // OCL peutTraiterFacture() — lecture seule
        $facture->peutEtreTraiteeParAgent = false;
        if ($role->id !== 3) {
            $personnel = Personnel_DCF::find($user->id);
            if ($personnel) {
                $facture->peutEtreTraiteeParAgent = PersonnelDCFConstraints::peutTraiterFacture($personnel, $facture);
            }
        }

        return response()->json([
            'success' => true,
            'message' => "la facture",
            'data'    => ['facture' => $facture],
        ]);
    }

    // --------------------------------------------------------
    // ACTIONS — Validation / Rejet
    // --------------------------------------------------------

    public function validerOuRejeter(Request $request): JsonResponse
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        if ($role->id === 3) {
            return response()->json([
                'success' => false,
                'message' => "Vous n'avez pas l'autorisation",
                'data'    => [],
            ]);
        }

        $facture = Facture::find($request->input('id'));
        if (!$facture) {
            return response()->json([
                'success' => false,
                'message' => "La facture n'existe pas",
                'data'    => [],
            ]);
        }

        if ($facture->validePar === $role->name) {
            return response()->json([
                'success' => false,
                'message' => "La facture est déjà en cours de traitement par ce rôle",
                'data'    => [],
            ]);
        }

        $etape = Etapes::where('facture_id', $facture->id)->where('traitParRoleNom', $role->name)->first();
        if ($etape) {
            return response()->json([
                'success' => false,
                'message' => "La facture est déjà en cours de traitement par ce rôle",
                'data'    => [],
            ]);
        }

        FactureStateFactory::resolve($facture);

        if ($request->input('etat_id') === "2") {
            try {
                $facture->valider($role->name);
                Etapes::create([
                    'facture_id'      => $facture->id,
                    'etat_id'         => 2,
                    'traitParRoleNom' => $role->name,
                    'traitParId'      => $user->id,
                    'traitParNom'     => $user->name,
                ]);
                $this->sendFactureNotifications($facture, $role, $user, 'valide');
                return response()->json(['success' => true, 'message' => "La facture est validée par : " . $user->name, 'data' => []]);
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage(), 'data' => []]);
            }
        }

        if ($request->input('etat_id') === "3") {
            if (!$request->input('motif_rejet')) {
                return response()->json(['success' => false, 'message' => "Le motif de rejet doit être ajouté", 'data' => []]);
            }
            try {
                $facture->rejeter($role->name);
                $facture->motif_rejet = $request->input('motif_rejet');
                $facture->save();
                Etapes::create([
                    'facture_id'      => $facture->id,
                    'etat_id'         => 3,
                    'traitParRoleNom' => $role->name,
                    'traitParId'      => $user->id,
                    'traitParNom'     => $user->name,
                ]);
                $this->sendFactureNotifications($facture, $role, $user, 'refuse');
                return response()->json(['success' => true, 'message' => "La facture est refusée par : " . $user->name, 'data' => []]);
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage(), 'data' => []]);
            }
        }

        return response()->json(['success' => false, 'message' => "Action non autorisée pour cette facture", 'data' => []]);
    }

    public function validerOuRejeterCOF(Request $request): JsonResponse
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        if ($role->id === 3) {
            return response()->json(['success' => false, 'message' => "vous n'avez pas autorisé", 'data' => []]);
        }

        $facture = Facture::find($request->input('id'));
        if (!$facture) {
            return response()->json(['success' => false, 'message' => "la facture n'existe pas", 'data' => []]);
        }

        // Cas spécial BOF : retour en "En Attente"
        if ($role->id === 2 && $request->input('etat_id') === "1") {
            if ($facture->validePar === $role->name) {
                $facture->validePar = null;
                $facture->etat_id   = 1;
                $facture->save();
                return response()->json(['success' => true, 'message' => "l'état de la facture est En Attente", 'data' => []]);
            }
            return response()->json([
                'success' => false,
                'message' => "vous n'avez pas la possibilité de changer l'état de la facture {$facture->id} vers En Attente car elle est déjà en cours de traitement par un autre agent",
                'data'    => [],
            ]);
        }

        // Contrainte OCL : RoleCorrespondTypeFacture
        if ($role->id !== 2) {
            $personnel = Personnel_DCF::find($user->id);
            if ($personnel) {
                try {
                    PersonnelDCFConstraints::checkRoleCorrespondTypeFacture($personnel, $facture);
                } catch (\InvalidArgumentException $e) {
                    return response()->json(['success' => false, 'message' => $e->getMessage(), 'data' => []], 403);
                }
            }
        }

        // Chain of Responsibility — validation
        if ($request->input('etat_id') === "2") {
            $chain  = ValidationChainBuilder::build();
            $result = $chain->handle($facture, $user);
            return response()->json(['success' => $result['success'], 'message' => $result['message'], 'data' => []], $result['success'] ? 200 : 422);
        }

        // Chain of Responsibility — rejet
        if ($request->input('etat_id') === "3") {
            $motif = $request->input('motif_rejet');
            if (empty($motif)) {
                return response()->json(['success' => false, 'message' => "Le motif de rejet doit être ajouté", 'data' => []]);
            }
            $chain  = ValidationChainBuilder::build();
            $result = $chain->handleRejet($facture, $user, $motif);
            return response()->json(['success' => $result['success'], 'message' => $result['message'], 'data' => []], $result['success'] ? 200 : 422);
        }

        return response()->json(['success' => false, 'message' => "etat_id invalide. Valeurs acceptées : 2 (valider), 3 (rejeter).", 'data' => []], 422);
    }

    public function validerViaStrategy(Request $request): JsonResponse
    {
        $user    = JWTAuth::user();
        $facture = Facture::find($request->input('id'));

        if (!$facture) {
            return response()->json(['success' => false, 'message' => 'Facture introuvable', 'data' => []], 404);
        }

        $context = new ValidationContext($facture);
        $result  = $context->valider($facture, $user);

        return response()->json(['success' => $result['success'], 'message' => $result['message'], 'data' => []]);
    }

    public function rejeterViaStrategy(Request $request): JsonResponse
    {
        $user    = JWTAuth::user();
        $facture = Facture::find($request->input('id'));

        if (!$facture) {
            return response()->json(['success' => false, 'message' => 'Facture introuvable', 'data' => []], 404);
        }

        $motif   = $request->input('motif', '');
        $context = new ValidationContext($facture);
        $result  = $context->rejeter($facture, $user, $motif);

        return response()->json(['success' => $result['success'], 'message' => $result['message'], 'data' => []]);
    }

    // --------------------------------------------------------
    // RÉFÉRENTIELS
    // --------------------------------------------------------

    public function getInvoiceTypes(Request $request): JsonResponse
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        if ($role->id === 3) {
            return response()->json(['success' => false, 'message' => "vous n'avez pas autorisé", 'data' => []]);
        }

        $allTypes = TypesFactures::all('id', 'typeName');
        if ($role->id === 2) {
            $userTypes = TypesFactures::all('id', 'typeName');
        } else {
            $typesFacturesids = collect($user->type_facture_ids)->values()->toArray();
            $userTypes = [];
            foreach ($typesFacturesids as $id) {
                $userTypes[] = TypesFactures::select('id', 'typeName')->where('id', $id)->get();
            }
            $userTypes = array_map('json_decode', $userTypes);
            $userTypes = array_merge(...$userTypes);
        }

        return response()->json([
            'success' => true,
            'message' => 'les types factures',
            'data'    => ['allTypes' => $allTypes, 'userTypes' => $userTypes],
        ]);
    }

    public function getInvoiceTypesCOF(Request $request): JsonResponse
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        if ($role->id === 3) {
            return response()->json(['success' => false, 'message' => "vous n'avez pas autorisé", 'data' => []]);
        }

        return response()->json([
            'success' => true,
            'message' => "les types de factures",
            'data'    => ['types_factures' => TypesFactures::all()],
        ]);
    }

    public function getMotifsDeRejet(Request $request): JsonResponse
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        if ($role->id === 3) {
            return response()->json(['success' => false, 'message' => "vous n'avez pas autorisé", 'data' => []]);
        }

        return response()->json([
            'success' => true,
            'message' => 'les motifs de rejets',
            'data'    => [
                'nomsMotifsDeRejets' => MotifDeRejet::all('nomMotif'),
                'idsMotifsDeRejets'  => MotifDeRejet::all('id'),
                'motifsDeRejets'     => MotifDeRejet::all('id', 'nomMotif'),
            ],
        ]);
    }

    public function getMotifsDeRejetCOF(Request $request): JsonResponse
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        if ($role->id === 3) {
            return response()->json(['success' => false, 'message' => "vous n'avez pas autorisé", 'data' => []]);
        }

        return response()->json([
            'success' => true,
            'message' => "les motifs de rejet",
            'data'    => ['motifs' => MotifDeRejet::all()],
        ]);
    }

    // --------------------------------------------------------
    // OCL — Lot de factures
    // --------------------------------------------------------

    public function verifierLotFactures(Request $request): JsonResponse
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        if ($role->id === 3) {
            return response()->json(['success' => false, 'message' => "Les fournisseurs ne peuvent pas valider des factures.", 'data' => []], 403);
        }

        $ids      = $request->input('facture_ids', []);
        $factures = Facture::whereIn('id', $ids)->get();

        if ($factures->isEmpty()) {
            return response()->json(['success' => false, 'message' => "Aucune facture trouvée pour les IDs fournis.", 'data' => []], 404);
        }

        $personnel = Personnel_DCF::find($user->id);
        if (!$personnel) {
            return response()->json(['success' => false, 'message' => "Personnel DCF introuvable.", 'data' => []], 404);
        }

        try {
            PersonnelDCFConstraints::checkForAllFactures($personnel, $factures);
            return response()->json([
                'success' => true,
                'message' => "L'agent '{$user->name}' est autorisé à traiter les " . count($factures) . " facture(s) du lot.",
                'data'    => ['facture_ids' => $ids],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'data' => []], 403);
        }
    }

    // --------------------------------------------------------
    // HELPERS PRIVÉS
    // --------------------------------------------------------

    /**
     * Construit la query Eloquent selon le rôle de l'utilisateur.
     */
    private function buildFacturesQuery($role, $user)
    {
        $select = ['id', 'number', 'billing_date', 'created_at', 'updated_at',
                   'etat_id', 'type_facture_id', 'fournisseur_id', 'agent_bof_id',
                   'amount', 'created_by', 'validePar', 'payment_period'];

        return match ($role->id) {
            2 => Facture::select($select)->where('etat_id', 1),
            4 => Facture::select($select)->where('etat_id', 2)->where('validePar', 'Agent Bof')->whereIn('type_facture_id', $user->type_facture_ids),
            5 => Facture::select($select)->where('etat_id', 2)->where('validePar', 'Agent Ap')->whereIn('type_facture_id', $user->type_facture_ids),
            6 => Facture::select($select)->where('etat_id', 2)->where('validePar', 'Agent Fiscaliste')->whereIn('type_facture_id', $user->type_facture_ids),
            default => Facture::select($select)->whereRaw('1=0'),
        };
    }

    /**
     * Enrichit une facture avec typeFacture, etat, createdBy, progress.
     */
    private function enrichFactureForList(Facture $facture): void
    {
        $typeFacture = $facture->typeFacture()->first();
        $facture->typeFacture = ($typeFacture === null || $typeFacture->typeName === null) ? null : $typeFacture;

        $facture->etat_name = $facture->etat()->first()->name_etat;
        $etat = $facture->etat()->first();
        if ($etat === null || $etat->name_etat === null) {
            $facture->etat = null;
        } elseif ($etat->id === 2 && $facture->validePar !== "Agent Trésorerie") {
            $facture->etat->id        = 4;
            $facture->etat->name_etat = "En Cours";
        } else {
            $facture->etat = $etat;
        }

        if ($facture->fournisseur_id !== null) {
            $usr = User::select('role_id', 'name', 'idFiscale')->where('id', $facture->fournisseur_id)->first();
            $usr->role_name      = $facture->created_by;
            $facture->createdBy  = $usr;
        } else {
            $facture->createdBy = User::select('role_id', 'name', 'idFiscale')->where('id', $facture->agent_bof_id)->first();
        }

        $periodePaiement = intval(preg_replace('/[^0-9]/', '', $facture->payment_period));
        if ($periodePaiement === 0) $periodePaiement = 60;

        $dateCreation        = Carbon::parse($facture->created_at)->startOfDay();
        $dateLimitePaiement  = $dateCreation->copy()->addDays($periodePaiement);
        $joursRestants       = $dateLimitePaiement->diffInDays(Carbon::now());
        $joursÉcoulés        = Carbon::now()->diffInDays($dateCreation);
        $facture->progress   = [
            'joursRestantsPourPaiement' => $joursRestants,
            'pourcentageJoursPassés'    => round(100 - ($joursRestants / $periodePaiement) * 100, 2),
            'pourcentageJoursRestants'  => round(($joursRestants / $periodePaiement) * 100, 2),
        ];
    }

    /**
     * Envoie les notifications mail + in-app après action sur une facture.
     */
    private function sendFactureNotifications(Facture $facture, $role, $user, string $action): void
    {
        $typeFactureId = $facture->type_facture_id;
        $emails = match ($role->id) {
            2 => User::where('role_id', 4)->whereJsonContains('type_facture_ids', $typeFactureId)->pluck('email')->toArray(),
            4 => User::where('role_id', 5)->whereJsonContains('type_facture_ids', $typeFactureId)->pluck('email')->toArray(),
            5 => User::where('role_id', 6)->whereJsonContains('type_facture_ids', $typeFactureId)->pluck('email')->toArray(),
            6 => $facture->fournisseur_id ? User::where('id', $facture->fournisseur_id)->pluck('email')->toArray() : [],
            default => [],
        };

        $users  = User::whereIn('email', $emails)->get();
        $client = new Client();

        foreach ($users as $userAg) {
            $message   = $action === 'valide' ? 'Une nouvelle facture validée.' : 'Votre facture a été refusée.';
            $typeNotif = $action === 'valide' ? 'FactureValidee' : 'FactureRefusee';
            $titre     = $action === 'valide' ? 'Une nouvelle facture a été validée' : 'Une nouvelle facture a été refusée';

            if ($userAg->isNotificationsEnabled && $userAg->role_id !== 3) {
                $client->post(env('NOTIFICATION_MAIL_URL'), ['json' => ['emails' => [$userAg->email], 'message' => $message]]);
            }

            Notification::create([
                'user_id'          => $userAg->id,
                'type'             => $typeNotif,
                'titre'            => $titre,
                'num_facture'      => $facture->number,
                'id_facture'       => $facture->id,
                'id_reclamation'   => null,
                'titre_reclamation' => null,
                'nom_creator'      => $user->name,
            ]);
        }

        Notification::where('updated_at', '<', Carbon::now()->subHours(env('NOTIFICATION_DELETE_DELAY', 24)))
            ->where('lu', true)
            ->delete();
    }
}
