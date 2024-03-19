<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;


class AuthController extends Controller
{
    //success , message , data

    function login(Request $request)
    {
        $validator = Validator($request->all(), [
            'email' => 'required|email|string',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            response()->json([
                'success' => false, 
                'message' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        
        if (! JWTAuth::attempt($data)) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
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
                'status' => true,
                'message' => 'Un code de vérification a été envoyé à votre appareil enregistré. Veuillez vérifier et saisir le code pour compléter le processus de connexion.',
            ]);
        }

        $token = JWTAuth::fromUser($user) ;
        return response()->json([
            'status' => true, 
            'user' => $user,
            'token' => $token,
        ])->cookie('auth_jwt', $token, 60);
        
    }



    function logout (Request $request) {
        try {
            $token = JWTAuth::getToken();

            if(!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], 401);
            }

            JwtAuth::invalidate($token);

            return response()->json([
                'sucess' => true,
                'message' => 'you are logged out',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something Wrong'
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
