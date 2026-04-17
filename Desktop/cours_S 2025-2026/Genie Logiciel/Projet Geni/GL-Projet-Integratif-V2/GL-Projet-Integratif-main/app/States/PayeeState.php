<?php

namespace App\States;

use App\Models\Facture;

class PayeeState implements FactureStateInterface
{
    public function valider(Facture $facture, string $agentRole): void
    {
        throw new \Exception("La facture est déjà payée, aucune action possible.");
    }

    public function rejeter(Facture $facture, string $agentRole): void
    {
        throw new \Exception("La facture est déjà payée, aucune action possible.");
    }

    public function getNomEtat(): string
    {
        return 'Payée';
    }

    public function getProchainAgent(): ?string
    {
        return null;
    }
}