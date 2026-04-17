<?php

namespace App\Interfaces;

use App\Models\User;

/**
 * Interface UtilisateurFactoryInterface
 *
 * Contrat de la factory de création d'utilisateurs.
 * Les controllers dépendent de cette interface, pas de la classe concrète.
 * → GRASP Low Coupling
 */
interface UtilisateurFactoryInterface
{
    /**
     * Crée un utilisateur selon son type (fournisseur, agent, admin, etc.)
     *
     * @param  array  $data  Les données validées de l'utilisateur
     * @return User
     */
    public function creer(array $data): User;
}