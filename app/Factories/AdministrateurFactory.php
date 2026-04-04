<?php

namespace App\Factories;

use App\Models\User;

class AdministrateurFactory extends UserFactory
{
    protected function getRoleId(): int
    {
        return 1;
    }

    /**
     * Crée un Administrateur
     * Pas de champs spécifiques supplémentaires
     */
    public function createUser(array $data): User
    {
        $user = $this->buildBaseUser($data);
        $user->save();

        // Notifier l'administrateur de ses credentials
        $user->NotificationCredentialsAgent($user->email, $user->plainPassword);

        return $user;
    }
}