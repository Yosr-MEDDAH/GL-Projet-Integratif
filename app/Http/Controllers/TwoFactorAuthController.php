<?php

namespace App\Http\Controllers;

use App\Models\User;
use GuzzleHttp\Psr7\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class TwoFactorAuthController extends Controller
{
    function enable(Request $request)
    {
        $user = $request->user();
        $user->enableTwoFactorAuth();
        return response()->json([
            'message' => 'La double authentification a été activée avec succès'
        ]);
    }

    function disable(Request $request)
    {
        $user = $request->user();
        $user->disableTwoFactorAuth();
        return response()->json([
            'message' => 'La double authentification a été désactivée avec succès'
        ]);
    }

    function verify(Request $request)
    {
        $validator = Validator($request->all(), [
            'code' => 'required|min:6|max:6',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ]);
        }

        $user = User::where('code_2FA', $request->input('code'))->first();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Le code de vérification fourni est invalide. Veuillez réessayer avec un code valide',
            ], 422);
        }

        $user->code_2fa = null;
        $token = JWTAuth::fromUser($user);


        return response()->json([
            'status' => true,
            'message' => 'Le code de vérification fourni est valide',
            'token' => $token,
            'user' => $user,
        ]);
    }
}
