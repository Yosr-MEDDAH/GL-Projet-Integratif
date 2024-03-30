<?php

namespace App\Http\Controllers;

use App\Models\BonDeCommande;
use App\Models\Bordereau;
use App\Models\Etat;
use App\Models\Facture;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;

class FactureController extends Controller
{
    function createInvoice(Request $request)
    {

        try {
            $user = JWTAuth::user();
            $role = $user->role()->first();
            $role_id = $role->id;
            if ($role_id !== 3 && $role_id !== 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'êtes pas autorisé à accéder à cette ressource',
                    'data' => []
                ], 403); // 403 accés refusé
            }

            if (Facture::where('number', $request->input('number'))->first()) {
                return response()->json([
                    'success' => false,
                    'message' => 'La facture existe déja',
                    'data' => [],
                ]);
            }



            $messages = [
                'num_commande.required' => 'Le numéro de commande est requis.',
                'num_commande.numeric' => 'Le numéro de commande doit être un nombre.',
                'id_fiscale.required' => 'L\'ID fiscale est requis.',
                'id_fiscale.string' => 'L\'ID fiscale doit être une chaîne de caractères.',
            ];

            $validator = Validator::make($request->all(), [
                'num_commande' => 'required|numeric', // changer nom _
                'id_fiscale' => 'required|string|max:255',
            ], $messages);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors(),
                    'data' => [],
                ]);
            }

            $purOrder = BonDeCommande::where('num_commande', $request->input('num_commande'))->first();

            //if (($role_id === 3) || ($role_id === 2 && User::where('idFiscale' ,$request->input('id_fiscale'))->first())) ******** 3eme cas (ajouter un attribut)
            if ($role_id === 3) {
                if ($request->input('id_fiscale') !== $user->idFiscale) {
                    return response()->json([
                        'success' => false,
                        'message' => "votre matricule fiscale n'est pas correcte", // on peut éliminer success
                        'data' => [],
                    ]);
                }
                if (!$purOrder || ($purOrder->four_idFiscale !== $user->idFiscale)) {
                    return response()->json([
                        'success' => false,
                        'message' => "vérifier votre numero du bon de commande",
                        'data' => [],
                    ]);
                }
            }

            //ajouter messages spécifiques ou pas ?? ********** ///////
            $validator = Validator::make($request->all(), [
                'organization' => 'required|string|max:255',
                'number' => 'required|numeric',
                'invoice_name' => 'required|string|max:255',
                'currency' => 'required|string|max:3',
                'billing_date' => 'required|date_format:Y-m-d', // à revoir 
                'amount' => 'required|numeric',
                'payment_period' => 'required|max:255',
                'invoice_file_path' => 'required|file|mimes:pdf|max:102400',
            ]);


            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors(),
                    'data' => [],
                ]);
            }


            $file = $request->file('invoice_file_path');
            $fileName = $file->getClientOriginalName() . '.' . $file->getClientOriginalExtension();

            $bord = Bordereau::whereDate('created_at', Carbon::today()->toDateString())->first();

            if (!$bord) {
                $filePath = $file->storeAs(Carbon::now()->toDateString(), $fileName, 'facture');
                $bord = new Bordereau();
                $bord->date_sent = now();
                $bord->folder = Carbon::now()->toDateString();
                $bord->status = 'En cours';
                $bord->reference = Str::random(8) . '/' . Carbon::now()->toDateString();
                $bord->save();
            } else {
                $filePath = $file->storeAs($bord->folder, $fileName, 'facture');
                $bord->date_sent = now();
            }

            // Archivage (si nécessaire) ********


            if ($role_id === 3) {
                Facture::create([
                    'number' => $request->input('number'),
                    'invoice_name' => $request->input('invoice_name'),
                    'organization' => $request->input('organization'),
                    'billing_date' => $request->input('billing_date'),
                    'amount' => $request->input('amount'),
                    'invoice_file_path' => $filePath,
                    'reception_date' => Carbon::now(),
                    'isArchived' => 0,
                    'etat_id' => 1,
                    'borderau_id' => $bord->id,
                    'bon_de_commande_id' => $purOrder->id,
                    'created_by' => $role->name,
                    'fournisseur_id' => $user->id,
                    'agent_bof_id' => null,
                ]);
            } else {
                Facture::create([
                    'number' => $request->input('number'),
                    'invoice_name' => $request->input('invoice_name'),
                    'organization' => $request->input('organization'),
                    'billing_date' => $request->input('billing_date'),
                    'amount' => $request->input('amount'),
                    'invoice_file_path' => $filePath,
                    'reception_date' => Carbon::now(),
                    'isArchived' => false,
                    'etat_id' => 1,
                    'borderau_id' => $bord->id,
                    'bon_de_commande_id' => $purOrder->id,
                    'created_by' => $role->name,
                    'fournisseur_id' => null,
                    'agent_bof_id' => $user->id,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'La facture a été ajouté avec succés',
                'data' => [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => []
            ]);
        }










        /*$bord = Bordereau::whereTime('created_at', '=', Carbon::now()->format('H:i:s'))->first();
        $file = $request->file('invoice_file_path');
        $fileName = $file->getClientOriginalName() . '.' . $file->getClientOriginalExtension();
        if (!$bord) {
            $file->storeAs(Carbon::now()->format('H-i-s'), $fileName, 'facture');
            $nouveauBordereau = new Bordereau();
            $nouveauBordereau->date_sent = now();
            $nouveauBordereau->folder = $fileName;
            $nouveauBordereau->save();
        }
        //$file->storeAs($bord->folder, $fileName, 'facture');
        $bord->date_sent = Carbon::now();
        return Carbon::now();*/

        /*$bord = Bordereau::whereTime('created_at', '=', '17:28:24')->first();
        $file = $request->file('invoice_file_path');
        $fileName = $file->getClientOriginalName() . '.' . $file->getClientOriginalExtension();
        if (!$bord) {
            $file->storeAs(Carbon::now()->format('H-i-s'), $fileName, 'facture');
            $nouveauBordereau = new Bordereau();
            $nouveauBordereau->date_sent = now();
            $nouveauBordereau->folder = Carbon::now()->format('H-i-s');
            $nouveauBordereau->save();
        } else {
            $file->storeAs($bord->folder, $fileName, 'facture');
            $bord->date_sent = Carbon::now();
        }*/
    }

    function deleteInvoice(Request $request)
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

        $facture = Facture::find($request->input('id'));

        if (!$facture) {
            return response()->json([
                'success' => false,
                'message' => 'la facture n \'existe pas',
                'data' => [],
            ]);
        }

        if ($role->id === 3) {
            if ($facture->fournisseur_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'cette facture n\' est pas concerné pour vous',
                    'data' => [],
                ]);
            }
            if ($facture->etat()->first()->id !== 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'vous n\'avez pas l\'autorisation de supprimer votre facture car elle est en cours de traitement',
                    'data' => [],
                ]);
            }

            $facture->delete();
            return response()->json([
                'success' => true,
                'message' => 'votre facture a été supprimé avec succés',
                'data' => [],
            ]);
        }
        // question pour Mr Yassine : agent bof peut supprimer n'importe quelle facture ? 
        //chaque agent bof peut uniquement supprimer une facture qu'il a créée
        if (($facture->created_by !== $role->name) || ($facture->agent_bof_id !== $user->id)) {
            return response()->json([
                'success' => false,
                'message' => "vous n'avez pas autorisation de supprimer une facture n'est pas crée par vous",
                'data' => [],
            ]);
        }

        $facture->delete();

        return response()->json([
            'success' => true,
            'message' => 'votre facture a été supprimé avec succés',
            'data' => [],
        ]);
    }
}
