<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;


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
        if ($user->isEnable) {
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

        $token = JWTAuth::fromUser($user);
        $role = $user->role()->first();
        return response()->json([
            'success' => true,
            'message' => 'Welcome User',
            'data' =>   [
                //is it right to return the whole user object?
                'user' => $user,
                'user_role' => ['role_id' => $role->id ,'role_name' => $role->name] ,
                'JWTtoken' => $token
            ]

        ])->cookie('auth_jwt', $token, 60);
    }



    function logout(Request $request)
    {
        try {
            $token = JWTAuth::getToken();

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                    'data' =>   []
                ], 401);
            }

            JwtAuth::invalidate($token);

            return response()->json([
                'sucess' => true,
                'message' => 'you are logged out',
                'data' =>   []
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'sucess' => false,
                'message' => 'Something went wrong',
                'data' =>   []
            ]);
        }
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
