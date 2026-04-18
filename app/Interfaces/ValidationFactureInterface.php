<?php

namespace App\Interfaces;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * ============================================================
 * INTERFACE : ValidationFactureInterface
 * ============================================================
 *
 * PRINCIPE SOLID — Dependency Inversion Principle (DIP) :
 *   Les modules de haut niveau (ValidationFactureController)
 *   ne doivent pas dépendre des modules de bas niveau
 *   (classes concrètes : ValidationChainBuilder, ValidationContext,
 *   PersonnelDCFConstraints). Tous deux doivent dépendre
 *   d'abstractions.
 *
 *   → ValidationFactureController dépend de cette interface,
 *     pas des implémentations concrètes.
 *   → Les classes de service concrètes implémentent cette interface.
 *
 * GRASP — Low Coupling :
 *   En dépendant de cette abstraction plutôt que des classes
 *   concrètes, le Controller est découplé des détails
 *   d'implémentation (Chain of Responsibility, Strategy, OCL).
 *   On peut substituer ou tester chaque service indépendamment.
 *
 * GRASP — Protected Variations :
 *   Cette interface isole le Controller des variations futures
 *   dans les stratégies de validation (ex : ajout d'un nouveau
 *   type de facture, changement du workflow de validation).
 *
 * Contrat que doit respecter tout service de validation
 * de factures injectable dans ValidationFactureController.
 * ============================================================
 */
interface ValidationFactureInterface
{
    // --------------------------------------------------------
    // CONSULTATION — Listes de factures
    // --------------------------------------------------------

    /**
     * Retourne les factures à valider selon le rôle de l'agent
     * connecté (version Before).
     */
    public function getInvoicesToValidate(Request $request): JsonResponse;

    /**
     * Retourne les factures à valider selon le rôle de l'agent
     * connecté (version COF — Chain of Responsibility).
     */
    public function getInvoicesToValidateCOF(Request $request): JsonResponse;

    // --------------------------------------------------------
    // CONSULTATION — Détail d'une facture
    // --------------------------------------------------------

    /**
     * Retourne le détail complet d'une facture à valider
     * (version Before, avec progression de paiement).
     */
    public function getInvoiceToValidate(Request $request): JsonResponse;

    /**
     * Retourne le détail d'une facture avec OCL peutTraiterFacture()
     * (version COF).
     */
    public function getInvoiceToValidateCOF(Request $request): JsonResponse;

    // --------------------------------------------------------
    // ACTIONS — Validation / Rejet
    // --------------------------------------------------------

    /**
     * Valide ou rejette une facture (version Before, avec States).
     *
     * @param  Request $request  Doit contenir : id, etat_id, motif_rejet (si rejet)
     */
    public function validerOuRejeter(Request $request): JsonResponse;

    /**
     * Valide ou rejette une facture via Chain of Responsibility
     * et contrainte OCL RoleCorrespondTypeFacture (version COF).
     *
     * @param  Request $request  Doit contenir : id, etat_id, motif_rejet (si rejet)
     */
    public function validerOuRejeterCOF(Request $request): JsonResponse;

    /**
     * Valide une facture via le Pattern Strategy
     * (ValidationContext + stratégie selon type de facture).
     *
     * @param  Request $request  Doit contenir : id
     */
    public function validerViaStrategy(Request $request): JsonResponse;

    /**
     * Rejette une facture via le Pattern Strategy.
     *
     * @param  Request $request  Doit contenir : id, motif
     */
    public function rejeterViaStrategy(Request $request): JsonResponse;

    // --------------------------------------------------------
    // RÉFÉRENTIELS — Types de factures & Motifs de rejet
    // --------------------------------------------------------

    /**
     * Retourne les types de factures accessibles à l'agent
     * connecté (version Before).
     */
    public function getInvoiceTypes(Request $request): JsonResponse;

    /**
     * Retourne tous les types de factures (version COF).
     */
    public function getInvoiceTypesCOF(Request $request): JsonResponse;

    /**
     * Retourne les motifs de rejet disponibles (version Before).
     */
    public function getMotifsDeRejet(Request $request): JsonResponse;

    /**
     * Retourne les motifs de rejet disponibles (version COF).
     */
    public function getMotifsDeRejetCOF(Request $request): JsonResponse;

    // --------------------------------------------------------
    // OCL — Validation d'un lot de factures
    // --------------------------------------------------------

    /**
     * Vérifie si l'agent est autorisé à traiter un lot de factures.
     *
     * Correspond à la contrainte OCL :
     *   context PersonnelDCF
     *   inv RoleCorrespondTypeFacture:
     *     self.factures->forAll(f |
     *       self.role.typesFactures->includes(f.typeFacture)
     *     )
     *
     * @param  Request $request  Doit contenir : facture_ids (array)
     */
    public function verifierLotFactures(Request $request): JsonResponse;
}
