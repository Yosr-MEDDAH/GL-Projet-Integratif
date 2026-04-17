<?php

namespace App\States;

use App\Models\Facture;

class ValideeFiscalisteState implements FactureStateInterface
{
    public function valider(Facture $facture, string $agentRole): void
    {
        if ($agentRole !== 'Agent Trésorerie') {
            throw new \Exception("Seul un Agent Trésorerie peut valider à cette étape.");
        }
        $facture->validePar = 'Agent Trésorerie';
        $facture->save();
    }

    public function rejeter(Facture $facture, string $agentRole): void
    {
        if ($agentRole !== 'Agent Trésorerie') {
            throw new \Exception("Seul un Agent Trésorerie peut rejeter à cette étape.");
        }
        $facture->etat_id = 3;
        $facture->validePar = 'Agent Trésorerie';
        $facture->save();
    }

    public function getNomEtat(): string
    {
        return 'Validée Fiscaliste';
    }

    public function getProchainAgent(): ?string
    {
        return 'Agent Trésorerie';
    }
}