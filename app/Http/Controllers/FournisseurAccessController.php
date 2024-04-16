<?php

namespace App\Http\Controllers;

use App\Models\Fournisseur;
use App\Models\FournisseursSansCompte;
use App\Models\User;
use Illuminate\Auth\Events\Validated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class FournisseurAccessController extends Controller
{
    function accessFournisseur(Request $request)
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        if ($role->id !== 2) {
            return response()->json([
                'success' => false,
                'message' => "vous n'avez pas d'autorisation",
                'data' => [],
            ]);
        }

        $validator = Validator::make($request->all(), [
            'idFiscale' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
                'data' => [],
            ]);
        }

        $four1 = FournisseursSansCompte::where('idFiscale', $request->input('idFiscale'))->first();
        $four2 = User::where('idFiscale', $request->input('idFiscale'))->first();

        if (!$four1 && $four2) {
            return response()->json([
                'success' => false,
                'message' => 'fournisseur posséde un compte',
                'data' => [],
            ]);
        }

        if (!$four1 && !$four2) {
            return response()->json([
                'success' => false,
                'message' => 'fournisseur n\'existe pas',
                'data' => [],
            ]);
        }

        if ($four1 && $four2) {
            return response()->json([
                'success' => false,
                'message' => "erreur, Le fournisseur existe dans les deux tables",
                'data' => [],
            ]);
        }

        $exist = User::where('email', $four1->email)->first();

        if ($exist) { // donner la possibilité pour agent bof de changer l'email s'il existe .... 
            return response()->json([
                'success' => false,
                'message' => "l'adresse email existe, s'il vous plait changer un autre adresse email",
                'data' => [],
            ]);
        }

        $password = $four1->generateRandomPassword();

        Fournisseur::create([
            'name' => $four1->name,
            'email' => $four1->email,
            'password' => Hash::make($password),
            'phone' => $four1->phone,
            'image' => "test/test",
            'isActive' => 0,
            'code_2FA' => null,
            "code_2fa_created_at" => null,
            'isTwoFactorEnabled' => 0,
            'role_id' => 3,
            'refresh_token' => null,
            'refreshToken_created_at' => null,
            'idErp' => $four1->idErp,
            'idFiscale' => $four1->idFiscale,
            'adress' => $four1->adress,
            'nationnalites' => $four1->nationnalites,
        ]);

        $four1->NotificationCredentials($four1->email, $password);
        $four1->delete();

        return response()->json([
            'success' => true,
            'message' => 'le fournisseur a été crée avec succés',
            'data' => [],
        ]);
    }


    function updateEmail(Request $request)
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        if ($role->id !== 2) {
            return response()->json([
                'success' => false,
                'message' => "vous n'avez pas d'autorisation",
                'data' => [],
            ]);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'email|string|max:255',
            'idFiscale' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
                'data' => [],
            ]);
        }

        $exist1 = User::where('email', $request->input('email'))->first();
        $exist2 = FournisseursSansCompte::where('email', $request->input('email'))->first();

        if ($exist1 || $exist2) {
            return response()->json([
                'success' => false,
                'message' => "l'adresse email existe, s'il vous plait changer un autre adresse email",
                'data' => [],
            ]);
        }

        $four1 = FournisseursSansCompte::where('idFiscale', $request->input('idFiscale'))->first();

        $four1->update([
            'email' => $request->email,
        ]);

        return response()->json([
            'success' => true,
            'message' => "l'adresse email du fournisseur a été changer avec succés",
            'data' => [],
        ]);
    }

    function getFournisseurSansCompte (Request $request) {
        
    }
}
