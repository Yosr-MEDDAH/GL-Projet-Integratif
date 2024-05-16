<?php

namespace App\Http\Controllers;

use App\Models\BonDeCommande;
use App\Models\FournisseursSansCompte;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdministrateurController extends Controller
{
    public function storeDefaultProfilePicture(Request $request)
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        if ($role->id !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette ressource',
                'data' => []
            ], 403); // 403 accés refusé
        }
        if (!$request->hasFile('image')) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune image téléchargée',
                'data' => []
            ], 400);
        }


        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);


        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ]);
        }

        $image = $request->file('image');
        $fileName = "default." . $image->getClientOriginalExtension();
        $image->storeAs('settings/users/default/profile_picture', $fileName, 'image');

        return response()->json([
            'success' => true,
            'message' => "L'image par defaut du nouvel utilisateur a été mise à jour avec succès",
        ]);
    }





    function createAgent(Request $request)
    {

        $user = JWTAuth::user();
        $role = $user->role()->first();

        if ($role->id !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette ressource',
                'data' => []
            ], 403); // 403 accés refusé
        }

        $messages = [
            'email.required' => 'Le champ email est requis.',
            'email.email' => 'L\'adresse email doit être une adresse email valide.',
            'email.string' => 'Le champ email doit être une chaîne de caractères.',
            'email.max' => 'L\'adresse email ne peut pas dépasser 255 caractères.',
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            'email.regex' => 'Le format de l\'adresse email est invalide.',
            'name.string' => 'Le nom doit être une chaîne de caractères.',
            'name.max' => 'Le nom ne peut pas dépasser 30 caractères.',
            'phone.numeric' => 'Le numéro de téléphone doit être un nombre.',
            'phone.digits_between' => 'Le numéro de téléphone doit avoir entre 8 et 15 chiffres.',
            'role_id.required' => 'Le champ rôle est requis.',
            'password.required' => 'Le champ mot de passe est requis.',
            'password.min' => 'Le mot de passe doit avoir au moins :8 caractères.',
            'role_id.in' => 'Le champ rôle doit être un Agent',
        ];

        $validator = Validator::make($request->all(), [
            'email' => [
                'required',
                'email',
                'string',
                'max:255',
                'unique:users,email,' . $user->id,
                'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            ],
            'name' => [
                'string',
                'max:30',
            ],
            'role_id' => 'required|in:2,4,5,6',
            'type_facture_ids' => 'nullable', //  s'affiche seulement si l'admin choisi de créer un agent hors bof
        ], $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
                'data' => [],
            ]);
        }
        // ajouter envoi d'email
        $userData = [
            'email' => $request->input('email'),
            'name' => $request->input('name'),
            'role_id' => $request->input('role_id'),
            'image' => 'test.jpg',
            'isActive' => 1,
            'isTwoFactorEnabled' => '0',
        ];

        if (($request->has('password'))) {
            $userData['password'] = Hash::make($request->input('password'));
        }

        $user = User::create($userData);

        if ($request->has('type_facture_ids') && ($request->input('type_facture_ids') !== null)) {
            $user->type_facture_ids = $request->input('type_facture_ids');
            $user->save();
        }
        return response()->json([
            'success' => true,
            'message' => "L'agent a été créé avec succès",
            'data' => [
                'userCredentials' => ['email' => $user->email, 'password' => $request->input('password', $password ?? null)]
            ]
        ]);
    }





    function ajoutFournisseurs(Request $request)
    {

        $fournisseurs_sans_compte = $request->input('fournisseurs');


        $rules = [
            '*.name' => 'required|string|max:255',
            '*.email' => [
                'nullable',
                'email',
                'string',
                'max:255',
                'unique:fournisseurs_sans_comptes,email',
                'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            ],
            '*.phone' => 'required|string|max:20',
            '*.idErp' => 'nullable|integer',
            '*.idFiscale' => 'nullable|string|max:50',
            '*.adress' => 'nullable|string|max:255',
            '*.nationnalites' => 'nullable|string|max:255',
        ];

        $validator = Validator::make($fournisseurs_sans_compte, $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation des fournisseurs.',
                'errors' => $validator->errors(),
            ]);
        }

        foreach ($fournisseurs_sans_compte as $fournisseurData) {
            FournisseursSansCompte::create([
                'name' => $fournisseurData['name'],
                'email' => $fournisseurData['email'],
                'phone' => $fournisseurData['phone'],
                'idErp' => $fournisseurData['idErp'],
                'idFiscale' => $fournisseurData['idFiscale'],
                'adress' => $fournisseurData['adress'],
                'nationnalites' => $fournisseurData['nationnalites'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Fournisseurs importés avec succès depuis le système global.'
        ]);
    }


    public function ajoutBonDeCommande(Request $request)
    {
        $bons_de_commande = $request->input('bons_de_commande');

        $rules = [
            '*.num_commande' => 'required|integer',
            '*.created_by' => 'nullable|string|max:255',
            '*.idErp' => 'nullable|integer',
            '*.delai_paiement' => 'nullable|string|max:255',
            '*.four_idFiscale' => 'nullable|string|max:50',
        ];

        $validator = Validator::make($bons_de_commande, $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation des bons de commande.',
                'errors' => $validator->errors(),
            ]);
        }

        foreach ($bons_de_commande as $bonDeCommandeData) {
            BonDeCommande::create([
                'num_commande' => $bonDeCommandeData['num_commande'],
                'created_by' => $bonDeCommandeData['created_by'],
                'idErp' => $bonDeCommandeData['idErp'],
                'delai_paiement' => $bonDeCommandeData['delai_paiement'],
                'hasInvoice' => false,
                'four_idFiscale' => $bonDeCommandeData['four_idFiscale'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Bons de commande importés avec succès.'
        ]);
    }
}
