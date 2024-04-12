<?php

namespace App\Http\Controllers;

use App\Models\Bordereau;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class BordoreauConsultation extends Controller
{
    function getAllBordoreau(Request $request)
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        if ($role->id !== 2  && $role->id !== 1) {
            return response()->json([
                'success' => false,
                'message' => "vous n'avez pas d'autorisation",
                'data' => [],
            ]);
        }

        $page = $request->query('page', 1);
        $nb = $request->query('nb', 10);

        $bordereaux = Bordereau::select('date_sent', 'folder', 'status', 'nature', 'reference')->paginate($nb, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'message' => "tous les bordereaux",
            'data' => [
                'totalPages' => $bordereaux->lastPage(),
                'bordereaux' => $bordereaux
            ]
        ]);
    }
}
