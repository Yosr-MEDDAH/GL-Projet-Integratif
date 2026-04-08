<?php

namespace App\States;

use App\Models\Facture;

class ValideeBofState implements FactureStateInterface
{
    public function valider(Facture $facture, string $agentRole): void
    {
        if ($agentRole !== 'Agent Ap') {
            throw new \Exception("Seul un Agent Ap peut valider à cette étape.");
        }
        $facture->validePar = 'Agent Ap';
        $facture->save();
    }

    public function rejeter(Facture $facture, string $agentRole): void
    {
        if ($agentRole !== 'Agent Ap') {
            throw new \Exception("Seul un Agent Ap peut rejeter à cette étape.");
        }
        $facture->etat_id = 3;
        $facture->validePar = 'Agent Ap';
        $facture->save();
    }

    public function getNomEtat(): string
    {
        return 'Validée BOF';
    }

    public function getProchainAgent(): ?string
    {
        return 'Agent Ap';
    }
}
