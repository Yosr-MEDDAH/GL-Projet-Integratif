<?php

namespace App\Interfaces;

use App\Models\Facture;

interface ValideurInterface
{
    /**
     * Valide une facture selon les règles métier du rôle courant.
     *
     * @param  Facture $facture  La facture à valider
     * @param  int     $userId   L'identifiant de l'agent qui valide
     * @return array             ['success' => bool, 'message' => string]
     */
    public function valider(Facture $facture, int $userId): array;
}