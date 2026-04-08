<?php

namespace App\States;


use App\Models\Facture;

class SoumiseState implements FactureStateInterface
{

    public function valider(Facture $facture, string $agentRole): void
    {
        if ($agentRole !== 'Agent Bof') {
            throw new \Exception("Seul un Agent Bof peut valider une facture soumise.");
        }
        $facture->etat_id = 2;
        $facture->validePar = 'Agent Bof';
        $facture->save();
    }

    public function rejeter(Facture $facture, string $agentRole): void
    {
        if ($agentRole !== 'Agent Bof') {
            throw new \Exception("Seul un Agent Bof peut rejeter une facture soumise.");
        }
        $facture->etat_id = 3;
        $facture->validePar = 'Agent Bof';
        $facture->save();
    }

    public function getNomEtat(): string
    {
        return 'Soumise';
    }

    public function getProchainAgent(): ?string
    {
        return 'Agent Bof';
    }
}
