<?php

namespace App\Http\Controllers;

use App\Models\FournisseursSansCompte;
use App\Models\User;
use Illuminate\Auth\Events\Validated;
use Illuminate\Http\Request;
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

        $validator = Validator::make($request->input('idFiscale'), [
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

        //if($four1 && $four2)


        
    }
}
