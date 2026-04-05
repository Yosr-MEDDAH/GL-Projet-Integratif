<?php

namespace App\OCL;

use App\Models\Facture;
use App\Models\Personnel_DCF;
use App\Models\Role;
use App\Models\TypesFactures;

/**
 * ============================================================
 * CONTRAINTE OCL — Rôle Agent vs Type Facture
 * ============================================================
 *
 * Spécification OCL formelle :
 *
 *   context PersonnelDCF
 *   inv RoleCorrespondTypeFacture:
 *     self.factures->forAll(f |
 *       self.role.typesFactures->includes(f.typeFacture)
 *     )
 *
 * Signification :
 *   Un PersonnelDCF ne peut traiter une Facture que si son rôle
 *   correspond au TypeFacture de la facture.
 *   Autrement dit : le type_facture_id de la facture doit
 *   figurer dans la liste type_facture_ids du personnel.
 *
 * Composantes OCL identifiées :
 *   - context PersonnelDCF        → classe App\Models\Personnel_DCF
 *   - inv                         → invariant de classe
 *   - self                        → instance $personnel
 *   - self.role                   → navigation vers App\Models\Role via role_id
 *   - self.role.typesFactures     → les types de factures autorisés (type_facture_ids)
 *   - f.typeFacture               → le type de la facture (type_facture_id)
 *   - ->includes(...)             → appartenance de collection OCL
 * ============================================================
 */
class PersonnelDCFConstraints
{
    /**
     * ──────────────────────────────────────────────────────────
     * INVARIANT : RoleCorrespondTypeFacture
     *
     * context PersonnelDCF
     * inv RoleCorrespondTypeFacture:
     *   self.role.typesFactures->includes(facture.typeFacture)
     *
     * Vérifie qu'un PersonnelDCF est autorisé à traiter
     * la facture donnée selon son rôle.
     *
     * @param  Personnel_DCF  $personnel  l'agent qui veut traiter la facture
     * @param  Facture        $facture    la facture à traiter
     * @throws \InvalidArgumentException si la contrainte OCL est violée
     * ──────────────────────────────────────────────────────────
     */
    public static function checkRoleCorrespondTypeFacture(
        Personnel_DCF $personnel,
        Facture $facture
    ): void {
        // self.role.typesFactures  → liste des type_facture_ids autorisés pour ce rôle
        $typesAutorises = self::getTypesFacturesAutorises($personnel);

        // f.typeFacture → type_facture_id de la facture à traiter
        $typeFactureId = $facture->type_facture_id;

        if ($typeFactureId === null) {
            throw new \InvalidArgumentException(
                "Contrainte OCL violée [RoleCorrespondTypeFacture] : " .
                "la facture #{$facture->number} n'a pas de type défini."
            );
        }

        // OCL : self.role.typesFactures->includes(f.typeFacture)
        if (!in_array($typeFactureId, $typesAutorises, strict: true)) {
            $typeNom = optional(TypesFactures::find($typeFactureId))->typeName ?? "ID={$typeFactureId}";
            $roleNom = optional(Role::find($personnel->role_id))->name ?? "role_id={$personnel->role_id}";

            throw new \InvalidArgumentException(
                "Contrainte OCL violée [RoleCorrespondTypeFacture] : " .
                "le Personnel DCF '{$personnel->name}' (rôle : {$roleNom}) " .
                "n'est pas autorisé à traiter une facture de type '{$typeNom}'. " .
                "Types autorisés : [" . implode(', ', $typesAutorises) . "]."
            );
        }
    }

    /**
     * ──────────────────────────────────────────────────────────
     * Version booléenne (sans exception) utile pour les guards
     * dans les contrôleurs.
     *
     * Retourne true si la contrainte est respectée, false sinon.
     * ──────────────────────────────────────────────────────────
     */
    public static function peutTraiterFacture(
        Personnel_DCF $personnel,
        Facture $facture
    ): bool {
        try {
            self::checkRoleCorrespondTypeFacture($personnel, $facture);
            return true;
        } catch (\InvalidArgumentException) {
            return false;
        }
    }

    /**
     * ──────────────────────────────────────────────────────────
     * Récupère la liste des type_facture_ids autorisés pour
     * un PersonnelDCF.
     *
     * Mapping OCL : self.role.typesFactures
     *
     * Dans l'application, ces IDs sont stockés sous forme
     * de JSON dans la colonne type_facture_ids de la table users.
     * ──────────────────────────────────────────────────────────
     */
    private static function getTypesFacturesAutorises(Personnel_DCF $personnel): array
    {
        // Le champ type_facture_ids est castable en array (JSON)
        $ids = $personnel->type_facture_ids;

        if (is_string($ids)) {
            $ids = json_decode($ids, true) ?? [];
        }

        return is_array($ids) ? $ids : [];
    }

    /**
     * ──────────────────────────────────────────────────────────
     * Vérifie la contrainte pour une liste de factures.
     *
     * context PersonnelDCF
     * inv RoleCorrespondTypeFacture:
     *   self.factures->forAll(f |
     *     self.role.typesFactures->includes(f.typeFacture)
     *   )
     *
     * @param  Personnel_DCF  $personnel
     * @param  Facture[]      $factures
     * @throws \InvalidArgumentException à la première violation
     * ──────────────────────────────────────────────────────────
     */
    public static function checkForAllFactures(
        Personnel_DCF $personnel,
        iterable $factures
    ): void {
        foreach ($factures as $facture) {
            self::checkRoleCorrespondTypeFacture($personnel, $facture);
        }
    }
}
