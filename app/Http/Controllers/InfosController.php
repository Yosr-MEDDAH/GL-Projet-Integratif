<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class InfosController extends Controller
{
    function updateGeneralInfo(Request $request)
    {
        try {

            $user = JWTAuth::user();

            $messages = [
                'email.email' => 'L\'adresse email doit être une adresse email valide.',
                'email.unique' => 'Cette adresse email est déjà utilisée par un autre utilisateur.',
                'email.regex' => 'L\'adresse email doit être une adresse email valide.',
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
                'name' => 'string|max:255',
                'phone' => 'numeric|digits_between:8,15',
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
                'data' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [],
            ]);
        }
    }







    function updateImage(Request $request)
    {
        try {

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
            $imagePath = $image->storeAs("users/" . $user->id . "/userUploads/img", $fileName, 'image');
            $user->image = $imagePath;
            $user->save();


            return response()->json([
                'success' => true,
                'message' => "L'image de l'utilisateur a été mise à jour avec succès",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [],
            ]);
        }
    }






    function updatePassword(Request $request)
    {
        try {

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
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [],
            ]);
        }
    }






    function getUser(Request $request)
    {
        try {

            $user = JWTAuth::user();

            $userData = [
                "email" => $user->email,
                "name" => $user->name,
                "phone" => $user->phone,
                "image" => $user->image,
                "accountActive" => $user->isActive,
                "isTwoFactorEnabled" => $user->isTwoFactorEnabled,
                "role_id" => $user->role_id,
                "role_name" => $user->role->name,
            ];

            return response()->json([
                'success' => true,
                'message' => "Les informations de l'utilisateur authentifié :",
                'data' => $userData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [],
            ]);
        }
    }



    function getImage(Request $request, $userId, $imageName)
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

        $filePath = "users/" . $userId . "/userUploads/img/" . $imageName;
        $imageFile = User::where('image', $filePath)->where('id', $user->id)->first(); // pour etre true => il faut le fichier recherché doit etre existe avec le meme path et doit etre id = $user->id
        if (!$imageFile) {
            return response()->json([
                'success' => false,
                'message' => "le fichier  n'existe pas", //BD
                'data' => [],
            ]);
        }
        if (Storage::disk('image')->exists($filePath)) {
            $fileContents = Storage::disk('image')->get($filePath);
            $extension = pathinfo($filePath, PATHINFO_EXTENSION);
            $contentType = 'image/' . $extension;

            return response()->make($fileContents, 200, [
                'Content-Type' => $contentType
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => "le fichier n'existe pas",
                'data' => []
            ]); //disk
        }
    }




    function toggleNotification(Request $request)
    {
        $bool = $request->input('toggleNotif');
        $user = JWTAuth::user();
        $user->toggleNotif($bool);
        return response()->json([
            'success' => true,
            'message' => $bool ? 'Notification activée' : 'Notification désactivée'
        ]);
    }
}
