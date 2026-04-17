<?php

namespace App\Factories;

use InvalidArgumentException;

/**
 * UserFactoryProvider
 *
 * Point d'entrée unique du Factory Method Pattern.
 * Instancie la bonne factory selon le role_id fourni,
 * puis délègue la création à createUser().
 *
 * Usage dans un Controller :
 *   $user = UserFactoryProvider::make(3, $data);   // → Fournisseur
 *   $user = UserFactoryProvider::make(2, $data);   // → Agent BOF
 *   $user = UserFactoryProvider::make(4, $data);   // → Agent AP
 */
class UserFactoryProvider
{
    // Map role_id → factory (classe concrète)
    private static array $factories = [
        1 => AdministrateurFactory::class,
        2 => AgentBofFactory::class,
        3 => FournisseurFactory::class,
        // 4, 5, 6 → PersonnelDCFFactory avec roleId passé en constructeur
        4 => PersonnelDCFFactory::class,
        5 => PersonnelDCFFactory::class,
        6 => PersonnelDCFFactory::class,
    ];

    /**
     * Résout la factory et crée l'utilisateur
     *
     * @param  int   $roleId  Le role_id (1=Admin, 2=BOF, 3=Fournisseur, 4=AP, 5=Fiscaliste, 6=Trésorerie)
     * @param  array $data    Les données de l'utilisateur à créer
     * @return \App\Models\User
     *
     * @throws InvalidArgumentException si le role_id est inconnu
     */
    public static function make(int $roleId, array $data): \App\Models\User
    {
        if (!array_key_exists($roleId, self::$factories)) {
            throw new InvalidArgumentException("role_id {$roleId} non supporté par UserFactoryProvider.");
        }

        $factory = self::resolveFactory($roleId);

        return $factory->createUser($data);
    }

    /**
     * Instancie la bonne factory selon le role_id
     * PersonnelDCFFactory nécessite le roleId en constructeur
     */
    private static function resolveFactory(int $roleId): UserFactory
    {
        // PersonnelDCF regroupe les rôles 4, 5, 6
        if (in_array($roleId, [4, 5, 6])) {
            return new PersonnelDCFFactory($roleId);
        }

        $factoryClass = self::$factories[$roleId];
        return new $factoryClass();
    }
}