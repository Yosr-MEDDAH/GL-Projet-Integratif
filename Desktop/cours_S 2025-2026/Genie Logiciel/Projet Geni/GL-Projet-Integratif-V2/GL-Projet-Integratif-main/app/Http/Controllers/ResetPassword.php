<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ResetPassword extends Controller
{


    function verifyEmail(Request $request)
    {
        /* juste pour le test coté front si tu vas utiliser les messages, on peut les ignorer et garder les messages par défaut générés par Validator*/
        $messages = [
            'email.required' => 'L\'adresse e-mail est requise.',
            'email.string' => 'L\'adresse e-mail doit être une chaîne de caractères.',
            'email.email' => 'L\'adresse e-mail doit être une adresse e-mail valide.',
            'email.exists' => "L'adresse e-mail fournie n'existe pas",
        ];


        $validator = Validator($request->all(), [
            'email' => 'required|string|email|exists:users,email',
        ], $messages);


        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
                'data' => [],
            ]);
        }


        $user = User::where('email', $request->input('email'))->first();
        $resetToken = $user->generateRandomResetToken();


        $record = DB::table('password_reset_tokens')
            ->where('email', $request->input('email'))
            ->first();

        if (!$record) {
            DB::table('password_reset_tokens')->insert([
                'email' => $request->input('email'),
                'token' => $resetToken,
                'created_at' => Carbon::now(),
            ]);
        } else {
            DB::table('password_reset_tokens')->update([
                'token' => $resetToken,
                'created_at' => Carbon::now(),
            ]);
        }


        $user->sendResetPasswordNotification($resetToken);


        return response()->json([
            'success' => true,
            'message' => 'Un lien de réinitialisation de mot de passe a été envoyé par e-mail',
            'data' => [],
        ]);
    }









    function resetPassword(Request $request)
    {
        /* juste pour le test coté front si tu vas utiliser les messages, on peut les ignorer et garder les messages par défaut générés par Validator*/
        $messages = [
            'resetToken.exists' => 'Le token de réinitialisation de mot de passe est invalide.',
            'newPassword.required' => 'Le champ du nouveau mot de passe est requis.',
            'newPassword.min' => 'Le nouveau mot de passe doit avoir au moins :min caractères.',
            'confirmPassword.required' => 'Le champ de confirmation du mot de passe est requis.',
            'confirmPassword.same' => 'Le champ de confirmation du mot de passe doit correspondre au nouveau mot de passe.',
        ];


        $validator = Validator($request->all(), [
            'resetToken' => 'required|string|min:60|max:60|exists:password_reset_tokens,token', // exists : teste si le resetToken existe dans la table ou non sans nécessiter if ...
            'newPassword' => 'required|string|min:8',
            'confirmPassword' => 'required|same:newPassword',
        ], $messages);


        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
                'data' => [],
            ]);
        }



        $createdAt = DB::table('password_reset_tokens')->where('token', $request->input('resetToken'))->first();
        $user = User::where('email', $createdAt->email)->first();
        if (!$user) { /* tester l'existabce de user meme aprés envoi du lien à son email pour cloturer toutes les possibilités */
            return response()->json([
                'success' => false,
                'message' => "l'utilisateur concerné n'existe pas",
            ]);
        }


        $expiration = Carbon::parse($createdAt->created_at)->addMinutes(10);
        if (!Carbon::now()->lt($expiration)) {
            DB::table('password_reset_tokens')->where('email', $user->email)->delete(); //supprimer record si le token est expiré 
            return response()->json([
                'success' => false,
                'message' => "Le lien de de réinitialisation de mot de passe fourni est expiré",
                'data' => [],
            ]);
        }


        $user->update([
            'password' => Hash::make($request->input('newPassword')),
        ]);
        DB::table('password_reset_tokens')->where('email', $user->email)->delete();


        return response()->json([
            'success' => true,
            'message' => "Mot de passe réinitialisé avec succès",
            'data' => [],
        ]);
    }
}


/* j'ai utilisé Query Builder (DB::), pour ne pas créer un model car 'cette enregistrement du reset token ne nécessite pas un logique supplémentaire comme une methode' */