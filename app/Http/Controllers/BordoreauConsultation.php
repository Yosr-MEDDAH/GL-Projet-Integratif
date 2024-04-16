<?php

namespace App\Http\Controllers;

use App\Models\Bordereau;
use App\Models\Facture;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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

        $bordoreaux = Bordereau::select('date_sent', 'folder', 'status', 'nature', 'reference')->orderBy('created_at', 'desc')->paginate($nb, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'message' => "tous les bordereaux",
            'data' => [
                'totalPages' => $bordoreaux->lastPage(),
                'bordereaux' => $bordoreaux->items(),
            ]
        ]);
    }


    function getBordoreauListFacture(Request $request)
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

        $bordoreau = Bordereau::find($request->input('id'));
        if (!$bordoreau) {
            return response()->json([
                'success' => false,
                'message' => 'le bordoreau n \'existe pas',
                'data' => [],
            ]);
        }
        $page = $request->query('page', 1);
        $nb = $request->query('nb', 10);
        $listFacture = Facture::where('borderau_id', $request->input('id'))->orderBy('created_at', 'desc')->paginate($nb, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'message' => "les factures trouvées dans le bordoreau spécifié",
            'data' => [
                'totalPages' => $listFacture->lastPage(),
                'factures' => $listFacture->items(),
            ]
        ]);
    }
}
