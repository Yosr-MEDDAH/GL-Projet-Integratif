<?php

namespace App\Services;

use App\Interfaces\ValidationStrategyInterface;
use App\Models\Etapes;
use App\Models\Facture;
use Carbon\Carbon;


class AgentBofValidationStrategy implements ValidationStrategyInterface
{
    public function valider(Facture $facture, int $userId): array
    {
        // L'Agent BOF valide les factures en attente (etat_id = 1)
        if ($facture->etat_id !== 1) {
            return [
                'success' => false,
                'message' => 'La facture n\'est pas en attente de validation BOF.',
            ];
        }

        $facture->etat_id    = 2;
        $facture->validePar  = 'Agent Bof';
        $facture->save();

        Etapes::create([
            'facture_id'       => $facture->id,
            'traitParId'       => $userId,
            'traitParRoleNom'  => 'Agent Bof',
            'action'           => 'Validée',
            'created_at'       => Carbon::now(),
            'updated_at'       => Carbon::now(),
        ]);

        return [
            'success' => true,
            'message' => 'Facture validée par Agent BOF.',
        ];
    }

    public function rejeter(Facture $facture, int $userId, string $motif): array
    {
        $facture->etat_id   = 3;
        $facture->validePar = 'Agent Bof';
        $facture->save();

        Etapes::create([
            'facture_id'       => $facture->id,
            'traitParId'       => $userId,
            'traitParRoleNom'  => 'Agent Bof',
            'action'           => 'Rejetée',
            'motif'            => $motif,
            'created_at'       => Carbon::now(),
            'updated_at'       => Carbon::now(),
        ]);

        return [
            'success' => true,
            'message' => 'Facture rejetée par Agent BOF.',
        ];
    }
}