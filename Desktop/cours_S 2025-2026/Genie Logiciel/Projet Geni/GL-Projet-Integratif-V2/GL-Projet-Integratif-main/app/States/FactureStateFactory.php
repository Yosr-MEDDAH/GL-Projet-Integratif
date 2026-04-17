<?php

namespace App\States;

use App\Models\Facture;

class FactureStateFactory
{
    public static function resolve(Facture $facture): FactureStateInterface
    {
        if ($facture->etat_id === 3) {
            return new RejetéeState();
        }

        if ($facture->etat_id === 2 && $facture->validePar === 'Agent Trésorerie') {
            return new PayéeState();
        }

        return match ($facture->validePar) {
            null          => new SoumiseState(),
            'Agent Bof'   => new ValidéeBofState(),
            'Agent Ap'    => new ValidéeApState(),
            'Agent Fiscaliste' => new ValidéeFiscalisteState(),
            default       => new SoumiseState(),
        };
    }
}