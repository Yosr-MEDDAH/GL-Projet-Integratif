<?php

namespace App\Http\Controllers;

use App\Models\BonDeCommande;
use App\Models\Bordereau;
use App\Models\Etat;
use App\Models\Facture;
use App\Models\Fournisseur;
use App\Models\Notification;
use App\Models\ObjetFacture;
use App\Models\User;
use App\Services\FactureCreationPolicy;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;
use Webklex\PDFMerger\Facades\PDFMergerFacade;
use App\Services\GestionDocumentsFacade;
use App\Services\NotificationService;

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
            'num_commande' => 'numeric', // changer nom _
            'id_fiscale' => 'string|max:255',
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

        $messages = [
            'number.numeric' => 'Le numéro doit être un nombre.',
            'currency.string' => 'La devise doit être une chaîne de caractères.',
            'currency.max' => 'La devise ne doit pas dépasser 3 caractères.',
            'billing_date.date_format' => 'La date de facturation doit être au format YYYY-MM-DD.',
            'amount.numeric' => 'Le montant doit être un nombre.',
            'payment_period.max' => 'La période de paiement ne doit pas dépasser 255 caractères.',
            'objet_facture_id.integer' => 'L\'ID de l\'objet de la facture doit être un entier.',
            //'pieces_jointes.json' => 'Les pièces jointes doivent être au format JSON.',
            // 'invoice_file_path.*.required' => 'Chaque fichier de facture est requis.',
            // 'invoice_file_path.*.file' => 'Chaque fichier doit être un fichier valide.',
            // 'invoice_file_path.*.mimes' => 'Chaque fichier doit être au format PDF.',
            // 'invoice_file_path.*.max' => 'Chaque fichier ne doit pas dépasser 100 Mo.',
        ];

        //ajouter messages spécifiques ou pas ?? ********** ///////
        $validator = Validator::make($request->all(), [
            'number' => 'numeric',
            'currency' => 'string|max:3',
            'billing_date' => 'date_format:Y-m-d', // à revoir 
            'amount' => 'numeric',
            'payment_period' => 'max:255',
            'objet_facture_id' => 'integer', // annuler ou non 
            //'pieces_jointes' => 'json', //changer
            // 'invoice_file_path.*' => 'required|file|mimes:pdf|max:102400', //changer
        ], $messages);


        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
                'data' => [],
            ]);
        }

        /*if (Facture::where('number', $request->input('number'))->first()) {
            return response()->json([
                'success' => false,
                'message' => "vérifier le numero de la facture", // question le numero de la facture est unique ?? 
                'data' => [],
            ]);
        }*/

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
                'objet_facture_id' => $request->input('objet_facture_id'),
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
                'objet_facture_id' => $request->input('objet_facture_id'),
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
            'invoice_file_path.required' => 'Le fichier joint est obligatoire.',
            'invoice_file_path.array' => 'Le champ du fichier joint doit être un tableau.',
            'invoice_file_path.*.required' => 'Le fichier joint est obligatoire.',
            'invoice_file_path.*.file' => 'Le fichier joint doit être un fichier.',
            'invoice_file_path.*.mimes' => 'Les fichiers joints doivent être de type : pdf.',
            'invoice_file_path.*.max' => 'Le fichier joint ne doit pas dépasser : 100 MO.',
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
            'invoice_file_path' => 'required|array',
            'invoice_file_path.*' => 'required|file|mimes:pdf|max:102400', //changer
            //'id_fiscale' => 'required|string|max:255',
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
            $bord->nature = "3WM";
            $bord->save();
        } else {
            Storage::disk('facture')->put(Carbon::now()->toDateString() . '/' .  $fileName, $pdf->output());
            $filePath = Carbon::now()->toDateString() . '/' .  $fileName;
            $bord->date_sent = Carbon::now();
        }
        $fac = new Facture();
        if ($role_id === 3) {
            // Enforce OCL: fournisseur must be active before creating a facture.
            $guard = FactureCreationPolicy::validateActiveFournisseur($user->id);
            if (!$guard['allowed']) {
                return response()->json([
                    'success' => false,
                    'message' => $guard['message'],
                    'data' => [],
                ], 403);
            }

            /*
            $fac = Facture::create([
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
            */

            $fournisseur = Fournisseur::find($user->id);
            $fac = $fournisseur->createFacture([
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
                'pieces_jointes' => json_decode($request->input('pieces_jointes'), true),
                'borderau_id' => $bord->id,
                'bon_de_commande_id' => $purOrder->id,
                'created_by' => $role->name,
                'agent_bof_id' => null,
            ]);
            $purOrder->hasInvoice = 1;
            $purOrder->save();
        } elseif ($role_id === 2 && $fourExist = User::where('idFiscale', $request->input('id_fiscale'))->first()) {
            // Enforce OCL: fournisseur must be active before creating a facture.
            $guard = FactureCreationPolicy::validateActiveFournisseur($fourExist->id);
            if (!$guard['allowed']) {
                return response()->json([
                    'success' => false,
                    'message' => $guard['message'],
                    'data' => [],
                ], 403);
            }

            /*
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
            */

            $fournisseur = Fournisseur::find($fourExist->id);
            $fournisseur->createFacture([
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
                'pieces_jointes' => json_decode($request->input('pieces_jointes'), true),
                'borderau_id' => $bord->id,
                'bon_de_commande_id' => $purOrder->id,
                'created_by' => $role->name,
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
    $notifService = NotificationService::getInstance();

    $notifService->notifierParRole(
        roleId: 2,
        message: 'Une nouvelle facture a été ajoutée par un fournisseur',
        type: 'FactureEnvoyee',
        titre: 'Une nouvelle facture a été envoyée',
        extra: [
            'num_facture' => $request->input('number'),
            'id_facture' => $fac->id,
            'nom_creator' => $user->name,
        ]
    );

    return response()->json([
        'success' => true,
        'message' => 'La facture a été ajoutée avec succès',
        'data' => [],
    ]);
}
    }





    function createInvoiceLC(Request $request) //(création facture pour fournisseur ou agent bof "facture de type 3WM")
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
            'organization.string' => 'L\'organisation doit être une chaîne de caractères.',
            'organization.max' => 'L\'organisation ne doit pas dépasser :255 caractères.',
            'number.required' => 'Le numéro de facture est obligatoire.',
            'number.numeric' => 'Le numéro de facture doit être un nombre.',
            'invoice_name.string' => 'Le nom de la facture doit être une chaîne de caractères.',
            'invoice_name.max' => 'Le nom de la facture ne doit pas dépasser :255 caractères.',
            'currency.required' => 'La devise est obligatoire.',
            'currency.string' => 'La devise doit être une chaîne de caractères.',
            'currency.max' => 'La devise ne doit pas dépasser :3 caractères.',
            'billing_date.required' => 'La date de facturation est obligatoire.',
            'billing_date.date_format' => 'La date de facturation doit être au format : Y-m-d.',
            'amount.required' => 'Le montant est obligatoire.',
            'amount.numeric' => 'Le montant doit être un nombre.',
            'payment_period.required' => 'La période de paiement est obligatoire.',
            'payment_period.max' => 'La période de paiement ne doit pas dépasser :255 caractères.',
            'objet_facture_id.integer' => 'L\'ID de l\'objet de facture doit être un entier.',
            'pieces_jointes.json' => 'Les pièces jointes doivent être au format JSON.',
            'invoice_file_path.required' => 'Le fichier joint est obligatoire.',
            'invoice_file_path.array' => 'Le champ du fichier joint doit être un tableau.',
            'invoice_file_path.*.required' => 'Le fichier joint est obligatoire.',
            'invoice_file_path.*.file' => 'Le fichier joint doit être un fichier.',
            'invoice_file_path.*.mimes' => 'Les fichiers joints doivent être de type : pdf.',
            'invoice_file_path.*.max' => 'Le fichier joint ne doit pas dépasser : 100 MO.',
            'numOp.required' => 'L\'ordre de paiement est obligatoire.',
            'numOp.string' => 'L\'ordre de paiement doit être une chaîne de caractères.',
            'idFiscale.required' => 'L\'ID fiscale est obligatoire.',
            'idFiscale.string' => 'L\'ID fiscale doit être une chaîne de caractères.',
        ];

        $validator = Validator::make($request->all(), [
            'organization' => 'string|max:255',
            'number' => 'required|numeric',
            'invoice_name' => 'string|max:255',
            'currency' => 'required|string|max:3',
            'billing_date' => 'required|date_format:Y-m-d',
            'amount' => 'required|numeric',
            'payment_period' => 'required|max:255',
            'objet_facture_id' => 'integer',
            'pieces_jointes' => 'json',
            'invoice_file_path' => 'required|array',
            'invoice_file_path.*' => 'file|mimes:pdf|max:102400',
            'numOp' => 'required|string|max:255', // Validation pour numPo
            'idFiscale' => 'required|string|max:255', // Validation pour idFiscale
        ], $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
                'data' => [],
            ]);
        }

        //$fourName = User::where('idFiscale', $request->input('idFiscale'))->first()->name;

        $bord = Bordereau::whereDate('created_at', Carbon::today()->toDateString())->first();
        $count = ($bord ? Facture::where('borderau_id', $bord->id)->count() : 0);

        $files = $request->file('invoice_file_path');
        $pdf = PDFMergerFacade::init();
        foreach ($files as $file) {
            $pdf->addPDF($file->getPathName(), 'all');
        }
        $fileName = 'facture_' . $request->input('number') . " " . $count = $count + 1 . ".pdf"; //. '.' . $file->getClientOriginalExtension();
        $pdf->merge();

        if (!$bord) {
            Storage::disk('facture')->put(Carbon::now()->toDateString() . '/' .  $fileName, $pdf->output());
            $filePath = Carbon::now()->toDateString() . '/' .  $fileName;
            $bord = new Bordereau();
            $bord->date_sent = Carbon::now();;
            $bord->folder = Carbon::now()->toDateString();
            $bord->status = 'En cours';
            $bord->reference = Str::random(8) . '/' . Carbon::now()->toDateString();
            $bord->nature = "Lettre De Credit";
            $bord->save();
        } else {
            Storage::disk('facture')->put(Carbon::now()->toDateString() . '/' .  $fileName, $pdf->output());
            $filePath = Carbon::now()->toDateString() . '/' .  $fileName;
            $bord->date_sent = Carbon::now();
        }

        if ($role_id === 3) {
            // Enforce OCL: fournisseur must be active before creating a facture.
            $guard = FactureCreationPolicy::validateActiveFournisseur($user->id);
            if (!$guard['allowed']) {
                return response()->json([
                    'success' => false,
                    'message' => $guard['message'],
                    'data' => [],
                ], 403);
            }
        }

        Facture::create([
            'number' => $request->input('number'),
            'invoice_name' => $request->input('invoice_name'),
            'organization' => $request->input('organization'),
            'billing_date' => $request->input('billing_date'),
            'amount' => $request->input('amount'),
            'type_facture_id' => 2,
            'invoice_file_path' => $filePath,
            'reception_date' => Carbon::now(),
            'isArchived' => false,
            'etat_id' => 1,
            'objet_facture_id' => $request->input('objet_facture_id'),
            'pieces_jointes' =>  json_decode($request->input('pieces_jointes'), true), //explode(',', $request->input('pieces_jointes')),
            'borderau_id' => $bord->id,
            'created_by' => $role->name,
            'fournisseur_id' => null,
            'agent_bof_id' => $user->id,
            'numOp' => $request->input('numOp'),
            'idFiscale' => $request->input('idFiscale'),
            'currency' => $request->input('currency'),
        ]);


        return response()->json([
            'success' => true,
            'message' => 'La facture a été ajoutée avec succès',
            'data' => [],
        ]);
    }



    function createInvoiceOper(Request $request) //(création facture pour fournisseur ou agent bof "facture de type 3WM")
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
            'structureOrd.string' => 'La structure ordinatrice doit être une chaîne de caractères.',
            'structureOrd.required' => 'La structure ordinatrice est obligatoire.',
            'organization.string' => 'L\'organisation doit être une chaîne de caractères.',
            'organization.max' => 'L\'organisation ne doit pas dépasser :255 caractères.',
            'number.required' => 'Le numéro de facture est obligatoire.',
            'number.numeric' => 'Le numéro de facture doit être un nombre.',
            'invoice_name.string' => 'Le nom de la facture doit être une chaîne de caractères.',
            'invoice_name.max' => 'Le nom de la facture ne doit pas dépasser :255 caractères.',
            'currency.required' => 'La devise est obligatoire.',
            'currency.string' => 'La devise doit être une chaîne de caractères.',
            'currency.max' => 'La devise ne doit pas dépasser :3 caractères.',
            'billing_date.required' => 'La date de facturation est obligatoire.',
            'billing_date.date_format' => 'La date de facturation doit être au format : Y-m-d.',
            'amount.required' => 'Le montant est obligatoire.',
            'amount.numeric' => 'Le montant doit être un nombre.',
            'payment_period.required' => 'La période de paiement est obligatoire.',
            'payment_period.max' => 'La période de paiement ne doit pas dépasser :255 caractères.',
            'objet_facture_id.integer' => 'L\'ID de l\'objet de facture doit être un entier.',
            'pieces_jointes.json' => 'Les pièces jointes doivent être au format JSON.',
            'invoice_file_path.required' => 'Le fichier joint est obligatoire.',
            'invoice_file_path.array' => 'Le champ du fichier joint doit être un tableau.',
            'invoice_file_path.*.required' => 'Le fichier joint est obligatoire.',
            'invoice_file_path.*.file' => 'Le fichier joint doit être un fichier.',
            'invoice_file_path.*.mimes' => 'Les fichiers joints doivent être de type : pdf.',
            'invoice_file_path.*.max' => 'Le fichier joint ne doit pas dépasser : 100 MO.',
            'numOp.required' => 'L\'ordre de paiement est obligatoire.',
            'numOp.string' => 'L\'ordre de paiement doit être une chaîne de caractères.',
            'idFiscale.required' => 'L\'ID fiscale est obligatoire.',
            'idFiscale.string' => 'L\'ID fiscale doit être une chaîne de caractères.',
        ];

        $validator = Validator::make($request->all(), [
            'organization' => 'string|max:255',
            'number' => 'required|numeric',
            'invoice_name' => 'string|max:255',
            'currency' => 'required|string|max:3',
            'billing_date' => 'required|date_format:Y-m-d',
            'amount' => 'required|numeric',
            'payment_period' => 'required|max:255',
            'objet_facture_id' => 'integer',
            'pieces_jointes' => 'json',
            'invoice_file_path' => 'required|array',
            'invoice_file_path.*' => 'file|mimes:pdf|max:102400',
            'numOp' => 'required|string|max:255', // Validation pour numPo
            'idFiscale' => 'required|string|max:255', // Validation pour idFiscale
            'structureOrd' => 'required|string',
        ], $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
                'data' => [],
            ]);
        }

        //$fourName = User::where('idFiscale', $request->input('idFiscale'))->first()->name;

        $bord = Bordereau::whereDate('created_at', Carbon::today()->toDateString())->first();
        $count = ($bord ? Facture::where('borderau_id', $bord->id)->count() : 0);

        $files = $request->file('invoice_file_path');
        $pdf = PDFMergerFacade::init();
        foreach ($files as $file) {
            $pdf->addPDF($file->getPathName(), 'all');
        }
        $fileName = 'facture_' . $request->input('number') . " " . $count = $count + 1 . ".pdf"; //. '.' . $file->getClientOriginalExtension();
        $pdf->merge();

        if (!$bord) {
            Storage::disk('facture')->put(Carbon::now()->toDateString() . '/' .  $fileName, $pdf->output());
            $filePath = Carbon::now()->toDateString() . '/' .  $fileName;
            $bord = new Bordereau();
            $bord->date_sent = Carbon::now();;
            $bord->folder = Carbon::now()->toDateString();
            $bord->status = 'En cours';
            $bord->reference = Str::random(8) . '/' . Carbon::now()->toDateString();
            $bord->nature = "Operateur";
            $bord->save();
        } else {
            Storage::disk('facture')->put(Carbon::now()->toDateString() . '/' .  $fileName, $pdf->output());
            $filePath = Carbon::now()->toDateString() . '/' .  $fileName;
            $bord->date_sent = Carbon::now();
        }

        if ($role_id === 3) {
            // Enforce OCL: fournisseur must be active before creating a facture.
            $guard = FactureCreationPolicy::validateActiveFournisseur($user->id);
            if (!$guard['allowed']) {
                return response()->json([
                    'success' => false,
                    'message' => $guard['message'],
                    'data' => [],
                ], 403);
            }
        }

        Facture::create([
            'number' => $request->input('number'),
            'invoice_name' => $request->input('invoice_name'),
            'organization' => $request->input('organization'),
            'billing_date' => $request->input('billing_date'),
            'amount' => $request->input('amount'),
            'type_facture_id' => 8,
            'invoice_file_path' => $filePath,
            'reception_date' => Carbon::now(),
            'isArchived' => false,
            'etat_id' => 1,
            'objet_facture_id' => $request->input('objet_facture_id'),
            'pieces_jointes' =>  json_decode($request->input('pieces_jointes'), true), //explode(',', $request->input('pieces_jointes')),
            'borderau_id' => $bord->id,
            'created_by' => $role->name,
            'fournisseur_id' => null,
            'agent_bof_id' => $user->id,
            'numOp' => $request->input('numOp'),
            'idFiscale' => $request->input('idFiscale'),
            'currency' => $request->input('currency'),
            'structureOrd' =>  $request->input('structureOrd'),
        ]);


        return response()->json([
            'success' => true,
            'message' => 'La facture a été ajoutée avec succès',
            'data' => [],
        ]);
    }





    function updateInvoiceLC(Request $request)
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();
        $role_id = $role->id;

        // Vérifiez si l'utilisateur a le droit d'accès (seul un agent BOF, role_id = 2)
        if ($role_id !== 2) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette ressource',
                'data' => []
            ], 403);
        }

        // Rechercher la facture par ID
        $facture = Facture::find($request->input('id'));
        if (!$facture) {
            return response()->json([
                'success' => false,
                'message' => "La facture n'existe pas",
                'data' => [],
            ]);
        }

        if ($facture->agent_bof_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => "Vous n'êtes pas autorisé à modifier cette facture",
                'data' => [],
            ]);
        }

        // Ajouter des règles de validation et des messages personnalisés
        $messages = [
            //'organization.string' => 'L\'organisation doit être une chaîne de caractères.',
            //'organization.max' => 'L\'organisation ne doit pas dépasser 255 caractères.',
            'number.required' => 'Le numéro de facture est obligatoire.',
            'number.numeric' => 'Le numéro de facture doit être un nombre.',
            'invoice_name.string' => 'Le nom de la facture doit être une chaîne de caractères.',
            'invoice_name.max' => 'Le nom de la facture ne doit pas dépasser 255 caractères.',
            'currency.required' => 'La devise est obligatoire.',
            'currency.string' => 'La devise doit être une chaîne de caractères.',
            'currency.max' => 'La devise ne doit pas dépasser 3 caractères.',
            'billing_date.required' => 'La date de facturation est obligatoire.',
            'billing_date.date_format' => 'La date de facturation doit être au format Y-m-d.',
            'amount.required' => 'Le montant est obligatoire.',
            'amount.numeric' => 'Le montant doit être un nombre.',
            'payment_period.required' => 'La période de paiement est obligatoire.',
            'payment_period.max' => 'La période de paiement ne doit pas dépasser 255 caractères.',
            'objet_facture_id.integer' => 'L\'ID de l\'objet de facture doit être un entier.',
            //'pieces_jointes.json' => 'Les pièces jointes doivent être au format JSON.',
            'numOp.required' => 'L\'ordre de paiement est obligatoire.',
            'numOp.string' => 'L\'ordre de paiement doit être une chaîne de caractères.',
            'idFiscale.required' => 'L\'ID fiscale est obligatoire.',
            'idFiscale.string' => 'L\'ID fiscale doit être une chaîne de caractères.',
        ];

        $validator = Validator::make($request->all(), [
            //'organization' => 'string|max:255',
            'number' => 'required|numeric',
            'invoice_name' => 'string|max:255',
            'currency' => 'required|string|max:3',
            'billing_date' => 'required|date_format:Y-m-d',
            'amount' => 'required|numeric',
            'payment_period' => 'required|max:255',
            'objet_facture_id' => 'integer',
            //'pieces_jointes' => 'json',
            'numOp' => 'required|string|max:255',
            'idFiscale' => 'required|string|max:255',
        ], $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
                'data' => [],
            ]);
        }

        // Mettre à jour la facture avec les nouvelles informations
        $facture->update([
            'number' => $request->input('number'),
            'invoice_name' => $request->input('invoice_name'),
            //'organization' => $request->input('organization'),
            'billing_date' => $request->input('billing_date'),
            'amount' => $request->input('amount'),
            'type_facture_id' => 2,
            'reception_date' => Carbon::now(),
            'isArchived' => false,
            'etat_id' => 1,
            'objet_facture_id' => $request->input('objet_facture_id'),
            //'pieces_jointes' => json_decode($request->input('pieces_jointes'), true),
            'created_by' => $role->name,
            'fournisseur_id' => null,
            'agent_bof_id' => $user->id,
            'numOp' => $request->input('numOp'),
            'idFiscale' => $request->input('idFiscale'),
            'currency' => $request->input('currency'),
        ]);



        return response()->json([
            'success' => true,
            'message' => 'La facture a été mise à jour avec succès',
            'data' => [],
        ]);
    }









    function updateInvoiceOper(Request $request)
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();
        $role_id = $role->id;

        // Vérifiez si l'utilisateur a le droit d'accès (seul un agent BOF, role_id = 2 ou fournisseur, role_id = 3)
        if ($role_id !== 3 && $role_id !== 2) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette ressource',
                'data' => []
            ], 403);
        }

        // Rechercher la facture par ID
        $facture = Facture::find($request->input('id'));
        if (!$facture) {
            return response()->json([
                'success' => false,
                'message' => "La facture n'existe pas",
                'data' => [],
            ]);
        }

        // Vérifier si l'utilisateur a le droit de modifier cette facture
        if ($role_id === 2 && $facture->agent_bof_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => "Vous n'êtes pas autorisé à modifier cette facture",
                'data' => [],
            ]);
        } elseif ($role_id === 3 && $facture->fournisseur_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => "Vous n'êtes pas autorisé à modifier cette facture",
                'data' => [],
            ]);
        }

        // Ajouter des règles de validation et des messages personnalisés
        $messages = [
            'structureOrd.string' => 'La structure ordinatrice doit être une chaîne de caractères.',
            'structureOrd.required' => 'La structure ordinatrice est obligatoire.',
            //'organization.string' => 'L\'organisation doit être une chaîne de caractères.',
            //'organization.max' => 'L\'organisation ne doit pas dépasser 255 caractères.',
            'number.required' => 'Le numéro de facture est obligatoire.',
            'number.numeric' => 'Le numéro de facture doit être un nombre.',
            'invoice_name.string' => 'Le nom de la facture doit être une chaîne de caractères.',
            'invoice_name.max' => 'Le nom de la facture ne doit pas dépasser 255 caractères.',
            'currency.required' => 'La devise est obligatoire.',
            'currency.string' => 'La devise doit être une chaîne de caractères.',
            'currency.max' => 'La devise ne doit pas dépasser 3 caractères.',
            'billing_date.required' => 'La date de facturation est obligatoire.',
            'billing_date.date_format' => 'La date de facturation doit être au format Y-m-d.',
            'amount.required' => 'Le montant est obligatoire.',
            'amount.numeric' => 'Le montant doit être un nombre.',
            'payment_period.required' => 'La période de paiement est obligatoire.',
            'payment_period.max' => 'La période de paiement ne doit pas dépasser 255 caractères.',
            'objet_facture_id.integer' => 'L\'ID de l\'objet de facture doit être un entier.',
            'pieces_jointes.json' => 'Les pièces jointes doivent être au format JSON.',
            'numOp.required' => 'L\'ordre de paiement est obligatoire.',
            'numOp.string' => 'L\'ordre de paiement doit être une chaîne de caractères.',
            'idFiscale.required' => 'L\'ID fiscale est obligatoire.',
            'idFiscale.string' => 'L\'ID fiscale doit être une chaîne de caractères.',
        ];

        $validator = Validator::make($request->all(), [
            //'organization' => 'string|max:255',
            'number' => 'required|numeric',
            'invoice_name' => 'string|max:255',
            'currency' => 'required|string|max:3',
            'billing_date' => 'required|date_format:Y-m-d',
            'amount' => 'required|numeric',
            'payment_period' => 'required|max:255',
            'objet_facture_id' => 'integer',
            'pieces_jointes' => 'json',
            'numOp' => 'required|string|max:255',
            'idFiscale' => 'required|string|max:255',
            'structureOrd' => 'required|string',
        ], $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
                'data' => [],
            ]);
        }

        // Mettre à jour la facture avec les nouvelles informations
        $facture->update([
            'number' => $request->input('number'),
            'invoice_name' => $request->input('invoice_name'),
            //'organization' => $request->input('organization'),
            'billing_date' => $request->input('billing_date'),
            'amount' => $request->input('amount'),
            'type_facture_id' => 8,
            'reception_date' => Carbon::now(),
            'isArchived' => false,
            'etat_id' => 1,
            'objet_facture_id' => $request->input('objet_facture_id'),
            //'pieces_jointes' => json_decode($request->input('pieces_jointes'), true),
            'created_by' => $role->name,
            'fournisseur_id' => null,
            'agent_bof_id' => $user->id,
            'numOp' => $request->input('numOp'),
            'idFiscale' => $request->input('idFiscale'),
            'currency' => $request->input('currency'),
            'structureOrd' => $request->input('structureOrd'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'La facture a été mise à jour avec succès',
            'data' => [],
        ]);
    }
    /**
     * Supprimer une facture via la Facade
     */
    public function supprimerFactureFacade(Request $request)
    {
        $facade = new GestionDocumentsFacade();
        $result = $facade->supprimerFacture($request);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data'    => []
        ], $result['code']);
    }

    /**
     * Créer une facture via la Facade
     */
    public function creerFactureFacade(Request $request)
    {
        $facade = new GestionDocumentsFacade();
        $result = $facade->creerFacture($request);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data'    => []
        ], $result['code']);
    }
}

