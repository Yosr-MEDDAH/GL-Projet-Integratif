<?php

namespace App\ChainOfResponsibility;

/**
 * ============================================================
 * BUILDER DE CHAÎNE
 * ============================================================
 * Construit et retourne la chaîne complète :
 *
 *   BofValidationHandler
 *     → ApValidationHandler
 *         → FiscalisteValidationHandler
 *             → TresorerieValidationHandler
 *
 * Utilisation :
 *   $chain = ValidationChainBuilder::build();
 *   $result = $chain->handle($facture, $user);
 *   $result = $chain->handleRejet($facture, $user, $motif);
 * ============================================================
 */
class ValidationChainBuilder
{
    public static function build(): ValidationHandlerInterface
    {
        $bof        = new BofValidationHandler();
        $ap         = new ApValidationHandler();
        $fiscaliste = new FiscalisteValidationHandler();
        $tresorerie = new TresorerieValidationHandler();

        // Assemblage de la chaîne
        $bof->setNext($ap)
            ->setNext($fiscaliste)
            ->setNext($tresorerie);

        return $bof; // On retourne la tête de la chaîne
    }
}
