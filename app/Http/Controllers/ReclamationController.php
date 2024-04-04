<?php

namespace App\Http\Controllers;

use App\Models\BonDeCommande;
use App\Models\Facture;
use App\Models\Reclamation;
use Dotenv\Validator;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;

class ReclamationController extends Controller
{
    function create(Request $request) // nombre de réclamation par fournisseur ?
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        //if ($role->id !== 3 && $role->id !== 2) {
        if ($role->id !== 3) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette ressource',
                'data' => []
            ]); // 403 accés refusé
        }

        /* $facture = Facture::where('number', $request->input('numFacture'))->first();

        if (!$facture) {
            return response()->json([
                'success' => false,
                'message' => "La facture n'existe pas",
                'data' => [],
            ]);
        }

        if ($facture->fournisseur_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'vérifier le numéro de votre facture',
                'data' => [],
            ]);
        }

        $purOrder = BonDeCommande::where('num_commande', $request->input('numCommande'))->first();

        if (!$purOrder || ($purOrder->four_idFiscale !== $user->idFiscale)) {
            return response()->json([
                'success' => false,
                'message' => "vérifier votre numero du bon de commande",
                'data' => [],
            ]);
        }

        if ($request->input('idFiscale') !== $user->idFiscale) {
            return response()->json([
                'success' => false,
                'message' => "vérifier votre matricule fiscale",
                'data' => [],
            ]);
        }*/

        $validator = Validator($request->all(), [
            'title' => 'required|string|max:255',
            'text' => 'required|string',
            'idFiscale' => 'nullable|string',
            'numFacture' => 'nullable|string',
            'numCommande' => 'nullable|string',
            'attached_file' => 'nullable||file|mimes:pdf|max:102400'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
                'data' => [],
            ]);
        }


        if ($request->hasFile('attached_file')) {
            $file = $request->file('attached_file');
            //$attachedFile = "";
            $fileName = $file->getClientOriginalName() . "_" . $user->name . "." . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('attached_files', $fileName, 'reclamation');
            $attachedFile = $filePath;
        }


        Reclamation::create([
            'title' => $request->input('title'),
            'text' => $request->input('text'),
            'idFiscale' => $request->input('idFiscale'),
            'numFacture' => $request->input('numFacture'),
            'numCommande' => $request->input('numCommande'),
            'attached_file' => $attachedFile,
            'etat' => 'En Attente',
            'fournisseur_id' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'La réclamation a été ajouté avec succés',
            'data' => [],
        ]);
    }


    function getAllReclamation(Request $request)
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        if ($role->id !== 3 && $role->id !== 2) {
            return response()->json([
                'success' => false,
                'message' => "vous n'avez pas l'autorisation",
                'data' => [],
            ]);
        }

        $page = $request->query('page', 1);
        $nb = $request->query('nb', 10);
        if ($role->id === 3) {
            $reclamations = Reclamation::where('fournisseur_id', $user->id)->paginate($nb, ['*'], 'page', $page);
            return response()->json([
                'success' => true,
                'message' => "voici les réclamtions",
                'data' => [
                    'totalPages' => $reclamations->lastPage(),
                    'reclamations' => $reclamations->items(),
                ]
            ]);
        }

        //pour agent bof
        $reclamations = Reclamation::paginate($nb, ['*'], 'page', $page);
        return response()->json([
            'success' => true,
            'message' => "voici les réclamations",
            'data' => [
                'totalPages' => $reclamations->lastPage(),
                'reclamations' => $reclamations->items(),
            ]
        ]);
    }


    function getReclamation(Request $request)
    {

        $user = JWTAuth::user();
        $role = $user->role()->first();

        if ($role->id !== 3 && $role->id !== 2) {
            return response()->json([
                'success' => false,
                'message' => "vous n'avez pas l'autorisation",
                'data' => [],
            ]);
        }

        $reclamation = Reclamation::where('id', $request->input('id'))->first();

        if (!$reclamation) {
            return response()->json([
                'success' => false,
                'message' => "la réclamtion n'existe pas",
                'data' => [],
            ]);
        }

        if ($role->id === 3 && $reclamation->fournisseur_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => "vous n'avez pas l'autorisation",
                'data' => [],
            ]);
        } else {
            return response()->json([
                'success' => true,
                'message' => "voici votre réclamations",
                'data' => [
                    'reclamation' => $reclamation,
                ]
            ]);
        }

        //pour agent bof
        return response()->json([
            'success' => true,
            'message' => "voici la réclaamtion",
            'data' => [
                'reclamation' => $reclamation,
            ]
        ]);
    }






    function deleteReclamation(Request $request)
    {

        $user = JWTAuth::user();
        $role = $user->role()->first();

        if ($role->id !== 3 && $role->id !== 2) {
            return response()->json([
                'success' => false,
                'message' => "vous n'avez pas l'autorisation",
                'data' => [],
            ]);
        }

        $reclamation = Reclamation::where('id', $request->input('id'))->first();

        if (!$reclamation) {
            return response()->json([
                'success' => false,
                'message' => "la réclamtion n'existe pas",
                'data' => [],
            ]);
        }

        if (($role->id === 3 && $reclamation->fournisseur_id !== $user->id) || $reclamation->etat !== "En Attente") {
            return response()->json([
                'success' => false,
                'message' => "vous n'avez pas l'autorisation",
                'data' => [],
            ]);
        }

        $reclamation->delete();

        return response()->json([
            'success' => true,
            'message' => "La réclamation a été supprimer avec succes",
            'data' => [],
        ]);
    }



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



    function getReclamationSpec(Request $request)
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        if ($role->id !== 3 && $role->id !== 2) {
            return response()->json([
                'success' => false,
                'message' => "vous n'avez pas l'autorisation",
                'data' => [],
            ]);
        }

        $page = $request->query('page', 1);
        $nb = $request->query('nb', 10);
        if ($role->id === 3) {
            $reclamations = Reclamation::select('title', 'text', 'etat')->where('fournisseur_id', $user->id)->paginate($nb, ['*'], 'page', $page);
            foreach ($reclamations as $reclamation) {
                $reclamation->text = Str::limit($reclamation->text, 197);
            }
            return response()->json([
                'success' => true,
                'message' => "voici les réclamtions",
                'data' => [
                    'totalPages' => $reclamations->lastPage(),
                    'reclamations' => $reclamations->items(),
                ]
            ]);
        }

        //pour agent bof
        $reclamations = Reclamation::select('title', 'text', 'etat')->paginate($nb, ['*'], 'page', $page);
        foreach ($reclamations as $reclamation) {
            $reclamation->text = Str::limit($reclamation->text, 197);
        }
        return response()->json([
            'success' => true,
            'message' => "voici les réclamations",
            'data' => [
                'totalPages' => $reclamations->lastPage(),
                'reclamations' => $reclamations->items(),
            ]
        ]);
    }
}
