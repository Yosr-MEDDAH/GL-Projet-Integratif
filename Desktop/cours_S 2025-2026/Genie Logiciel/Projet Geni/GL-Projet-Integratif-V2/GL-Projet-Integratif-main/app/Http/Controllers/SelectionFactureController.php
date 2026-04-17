<?php

namespace App\Http\Controllers;

use App\Models\ObjetFacture;
use App\Models\PieceJointeFacture;
use Illuminate\Http\Request;

class SelectionFactureController extends Controller
{

    function getObjects(Request $request)
    {
        $objects = ObjetFacture::all();

        if (!$objects) {
            return response()->json([
                'success' => false,
                'message' => "Pas d'objet",
                'data' => [],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'voila les objets',
            'data' => [
                'objets' => $objects,
            ]
        ]);
    }

    function getPJs(Request $request)
    {
        $piecesJ = PieceJointeFacture::all();

        if (!$piecesJ) {
            return response()->json([
                'success' => false,
                'message' => "Pas de piece jointe",
                'data' => [],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'voila les pieces jointes',
            'data' => [
                'objets' => $piecesJ,
            ]
        ]);
    }
}
