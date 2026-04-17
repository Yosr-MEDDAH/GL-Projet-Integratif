<?php

namespace App\Services;

use App\Models\User;

class FactureCreationPolicy
{
    public static function validateActiveFournisseur(?int $fournisseurId): array
    {
        if (!$fournisseurId) {
            return ['allowed' => true, 'message' => ''];
        }

        $fournisseur = User::find($fournisseurId);
        if (!$fournisseur || (int) $fournisseur->role_id !== 3) {
            return [
                'allowed' => false,
                'message' => 'Fournisseur introuvable ou non autorise a soumettre des factures.',
            ];
        }

        if (!$fournisseur->isActive) {
            return [
                'allowed' => false,
                'message' => 'Votre compte fournisseur est inactif ou non approuve.',
            ];
        }

        return ['allowed' => true, 'message' => ''];
    }

    public static function assertActiveFournisseur(?int $fournisseurId): void
    {
        $result = self::validateActiveFournisseur($fournisseurId);
        if (!$result['allowed']) {
            throw new \DomainException($result['message']);
        }
    }
}
