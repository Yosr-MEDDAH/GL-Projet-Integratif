<?php

namespace App\ChainOfResponsibility;

use App\Models\Facture;
use App\Models\User;

/**
 * ============================================================
 * PATTERN : CHAIN OF RESPONSIBILITY (Comportement)
 * ============================================================
 *
 * Interface du maillon de la chaîne de validation.
 *
 * Le workflow BOF → AP → Fiscaliste → Trésorerie est modélisé
 * comme une chaîne de responsabilité : chaque handler traite
 * la facture s'il est compétent, sinon il passe au suivant.
 *
 * Diagramme de la chaîne :
 *   [BofValidationHandler]
 *         ↓
 *   [ApValidationHandler]
 *         ↓
 *   [FiscalisteValidationHandler]
 *         ↓
 *   [TresorerieValidationHandler]
 * ============================================================
 */
interface ValidationHandlerInterface
{
    /**
     * Définit le prochain maillon dans la chaîne.
     */
    public function setNext(ValidationHandlerInterface $handler): ValidationHandlerInterface;

    /**
     * Tente de traiter la facture.
     * Si ce handler n'est pas compétent, délègue au suivant.
     *
     * @return array  ['success' => bool, 'message' => string]
     */
    public function handle(Facture $facture, User $user): array;

    /**
     * Tente de rejeter la facture.
     * Si ce handler n'est pas compétent, délègue au suivant.
     *
     * @return array  ['success' => bool, 'message' => string]
     */
    public function handleRejet(Facture $facture, User $user, string $motif): array;
}
