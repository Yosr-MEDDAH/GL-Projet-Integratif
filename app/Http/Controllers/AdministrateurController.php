<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
}
