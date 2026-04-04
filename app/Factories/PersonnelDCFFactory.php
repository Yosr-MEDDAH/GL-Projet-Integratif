<?php

namespace App\Factories;

use App\Models\User;
use InvalidArgumentException;

class PersonnelDCFFactory extends UserFactory
{
    // Rôles acceptés pour le personnel DCF
    private const ROLES_DCF = [4, 5, 6];

    private int $roleId;

    public function __construct(int $roleId)
    {
        if (!in_array($roleId, self::ROLES_DCF)) {
            throw new InvalidArgumentException(
                "role_id {$roleId} invalide pour PersonnelDCFFactory. Valeurs acceptées : " . implode(', ', self::ROLES_DCF)
            );
        }
        $this->roleId = $roleId;
    }

    protected function getRoleId(): int
    {
        return $this->roleId;
    }

    /**
     * Crée un membre du personnel DCF :
     *   role_id=4 → Agent AP (Après Paiement)
     *   role_id=5 → Agent Fiscaliste
     *   role_id=6 → Agent Trésorerie
     *
     * Champs spécifiques : direction, type_facture_ids
     */
    public function createUser(array $data): User
    {
        $user = $this->buildBaseUser($data);

        $user->direction = $data['direction'] ?? null;

        if (!empty($data['type_facture_ids'])) {
            $user->type_facture_ids = $data['type_facture_ids'];
        }

        $user->save();

        // Envoyer les credentials par email
        $user->NotificationCredentialsAgent($user->email, $user->plainPassword);

        return $user;
    }
}