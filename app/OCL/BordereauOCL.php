<?php

namespace App\OCL;

use App\Models\Bordereau;

class BordereauOCL
{
    /**
     * Contrainte OCL :
     * context Bordereau
     * inv memeTypeFacture:
     * self.factures->collect(f | f.typeFacture)->asSet()->size() = 1
     *
     * Un bordereau ne peut contenir que des factures du même type.
     */
    public function verifierMemeTypeFacture(Bordereau $bordereau): bool
    {
        $factures = $bordereau->factures()->get();

        if ($factures->isEmpty()) {
            return true;
        }

        $typesFactures = $factures
            ->pluck('type_facture_id')
            ->unique();

        return $typesFactures->count() === 1;
    }

    /**
     * Vérifier avant d'ajouter une facture au bordereau
     */
    public function peutAjouterFacture(Bordereau $bordereau, int $typeFactureId): bool
    {
        $factures = $bordereau->factures()->get();

        if ($factures->isEmpty()) {
            return true;
        }

        $typeExistant = $factures->first()->type_facture_id;

        return $typeExistant === $typeFactureId;
    }
}