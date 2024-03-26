<?php

namespace App\Http\Controllers;

use App\Models\User;
use GuzzleHttp\Psr7\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class TwoFactorAuthController extends Controller
{
    function toggle_2fa(Request $request)
    {
        $bool = $request->input('toggle');
        $user = JWTAuth::user();
        $user->toggle($bool);
        return response()->json([
            'success' => true,
            'message' => $bool ? 'Authentification à deux facteurs activée' : 'Authentification à deux facteurs désactivée'
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
                'success' => false,
                'message' => 'Le code de vérification fourni est invalide. Veuillez réessayer avec un code valide',
            ], 422);
        }
        $expiration = Carbon::parse($user->code_2fa_created_at)->addMinutes(5);

        if (!Carbon::now()->lt($expiration)) {
            $user->code_2FA = null;
            $user->code_2fa_created_at = null;
            $user->save();
            return response()->json([
                'success' => false,
                'message' => "Le code de vérification fourni est expiré. Veuillez réessayer avec un code valide"
            ]);
        }

        $user->code_2FA = null;
        $user->code_2fa_created_at = null;
        $user->save();


        $user->refresh_token = $user->generateRandomRefreshToken();
        $user->refreshToken_created_at = Carbon::now();
        $user->save();
        $role = $user->role()->first();
        $token = JWTAuth::claims(['role' => $role])->fromUser($user);

        return response()->json([
            'success' => true,
            'message' => 'Le code de vérification fourni est valide',
            'data' => [
                'user' => $user,
                'user_role' => ['role_id' => $role->id, 'role_name' => $role->name],
                'JWTtoken' => $token,
                'refreshToken' => $user->refresh_token,
            ]
        ])->cookie('auth_jwt_2fa', $token, 60);
    }
}
