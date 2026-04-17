<?php

namespace App\Http\Controllers;

use App\Exports\InvoicesProviderExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Tymon\JWTAuth\Facades\JWTAuth;

class FactureExportController extends Controller
{
    function export()
    {
        // **************************** pour tester *********************
        /*$excelFile = Excel::download(new InvoicesProviderExport(7), 'Mes Factures.xlsx');
        return $excelFile;*/


        // exportation en excel des factures (fournisseur) 
        $user = JWTAuth::user();
        $role = $user->role()->first();

        if ($role->id === 3) {
            return Excel::download(new InvoicesProviderExport($user->id), 'Mes Factures.xlsx');
        }

        // reste pour les autres acteurs
    }
}
