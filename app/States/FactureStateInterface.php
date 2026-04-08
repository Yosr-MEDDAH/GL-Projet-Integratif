<?php

namespace App\States;

use App\Models\Facture;

interface FactureStateInterface
{
    public function valider(Facture $facture, string $agentRole): void;
    public function rejeter(Facture $facture, string $agentRole): void;
    public function getNomEtat(): string;
    public function getProchainAgent(): ?string;
}
