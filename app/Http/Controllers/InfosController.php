<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class InfosController extends Controller
{
    function updateGeneralInfo(Request $request)
    {
        $user = JWTAuth::user();

        $messages = [
            'email.email' => 'L\'adresse email doit être une adresse email valide.',
            'email.unique' => 'Cette adresse email est déjà utilisée par un autre utilisateur.',
        ];

        $validator = Validator($request->all(), [
            'email' => 'email|string|max:255|unique:users,email,' . $user->id,
            'name' => 'string|max:255',
            'phone' => 'string|max:255',
        ], $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
                'data' => [],
            ]);
        }
        $user->update([
            'email' => $request->input('email'),
            'name' => $request->input('name'),
            'phone' => $request->input('phone'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Les informations de l\'utilisateur ont été mises à jour avec succès',
            'data' => [
                'user' => $user,
            ]
        ]);
    }


    function updateImage(Request $request)
    {
        $user = JWTAuth::user();


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
        $fileName = 'Photo_' . $user->id . '.' . $image->getClientOriginalExtension();
        $imagePath = $image->storeAs('profile/photos', $fileName, 'local');
        $user->image = $imagePath;
        $user->save();


        return response()->json([
            'success' => true,
            'message' => "L'image de l'utilisateur a été mise à jour avec succès",
        ]);
    }

    function updatePassword(Request $request)
    {
        $user = JWTAuth::user();

        /* juste pour le test coté front si tu vas utiliser les messages, on peut les ignorer et garder les messages par défaut*/
        $messages = [
            'oldPassword.required' => 'Le champ ancien mot de passe est requis',
            'newPassword.required' => 'Le champ nouveau mot de passe est requis',
            'newPassword.min' => 'Le nouveau mot de passe doit comporter au moins 8 caractéres',
            'confirmPassword.required' => 'Le champ confirmation du mot de passe est requis',
            'confirmPassword.same' => 'Le champ confirmation du mot de passe doit correspondre au nouveau mot de passe',
        ];

        $validator = Validator($request->all(), [
            'oldPassword' => 'required',
            'newPassword' => 'required|string|min:8',
            'confirmPassword' => 'required|same:newPassword',
        ], $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
                'data' => []
            ], 422);
        }

        $oldPassword = $request->input('oldPassword');
        $newPassword = $request->input('newPassword');

        if (!Hash::check($oldPassword, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Le mot de passe actuel que vous avez fourni ne correspond pas à celui enregistré',
                'data' => []
            ]);
        }

        $user->password = Hash::make($newPassword);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Votre mot de passe a été mis à jour avec succès',
            'data' => []
        ]);
    }
}
