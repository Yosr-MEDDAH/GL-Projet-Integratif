<?php

namespace App\Interfaces;

use App\Models\Facture;


interface ValidationStrategyInterface
{
    /**
     * Valide une facture selon les règles métier du rôle courant.
     *
     * @param  Facture $facture  La facture à valider
     * @param  int     $userId   L'identifiant de l'agent qui valide
     * @return array             ['success' => bool, 'message' => string]
     */
    public function valider(Facture $facture, int $userId): array;

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