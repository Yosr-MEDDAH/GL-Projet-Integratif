<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ResetPassword extends Controller
{
    function verifyEmail(Request $request)
    {

        $messages = [
            'email.exists' => "L'adresse e-mail fournie n'existe pas dans notre base de données.",
        ];

        $validator = Validator($request->all(), [
            'email' => 'required|string|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->fails(),
                'data' => [],
            ]);
        }
    }
}
