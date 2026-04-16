<?php

namespace App\Services;

use App\Interfaces\UtilisateurFactoryInterface;
use App\Models\User;
use Illuminate\Support\Facades\Hash;


class UtilisateurFactory implements UtilisateurFactoryInterface
{
    /**
     * Crée et persiste un utilisateur à partir des données fournies.
     *
     * @param  array $data  Données validées (name, email, password, role_id, idFiscale, ...)
     * @return User
     */
    public function creer(array $data): User
    {
        return User::create([
            'name'                       => $data['name'],
            'email'                      => $data['email'],
            'password'                   => Hash::make($data['password']),
            'role_id'                    => $data['role_id'],
            'idFiscale'                  => $data['idFiscale'] ?? null,
            'isActive'                   => $data['isActive'] ?? 1,
            'isTwoFactorEnabled'         => $data['isTwoFactorEnabled'] ?? false,
            'isNotificationsEnabled'     => $data['isNotificationsEnabled'] ?? true,
            'isRealTimeDashboardEnabled' => $data['isRealTimeDashboardEnabled'] ?? false,
        ]);
    }
}