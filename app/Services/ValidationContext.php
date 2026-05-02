<?php

namespace App\Services;

use App\Interfaces\ValideurInterface;
use App\Interfaces\RejeteurInterface;
use App\Models\Facture;

class ValidationContext
{
    private ValideurInterface $valideur;
    private RejeteurInterface $rejeteur;

    public function __construct(ValideurInterface $valideur, RejeteurInterface $rejeteur)
    {
        $this->valideur = $valideur;
        $this->rejeteur = $rejeteur;
    }

    public function setValideur(ValideurInterface $valideur): void
    {
        $this->valideur = $valideur;
    }

    public function setRejeteur(RejeteurInterface $rejeteur): void
    {
        $this->rejeteur = $rejeteur;
    }

    public function executerValidation(Facture $facture, int $userId): array
    {
        return $this->valideur->valider($facture, $userId);
    }

    public function executerRejet(Facture $facture, int $userId, string $motif): array
    {
        return $this->rejeteur->rejeter($facture, $userId, $motif);
    }
}