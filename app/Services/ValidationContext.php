<?php

namespace App\Services;

use App\Interfaces\ValidationStrategyInterface;
use App\Models\Facture;

class ValidationContext
{
    private ValidationStrategyInterface $strategy;

    public function __construct(ValidationStrategyInterface $strategy)
    {
        $this->strategy = $strategy;
    }

    public function setStrategy(ValidationStrategyInterface $strategy): void
    {
        $this->strategy = $strategy;
    }

    public function executerValidation(Facture $facture, int $userId): array
    {
        return $this->strategy->valider($facture, $userId);
    }

    public function executerRejet(Facture $facture, int $userId, string $motif): array
    {
        return $this->strategy->rejeter($facture, $userId, $motif);
    }
}