<?php

namespace App\Http\Controllers;

use App\Models\BonDeCommande;
use App\Models\Facture;
use App\Models\Reclamation;
use Carbon\Carbon;
use Dotenv\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;
use Webklex\PDFMerger\Facades\PDFMergerFacade;

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
            'attached_file.*' => 'nullable||file|mimes:pdf|max:102400'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
                'data' => [],
            ]);
        }

        $count = Reclamation::all()->count();
        if ($request->hasFile('attached_file')) {
            $files = $request->file('attached_file');
            $pdf = PDFMergerFacade::init();
            foreach ($files as $file) {
                $pdf->addPDF($file->getPathName(), 'all');
            }
            $fileName = 'reclamation_' . $user->name . " " . " nb_" . $count = $count + 1 . ".pdf"; //. '.' . $file->getClientOriginalExtension();
            $pdf->merge();
            Storage::disk('reclamation')->put("users/" . $user->id . "/userUploads/reclamation/" .  $fileName, $pdf->output());
            $attachedFile = "users/" . $user->id . "/userUploads/reclamation/" .  $fileName;
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
            $reclamations = Reclamation::where('fournisseur_id', $user->id)->orderBy('created_at', 'desc')->paginate($nb, ['*'], 'page', $page);
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
        $reclamations = Reclamation::orderBy('created_at', 'desc')->paginate($nb, ['*'], 'page', $page);
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


    function getFileReclamation(Request $request, $userId, $fileName)
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

        $filePath = "users/" . $userId . "/userUploads/reclamation/" .  $fileName;

        if ($role->id === 3) {
            $recFile = Reclamation::where('attached_file', $filePath)
                ->where('fournisseur_id', $user->id)->first(); // pour etre true => il faut le fichier recherché doit etre existe avec le meme path et doit etre id = $user->id
        } else {
            $recFile = Reclamation::where('attached_file', $filePath)->first();
        }
        if (!$recFile) {
            return response()->json([
                'success' => false,
                'message' => "le fichier  n'existe pas", //BD
                'data' => [],
            ]);
        }
        if (Storage::disk('reclamation')->exists($filePath)) {
            $fileContents = Storage::disk('reclamation')->get($filePath);
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
}
