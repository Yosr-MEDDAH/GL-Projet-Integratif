<?php

namespace App\ChainOfResponsibility;

use App\Models\Facture;
use App\Models\User;

/**
 * Maillon abstrait de la chaîne de responsabilité.
 *
 * Implémente la gestion du successeur et le comportement
 * par défaut (passer au maillon suivant).
 */
abstract class AbstractValidationHandler implements ValidationHandlerInterface
{
    private ?ValidationHandlerInterface $next = null;

    public function setNext(ValidationHandlerInterface $handler): ValidationHandlerInterface
    {
        $this->next = $handler;
        return $handler; // permet le chaînage fluide : $a->setNext($b)->setNext($c)
    }

    /**
     * Tente de passer au maillon suivant.
     * Retourne une erreur si la chaîne est terminée sans traitement.
     */
    protected function passToNext(Facture $facture, User $user): array
    {
        if ($this->next !== null) {
            return $this->next->handle($facture, $user);
        }

        return [
            'success' => false,
            'message' => "Aucun handler compétent trouvé pour cette facture (etat_id={$facture->etat_id}).",
        ];
    }

    /**
     * Tente de passer le rejet au maillon suivant.
     */
    protected function passRejetToNext(Facture $facture, User $user, string $motif): array
    {
        if ($this->next !== null) {
            return $this->next->handleRejet($facture, $user, $motif);
        }

        return [
            'success' => false,
            'message' => "Aucun handler compétent trouvé pour rejeter cette facture (etat_id={$facture->etat_id}).",
        ];
    }
}
