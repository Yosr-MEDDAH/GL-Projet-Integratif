<?php

namespace App\Strategies;

use App\Models\Facture;

interface ValidationStrategyResolverInterface
{
    /**
     * Resolve a validation strategy for a given facture without conditional branching.
     */
    public function resolve(Facture $facture): ValidationStrategyInterface;
}
