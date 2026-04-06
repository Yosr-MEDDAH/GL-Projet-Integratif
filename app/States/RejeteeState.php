<?php

namespace App\States;

use App\Models\Facture;

class RejetéeState implements FactureStateInterface
{
    public function valider(Facture $facture, string $agentRole): void
    {
        throw new \Exception("La facture est rejetée, aucune action possible.");
    }

    public function rejeter(Facture $facture, string $agentRole): void
    {
        throw new \Exception("La facture est déjà rejetée.");
    }

    public function getNomEtat(): string
    {
        return 'Rejetée';
    }

    public function getProchainAgent(): ?string
    {
        return null;
    }
}