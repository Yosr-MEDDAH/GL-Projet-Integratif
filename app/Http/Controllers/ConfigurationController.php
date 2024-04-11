<?php

namespace App\Http\Controllers;

use App\Models\Mailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class ConfigurationController extends Controller
{
    function editMailer(Request $request)
    {
        $user = JWTAuth::user();

        $role = $user->role()->first();

        if ($role->id !== 1) {
            return response()->json([
                'success' => false,
                'message' => "vous n'avez pas l'autorisation",
                'data' => [],
            ]);
        }

        $validator = Validator::make($request->all(), [
            'transport' => 'required|string',
            'host' => 'required|string',
            'port' => 'required|integer',
            'encryption' => 'required|string',
            'username' => 'required|string',
            'password' => 'required|string',
            'timeout' => 'nullable|integer',
            'local_domain' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
                'data' => [],
            ]);
        }

        $mailer = Mailer::first();

        if (!$mailer) {
            return response()->json([
                'success' => false,
                'message' => "aucune configuration enregistrée",
                'data' => [],
            ]);
        }

        $mailer->update([
            'transport' => $request->input('transport'),
            'host' => $request->input('host'),
            'port' => $request->input('port'),
            'encryption' => $request->input('encryption'),
            'username' => $request->input('username'),
            'password' => $request->input('password'),
            'timeout' => $request->input('timeout'),
            'local_domain' => $request->input('local_domain'),
        ]);

        Config::set('mail.transport', $request->input('transport'));
        Config::set('mail.host', $request->input('host'));
        Config::set('mail.port', $request->input('port'));
        Config::set('mail.encryption', $request->input('encryption'));
        Config::set('mail.username', $request->input('username'));
        Config::set('mail.password', $request->input('password'));
        Config::set('mail.timeout', $request->input('timeout'));
        Config::set('mail.local_domain', $request->input('local_domain'));


        return response()->json([
            'success' => true,
            'message' => "la configuration a été modifié avec succés",
            'data' => [],
        ]);
    }
}
