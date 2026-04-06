<?php

namespace App\States;

use App\Models\Facture;

class ValideeApState implements FactureStateInterface
{
    public function valider(Facture $facture, string $agentRole): void
    {
        if ($agentRole !== 'Agent Fiscaliste') {
            throw new \Exception("Seul un Agent Fiscaliste peut valider à cette étape.");
        }
        $facture->validePar = 'Agent Fiscaliste';
        $facture->save();
    }

    public function rejeter(Facture $facture, string $agentRole): void
    {
        if ($agentRole !== 'Agent Fiscaliste') {
            throw new \Exception("Seul un Agent Fiscaliste peut rejeter à cette étape.");
        }
        $facture->etat_id = 3;
        $facture->validePar = 'Agent Fiscaliste';
        $facture->save();
    }

    public function getNomEtat(): string
    {
        return 'Validée AP';
    }

    public function getProchainAgent(): ?string
    {
        return 'Agent Fiscaliste';
    }
}