<?php

namespace App\Strategies;

use App\Models\Facture;

class ValidationContext
{
    private ValidationStrategyInterface $strategy;

    public function __construct(Facture $facture)
    {
        $this->strategy = $this->resolveStrategy($facture);
    }

    private function resolveStrategy(Facture $facture): ValidationStrategyInterface
    {
        $typeFacture = $facture->typeFacture()->first();
        $nom = $typeFacture ? $typeFacture->nom : '3WM';

        return match($nom) {
            'LC'   => new ValidationLCStrategy(),
            'Oper' => new ValidationOperStrategy(),
            default => new Validation3WMStrategy(),
        };
    }

    public function valider(Facture $facture, $user): array
    {
        return $this->strategy->valider($facture, $user);
    }

    public function rejeter(Facture $facture, $user, string $motif): array
    {
        return $this->strategy->rejeter($facture, $user, $motif);
    }
}