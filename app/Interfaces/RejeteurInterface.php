<?php

namespace App\Interfaces;

use App\Models\Facture;

interface RejeteurInterface
{
    /**
     * Rejette une facture avec un motif.
     *
     * @param  Facture $facture  La facture à rejeter
     * @param  int     $userId   L'identifiant de l'agent qui rejette
     * @param  string  $motif    La raison du rejet
     * @return array             ['success' => bool, 'message' => string]
     */
    public function rejeter(Facture $facture, int $userId, string $motif): array;
}