<?php

namespace App\Strategies;

use App\Models\Facture;

interface ValidationStrategyInterface
{
    /**
     * Valider une facture selon la stratégie définie
     */
    public function valider(Facture $facture, $user): array;

    /**
     * Rejeter une facture avec un motif
     */
    public function rejeter(Facture $facture, $user, string $motif): array;
}