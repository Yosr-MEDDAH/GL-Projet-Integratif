<?php

namespace App\Http\Controllers;

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
            'phone' => [
                'numeric',
                'digits_between:8,15',
            ],
            'role_id' => 'required|in:2,4,5,6',
            'password' => 'sometimes|min:8', // s'affiche seulement si l'admin choisi de donner lui meme le password d'agent
            'type_facture_ids' => 'sometimes', //  s'affiche seulement si l'admin choisi de créer un agent hors bof
        ], $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
                'data' => [],
            ]);
        }

        $userData = [
            'email' => $request->input('email'),
            'name' => $request->input('name'),
            'phone' => $request->input('phone'),
            'role_id' => $request->input('role_id'),
            'image' => 'test.jpg',
            'isActive' => 1,
            'isTwoFactorEnabled' => '0',
        ];

        if (($request->has('password'))) {
            $userData['password'] = Hash::make($request->input('password'));
        } /*else {
            $password = User::generateRandomPassword();
            $userData['password'] = Hash::make($password);
            User::sendCredentialsNotification($userData['email'], $password);
        }*/

        $user = User::create($userData);

        if ($request->has('type_facture_ids')) {
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
}
