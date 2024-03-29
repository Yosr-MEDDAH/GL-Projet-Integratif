<?php

namespace App\Http\Controllers;

use App\Models\Facture;
use App\Models\Fournisseur;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;
use Dotenv\Validator as DotenvValidator;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\JWT;

class AuthController extends Controller
{


    function login(Request $request)
    {
        $validator = Validator($request->all(), [
            'email' => 'required|email|string',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
                'data' => []
            ], 422);
        }

        $data = $validator->validated();

        if (!JWTAuth::attempt($data)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
                'data' => []
            ], 401);
        }

        $user = User::where('email', $request->input('email'))->first();
        if ($user->isTwoFactorEnabled) {
            $code = $user->generateRandomCode();
            $user->code_2FA = $code;
            $user->code_2fa_created_at = Carbon::now();
            $user->save();
            $user->sendTwoFactorCodeEmailNotification($code, $user->name);
            return response()->json([
                'success' => true,
                'message' => 'Un code de vérification a été envoyé à votre appareil enregistré. Veuillez vérifier et saisir le code pour compléter le processus de connexion.',
                'data' => []
            ]);
        }

        $user->isActive = 1;
        $user->refresh_token = $user->generateRandomRefreshToken();
        $user->refreshToken_created_at = Carbon::now();
        $user->save();
        $role = $user->role()->first();
        $token = JWTAuth::claims(['role' => $role])->fromUser($user);
        return response()->json([
            'success' => true,
            'message' => 'Welcome User',
            'data' =>   [
                //is it right to return the whole user object?
                'user' => $user,
                'user_role' => ['role_id' => $role->id, 'role_name' => $role->name],
                'JWTtoken' => $token,
                'refreshToken' => $user->refresh_token,
            ]

        ])->cookie('auth_jwt', $token, 60);
    }



    function logout(Request $request)
    {
        try {
            $token = JWTAuth::getToken();
            $user = JWTAuth::user();
            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                    'data' =>   []
                ], 401);
            }

            JwtAuth::invalidate($token);
            $user->isActive = 0;
            return response()->json([
                'success' => true,
                'message' => 'you are logged out',
                'data' =>   []
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'data' =>   []
            ]);
        }
    }

    function refreshToken(Request $request)
    {
        $validator = Validator($request->all(), [
            'refreshToken' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
                'data' => []
            ], 422);
        }

        $token = JWTAuth::getToken();

        $refreshToken = $request->input('refreshToken');

        $user = User::where('refresh_token', $refreshToken)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Refresh Token fourni est invalide',
            ], 422);
        }
        $expiration = Carbon::parse($user->refreshToken_created_at)->addDays(7);

        if (!Carbon::now()->lt($expiration)) {
            $user->refresh_token = null;
            $user->refreshToken_created_at = null;
            $user->save();
            return response()->json([
                'success' => false,
                'message' => "Le code de vérification fourni est expiré."
            ], 401);
        }

        $newJWTtoken = JWTAuth::refresh();
        return response()->json([
            'success' => true,
            'message' => 'Nouveau token d\'accès généré avec succès',
            'data' => [
                'newJWTtoken' => $newJWTtoken,
            ]
        ]);
    }

















    /*function logout(Request $request) //cookie
    {
        try {
            if (!$request->bearerToken()) {
                return response()->json(['error' => 'User not authenticated']);
            }
            $user = JWTAuth::user($request->bearerToken());

            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }
            JWTAuth::invalidate($request->bearerToken());

            return response()->json([
                'message' => $user->name . ' you are logged out'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }*/
}
