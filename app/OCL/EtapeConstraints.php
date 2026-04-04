<?php

namespace App\OCL;

use App\Models\Etapes;
use App\Models\Facture;
use Carbon\Carbon;

/**
 * ============================================================
 * CONTRAINTES OCL — Classe Etapes
 * ============================================================
 *
 * Contrainte : DateEtapePosterieure
 *
 * Spécification OCL formelle :
 *
 *   context Etapes
 *   inv DateEtapePosterieure:
 *     self.created_at > self.facture.dateReception
 *
 * Signification :
 *   La date de création d'une Etape doit être strictement
 *   postérieure à la dateReception de la Facture associée.
 *
 * Composantes OCL identifiées :
 *   - context Etapes             → classe concernée : App\Models\Etapes
 *   - inv                        → invariant, toujours vrai sur toute instance
 *   - self                       → instance $etape en cours de création
 *   - self.facture               → navigation via belongsTo(Facture::class)
 *   - self.facture.dateReception → attribut dateReception de la Facture liée
 *   - self.created_at            → date de création de l'étape
 * ============================================================
 */
class EtapeConstraints
{
    /**
     * Vérifie l'invariant OCL : DateEtapePosterieure
     *
     * context Etapes
     * inv DateEtapePosterieure:
     *   self.created_at > self.facture.dateReception
     *
     * @param  Etapes  $etape
     * @throws \InvalidArgumentException si la contrainte est violée
     */
    public static function checkDateEtapePosterieure(Etapes $etape): void
    {
        // self.facture → navigation OCL vers la Facture associée
        $facture = Facture::find($etape->facture_id);

        if (!$facture) {
            throw new \InvalidArgumentException(
                "Contrainte OCL : la facture associée à cette étape est introuvable."
            );
        }

        $dateEtape     = Carbon::parse($etape->created_at ?? Carbon::now());
        $dateReception = Carbon::parse($facture->dateReception);

        // OCL : self.created_at > self.facture.dateReception
        if (!$dateEtape->greaterThan($dateReception)) {
            throw new \InvalidArgumentException(
                "Contrainte OCL violée [DateEtapePosterieure] : " .
                "la date de l'étape ({$dateEtape->toDateTimeString()}) " .
                "doit être postérieure à la date de réception " .
                "de la facture ({$dateReception->toDateTimeString()})."
            );
        }
    }
}