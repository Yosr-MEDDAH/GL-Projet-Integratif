<?php

namespace App\Factories;

use App\Models\User;

class AgentBofFactory extends UserFactory
{
    protected function getRoleId(): int
    {
        return 2;
    }

    /**
     * Crée un Agent BOF (Bureau des Opérations Financières)
     * Champs spécifiques : direction, type_facture_ids
     */
    public function createUser(array $data): User
    {
        $user = $this->buildBaseUser($data);

        $user->direction = $data['direction'] ?? null;

        // Types de factures que l'agent BOF peut traiter
        if (!empty($data['type_facture_ids'])) {
            $user->type_facture_ids = $data['type_facture_ids'];
        }

        $user->save();

        // Envoyer les credentials par email
        $user->NotificationCredentialsAgent($user->email, $user->plainPassword);

        return $user;
    }
}