<?php

namespace App\Factories;

use App\Models\Fournisseur;
use App\Models\User;

class FournisseurFactory extends UserFactory
{
    protected function getRoleId(): int
    {
        return 3;
    }

    /**
     * Crée un Fournisseur avec ses champs spécifiques :
     * idFiscale, idErp, adress, nationnalites
     */
    public function createUser(array $data): User
    {
        $user = $this->buildBaseUser($data);

        $user->idFiscale     = $data['idFiscale']     ?? null;
        $user->idErp         = $data['idErp']         ?? null;
        $user->adress        = $data['adress']        ?? null;
        $user->nationnalites = $data['nationnalites'] ?? null;

        $user->save();

        // Notifier le fournisseur de ses credentials
        $user->NotificationCredentialsAgent($user->email, $user->plainPassword);

        return $user;
    }
}