<?php

namespace App\Http\Controllers;

use App\Models\BonDeCommande;
use App\Models\Bordereau;
use App\Models\Etat;
use App\Models\Facture;
use App\Models\ObjetFacture;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;
use Webklex\PDFMerger\Facades\PDFMergerFacade;

class FactureController extends Controller
{
    /*function createInvoice(Request $request)
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

            $pur = BonDeCommande::where('num_commande', $request->input('num_commande'))->first();
            if (!$pur) {
                return response()->json([
                    'success' => false,
                    'message' => "vérifier votre numero du bon de commande",
                    'data' => [],
                ]);
            } // vérifier si user posséde la possibilité de saisir un bon de commande ? !!!!!!!!!!!!!!!!!!!!!
            if (Facture::where('number', $request->input('number'))->first() || Facture::where('bon_de_commande_id', $pur->id)->first()) {
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


            $fourName = User::where('idFiscale', $purOrder->four_idFiscale)->first()->name;




            //ajouter messages spécifiques ou pas ?? ********** ///////
            $validator = Validator::make($request->all(), [
                'organization' => 'string|max:255',
                'number' => 'required|numeric',
                'invoice_name' => 'string|max:255',
                'currency' => 'required|string|max:3',
                'billing_date' => 'required|date_format:Y-m-d', // à revoir 
                'amount' => 'required|numeric',
                'payment_period' => 'required|max:255',
                'objet_facture_id' => 'integer',
                'pieces_jointes' => 'json', //changer
                'invoice_file_path.*' => 'required|file|mimes:pdf|max:102400', //changer
            ]);

            $objet = ObjetFacture::find($request->input('objet_facture_id'));

            /*if (!$objet) {
                return response()->json([
                    'success' => false,
                    'message' => "objet n'existe pas",
                    'data' => [],
                ]);
            }

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors(),
                    'data' => [],
                ]);
            }

            $bord = Bordereau::whereDate('created_at', Carbon::today()->toDateString())->first();
            $count = ($bord ? Facture::where('borderau_id', $bord->id)->count() : 0);

            $files = $request->file('invoice_file_path');
            $pdf = PDFMergerFacade::init();
            foreach ($files as $file) {
                $pdf->addPDF($file->getPathName(), 'all');
            }
            $fileName = 'facture_' . $fourName . " " . $count = $count + 1 . ".pdf"; //. '.' . $file->getClientOriginalExtension();
            $pdf->merge();

            if (!$bord) {
                Storage::disk('facture')->put(Carbon::now()->toDateString() . '/' .  $fileName, $pdf->output());
                $filePath = Carbon::now()->toDateString() . '/' .  $fileName;
                $bord = new Bordereau();
                $bord->date_sent = Carbon::now();;
                $bord->folder = Carbon::now()->toDateString();
                $bord->status = 'En cours';
                $bord->reference = Str::random(8) . '/' . Carbon::now()->toDateString();
                $bord->save();
            } else {
                Storage::disk('facture')->put(Carbon::now()->toDateString() . '/' .  $fileName, $pdf->output());
                $filePath = Carbon::now()->toDateString() . '/' .  $fileName;
                $bord->date_sent = Carbon::now();
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
                    'objet_facture_id' => $request->input('objet_facture_id'),
                    'pieces_jointes' => json_decode($request->input('pieces_jointes'), true), //explode(',', $request->input('pieces_jointes')),
                    'borderau_id' => $bord->id,
                    'bon_de_commande_id' => $purOrder->id,
                    'created_by' => $role->name,
                    'fournisseur_id' => $user->id,
                    'agent_bof_id' => null,
                ]);
                $purOrder->hasInvoice = 1;
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
                    'objet_facture_id' => $request->input('objet_facture_id'),
                    'pieces_jointes' =>  json_decode($request->input('pieces_jointes'), true), //explode(',', $request->input('pieces_jointes')),
                    'borderau_id' => $bord->id,
                    'bon_de_commande_id' => $purOrder->id,
                    'created_by' => $role->name,
                    'fournisseur_id' => null,
                    'agent_bof_id' => $user->id,
                ]);
                $purOrder->hasInvoice = 1;
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
        }*/










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
        }
    }*/






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
        if (($facture->created_by !== $role->name) || ($facture->agent_bof_id !== $user->id) || ($facture->etat()->first()->id !== 1)) {
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







    // mettre à jour votre facture à condition que son etat est en attente (sans fichier pdf)
    function updateInvoice(Request $request)
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        if ($role->id !== 3 && $role->id !== 2) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette ressource',
                'data' => []
            ]); // 403 accés refusé
        }

        $facture = Facture::find($request->input('id'));
        if (!$facture) {
            return response()->json([
                'success' => false,
                'message' => "La facture n'existe pas ",
                'data' => [],
            ]);
        }

        if ($facture->etat_id !== 1) {
            return response()->json([
                'success' => false,
                'message' => "vous n'avez pas l'autorisation de modifier la facture car elle en cours de traitement",
                'data' => [],
            ]);
        }

        //agent bof peut modifier seulement une  factures qu'il a créée
        if ($role->id === 2 && ($facture->agent_bof_id !== $user->id)) {
            return response()->json([
                'success' => false,
                'message' => "pas d'autorisation",
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
        if ($role->id === 3) {
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
            'number' => 'required|numeric',
            'currency' => 'required|string|max:3',
            'billing_date' => 'required|date_format:Y-m-d', // à revoir 
            'amount' => 'required|numeric',
            'payment_period' => 'required|max:255',
            'objet_facture_id' => 'integer', // annuler ou non 
            //'pieces_jointes' => 'json', //changer
            // 'invoice_file_path.*' => 'required|file|mimes:pdf|max:102400', //changer
        ]);


        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
                'data' => [],
            ]);
        }

        if (Facture::where('number', $request->input('number'))->first()) {
            return response()->json([
                'success' => false,
                'message' => "vérifier le numero de la facture", // question le numero de la facture est unique ?? 
                'data' => [],
            ]);
        }

        if ($role->id === 3) {
            $facture->update([
                'number' => $request->input('number'),
                'invoice_name' => $request->input('invoice_name'),
                'organization' => $request->input('organization'),
                'billing_date' => $request->input('billing_date'),
                'amount' => $request->input('amount'),
                'reception_date' => Carbon::now(),
                'isArchived' => 0,
                'etat_id' => 1,
                'bon_de_commande_id' => $purOrder->id,
                'created_by' => $role->name,
                'fournisseur_id' => $user->id,
                'agent_bof_id' => null,
                //'objet_facture_id' => $request->input('objet_facture_id'),
                //'pieces_jointes' => json_decode($request->input('pieces_jointes'), true), //explode(',', $request->input('pieces_jointes')),
            ]);
        } else {
            $facture->update([
                'number' => $request->input('number'),
                'invoice_name' => $request->input('invoice_name'),
                'organization' => $request->input('organization'),
                'billing_date' => $request->input('billing_date'),
                'amount' => $request->input('amount'),
                'reception_date' => Carbon::now(),
                'isArchived' => false,
                'etat_id' => 1,
                'bon_de_commande_id' => $purOrder->id,
                'created_by' => $role->name,
                'fournisseur_id' => null,
                'agent_bof_id' => $user->id,
            ]);
        }

        /*$fourName = User::where('idFiscale', $purOrder->four_idFiscale)->first()->name;
        $bord = Bordereau::whereDate('created_at', Carbon::today()->toDateString())->first();
        $count = ($bord ? Facture::where('borderau_id', $bord->id)->count() : 0);

        $files = $request->file('invoice_file_path');
        $pdf = PDFMergerFacade::init();
        foreach ($files as $file) {
            $pdf->addPDF($file->getPathName(), 'all');
        }
        $fileName = 'facture_' . $fourName . " " . $count = $count + 1 . ".pdf"; //. '.' . $file->getClientOriginalExtension();
        $pdf->merge();

        if (!$bord) {
            Storage::disk('facture')->put(Carbon::now()->toDateString() . '/' .  $fileName, $pdf->output());
            $filePath = Carbon::now()->toDateString() . '/' .  $fileName;
            $bord = new Bordereau();
            $bord->date_sent = Carbon::now();;
            $bord->folder = Carbon::now()->toDateString();
            $bord->status = 'En cours';
            $bord->reference = Str::random(8) . '/' . Carbon::now()->toDateString();
            $bord->save();
        } else {
            Storage::disk('facture')->put(Carbon::now()->toDateString() . '/' .  $fileName, $pdf->output());
            $filePath = Carbon::now()->toDateString() . '/' .  $fileName;
            $bord->date_sent = Carbon::now();
        }*/


        return response()->json([
            'success' => true,
            'message' => 'votre facture a été modifié avec succés',
            'data' => [],
        ]);
    }





























    function createInvoice(Request $request) //(création facture pour fournisseur ou agent bof "facture de type 3WM")
    {
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

        //if ($role_id === 3)
        //******** 3eme cas (ajouter un attribut)
        if (($role_id === 3) || ($role_id === 2 && $user = User::where('idFiscale', $request->input('id_fiscale'))->first())) {
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

        $messages = [
            'number.required' => 'Le numéro de facture est obligatoire.',
            'number.numeric' => 'Le numéro de facture doit être un nombre.',
            'currency.required' => 'La devise est obligatoire.',
            'currency.string' => 'La devise doit être une chaîne de caractères.',
            'currency.max' => 'La devise ne doit pas dépasser :3.',
            'amount.required' => 'Le montant est obligatoire.',
            'amount.numeric' => 'Le montant doit être un nombre.',
            'billing_date.required' => 'La date de facturation est obligatoire.',
            'billing_date.date_format' => 'La date de facturation doit être au format : Y-m-d.',
            'payment_period.required' => 'La période de paiement est obligatoire.',
            'objet_facture_id.integer' => 'L\'ID de l\'objet de facture doit être un entier.',
            'invoice_file_path.*.required' => 'Le fichier joint est obligatoire.',
            'invoice_file_path.*.file' => 'Le fichier joint doit être un fichier pdf.',
            'invoice_file_path.*.mimes' => 'Les fichier joints doiventt être de type : pdf',
            'invoice_file_path.*.max' => 'Le fichier joint ne doit pas dépasser : 100 MO',
        ];

        $validator = Validator::make($request->all(), [
            'organization' => 'string|max:255',
            'number' => 'required|numeric', //
            'invoice_name' => 'string|max:255',
            'currency' => 'required|string|max:3', //
            'billing_date' => 'required|date_format:Y-m-d', // à revoir //
            'amount' => 'required|numeric', //
            'payment_period' => 'required|max:255', //
            'objet_facture_id' => 'integer', //
            'pieces_jointes' => 'json', //changer//
            'invoice_file_path.*' => 'required|file|mimes:pdf|max:102400', //changer
        ], $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
                'data' => [],
            ]);
        }

        $fourName = User::where('idFiscale', $purOrder->four_idFiscale)->first()->name;

        $bord = Bordereau::whereDate('created_at', Carbon::today()->toDateString())->first();
        $count = ($bord ? Facture::where('borderau_id', $bord->id)->count() : 0);

        $files = $request->file('invoice_file_path');
        $pdf = PDFMergerFacade::init();
        foreach ($files as $file) {
            $pdf->addPDF($file->getPathName(), 'all');
        }
        $fileName = 'facture_' . $fourName . " " . $count = $count + 1 . ".pdf"; //. '.' . $file->getClientOriginalExtension();
        $pdf->merge();

        if (!$bord) {
            Storage::disk('facture')->put(Carbon::now()->toDateString() . '/' .  $fileName, $pdf->output());
            $filePath = Carbon::now()->toDateString() . '/' .  $fileName;
            $bord = new Bordereau();
            $bord->date_sent = Carbon::now();;
            $bord->folder = Carbon::now()->toDateString();
            $bord->status = 'En cours';
            $bord->reference = Str::random(8) . '/' . Carbon::now()->toDateString();
            $bord->save();
        } else {
            Storage::disk('facture')->put(Carbon::now()->toDateString() . '/' .  $fileName, $pdf->output());
            $filePath = Carbon::now()->toDateString() . '/' .  $fileName;
            $bord->date_sent = Carbon::now();
        }

        if ($role_id === 3) {
            Facture::create([
                'number' => $request->input('number'),
                'invoice_name' => $request->input('invoice_name'),
                'organization' => $request->input('organization'),
                'billing_date' => $request->input('billing_date'),
                'amount' => $request->input('amount'),
                'type_facture_id' => 1,
                'invoice_file_path' => $filePath,
                'reception_date' => Carbon::now(),
                'isArchived' => 0,
                'etat_id' => 1,
                'objet_facture_id' => $request->input('objet_facture_id'),
                'pieces_jointes' => json_decode($request->input('pieces_jointes'), true), //explode(',', $request->input('pieces_jointes')),
                'borderau_id' => $bord->id,
                'bon_de_commande_id' => $purOrder->id,
                'created_by' => $role->name,
                'fournisseur_id' => $user->id,
                'agent_bof_id' => null,
            ]);
            $purOrder->hasInvoice = 1;
            $purOrder->save();
        } elseif ($role_id === 2 && $fourExist = User::where('idFiscale', $request->input('id_fiscale'))->first()) {
            Facture::create([
                'number' => $request->input('number'),
                'invoice_name' => $request->input('invoice_name'),
                'organization' => $request->input('organization'),
                'billing_date' => $request->input('billing_date'),
                'amount' => $request->input('amount'),
                'type_facture_id' => 1,
                'invoice_file_path' => $filePath,
                'reception_date' => Carbon::now(),
                'isArchived' => 0,
                'etat_id' => 1,
                'objet_facture_id' => $request->input('objet_facture_id'),
                'pieces_jointes' => json_decode($request->input('pieces_jointes'), true), //explode(',', $request->input('pieces_jointes')),
                'borderau_id' => $bord->id,
                'bon_de_commande_id' => $purOrder->id,
                'created_by' => $role->name,
                'fournisseur_id' => $fourExist->id,
                'agent_bof_id' => null,
            ]);
            $purOrder->hasInvoice = 1;
            $purOrder->save();
        } else {
            Facture::create([
                'number' => $request->input('number'),
                'invoice_name' => $request->input('invoice_name'),
                'organization' => $request->input('organization'),
                'billing_date' => $request->input('billing_date'),
                'amount' => $request->input('amount'),
                'type_facture_id' => 1,
                'invoice_file_path' => $filePath,
                'reception_date' => Carbon::now(),
                'isArchived' => false,
                'etat_id' => 1,
                'objet_facture_id' => $request->input('objet_facture_id'),
                'pieces_jointes' =>  json_decode($request->input('pieces_jointes'), true), //explode(',', $request->input('pieces_jointes')),
                'borderau_id' => $bord->id,
                'bon_de_commande_id' => $purOrder->id,
                'created_by' => $role->name,
                'fournisseur_id' => null,
                'agent_bof_id' => $user->id,
            ]);
            $purOrder->hasInvoice = 1;
            $purOrder->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'La facture a été ajouté avec succés',
            'data' => [],
        ]);
    }
}
