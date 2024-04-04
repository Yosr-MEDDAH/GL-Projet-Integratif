<?php

namespace Database\Seeders;

use App\Models\PieceJointeFacture;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PieceJointeFactureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $donneesPiecesJointes = [
            "PV DE RÉCEPTION",
            "BON DE COMMANDE",
            "BON DE LIVRAISON",
            "COPIE DE CONTRAT",
            "APPEL À LA FACTURATION",
            "RELEVÉ CONSOMMATION",
            "CIN",
        ];


        foreach ($donneesPiecesJointes as $nomPieceJointe) {
            $pieceJointe = new PieceJointeFacture();
            $pieceJointe->namePJ = $nomPieceJointe;
            $pieceJointe->createdBy = null;
            $pieceJointe->created_at = now();
            $pieceJointe->save();
        }
    }
}
