<?php

namespace App\Factories;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Classe abstraite — Factory Method Pattern
 * Chaque sous-classe implémente createUser() selon le rôle
 */
abstract class UserFactory
{
    /**
     * Factory Method : à implémenter dans chaque sous-classe concrète
     */
    abstract public function createUser(array $data): User;

    /**
     * Template Method : logique commune partagée entre toutes les factories
     * Hash du password, assignation role_id, notification credentials
     */
    protected function buildBaseUser(array $data): User
    {
        $password = $data['password'] ?? \Illuminate\Support\Str::random(9);

        $user = new User();
        $user->name     = $data['name']  ?? null;
        $user->email    = $data['email'];
        $user->password = Hash::make($password);
        $user->phone    = $data['phone'] ?? null;
        $user->image    = $data['image'] ?? 'default.jpg';
        $user->isActive = $data['isActive'] ?? 1;
        $user->isTwoFactorEnabled = 0;
        $user->role_id  = $this->getRoleId();

        // Stocker le password en clair temporairement pour notification
        $user->plainPassword = $password;

        return $user;
    }

    /**
     * Retourne le role_id associé à la factory concrète
     */
    abstract protected function getRoleId(): int;
}