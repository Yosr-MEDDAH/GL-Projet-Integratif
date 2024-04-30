<?php

namespace App\Http\Controllers;

use App\Models\BonDeCommande;
use App\Models\Etapes;
use App\Models\Facture;
use App\Models\ObjetFacture;
use App\Models\PieceJointeFacture;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;

class FactureConsultation extends Controller
{
    function getInvoice(Request $request)
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        $facture = Facture::find($request->input('id'));

        if (!$facture) {
            return response()->json([
                'success' => false,
                'message' => "la facture n'existe pas",
                'data' => [],
            ]);
        }

        if ($role->id === 3 && ($facture->fournisseur_id !== $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'cette facture n\' est pas concerné pour vous',
                'data' => [],
            ]);
        }

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
        $facture->makeHidden(['objet_facture_id', 'bon_de_commande_id']);
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

    function getInvoices(Request $request) // ***react Paginate*** (récupération les factures et le nombres de pages avec chaque call (set les informations nombre de pages et les factures))
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        if ($role->id !== 3 && $role->id !== 2) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette ressource',
                'data' => []
            ], 403); // 403 accés refusé
        }

        $page = $request->query('page', 1);
        $nb = $request->query('nb', 10);
        if ($role->id === 3) {
            $factures = Facture::where('fournisseur_id', $user->id)->orderBy('created_at', 'desc')->paginate($nb, ['*'], 'page', $page);
            foreach ($factures as $facture) {
                $etat = $facture->etat()->first();

                if ($etat === null || $etat->name_etat === null) {
                    $facture->etat = null;
                } elseif ($etat->id === 2 && $facture->validePar !== "Agent Trésorerie") {
                    $facture->etat->id = 4;
                    $facture->etat->name_etat = "En Cours";
                }  else {
                    $facture->etat = $etat;
                }
            }
            return response()->json([
                'success' => true,
                'message' => 'voici votre factures',
                'data' => [
                    'totalPages' => $factures->lastPage(),
                    'factures' => $factures->items(),
                ]
            ]);
        }
        //récupération des factures crée avec un agent bof spécifique
        $factures = Facture::where('agent_bof_id', $user->id)->orderBy('created_at', 'desc')->paginate($nb, ['*'], 'page', $page);
        $totalPages = $factures->lastPage();
        //$factures = Facture::where('fournisseur_id', $user->id)->orderBy('created_at', 'desc')->paginate($nb, ['*'], 'page', $page); à éliminer
        foreach ($factures as $facture) {
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
            'message' => 'voici votre factures',
            'data' => [
                'totalPages' => $totalPages,
                'factures' => $factures->items(),
            ]
        ]);
    }

    function getFileInvoice(Request $request, $date, $fileName)
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        /*if ($role->id !== 3 && $role->id !== 2) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette ressource',
                'data' => []
            ], 403); // 403 accés refusé
        }*/

        $filePath = $date . '/' . $fileName;

        if ($role->id === 3) {
            $invoiceFile = Facture::where('invoice_file_path', $filePath)
                ->where('fournisseur_id', $user->id)->first(); // pour etre true => il faut le fichier recherché doit etre existe avec le meme path et doit etre id = $user->id
        } else {
            $invoiceFile = Facture::where('invoice_file_path', $filePath)->first();
        }
        if (!$invoiceFile) {
            return response()->json([
                'success' => false,
                'message' => "le fichier  n'existe pas", //BD
                'data' => [],
            ]);
        }
        if (Storage::disk('facture')->exists($filePath)) {
            $fileContents = Storage::disk('facture')->get($filePath);
            return response()->make($fileContents, 200, [
                'Content-Type' => 'application/pdf'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => "le fichier n'existe pas",
                'data' => []
            ]); //disk
        }
    }




    function getfacturesParBonDeCommande(Request $request)
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

        $purOrder = BonDeCommande::select('id')->where('num_commande', $request->input('num_commande'))->first()->id;
        if (!$purOrder) {
            return response()->json([
                'success' => false,
                'message' => "bon de commande n'existe pas",
                'data' => [],
            ]);
        }

        /*$nbFactures = Facture::where('bon_de_commande_id', $purOrder)->count();
        dd($nbFactures);
        if ($nbFactures === 0) {
            return response()->json([
                'success' => false,
                'message' => "Aucune facture ne correspond à ce bon de commande.",
                'data' => [],
            ]);
        }*/

        $factures = Facture::where('bon_de_commande_id', $purOrder)->paginate($nb, ['*'], 'page', $page);

        foreach ($factures as $facture) {
            $etat = $facture->etat()->first();
            if ($etat === null || $etat->name_etat === null) {
                $facture->etat = null;
            } else {
                $facture->etat = $etat->name_etat;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Voici les factures qui correspondent à ce bon de commande.",
            'data' => [
                'totalPages' => $factures->lastPage(),
                'factures' => $factures->items(),
            ],
        ]);
    }
}
