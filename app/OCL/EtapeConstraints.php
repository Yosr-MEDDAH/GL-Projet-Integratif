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
     * Rôles exclus du circuit de validation.
     * Correspondent aux acteurs qui ne valident pas de facture.
     */
    private const ROLES_NON_VALIDATEURS = ['Admin', 'Fournisseur'];
 
    // -------------------------------------------------------
    // Contrainte 1 : DateEtapePosterieure
    // -------------------------------------------------------

    /**
     * Vérifie l'invariant OCL : DateEtapePosterieure
     *
     *   context Etapes
     *   inv DateEtapePosterieure:
     *     self.created_at > self.facture.reception_date
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

// -------------------------------------------------------
    // Contrainte 2 : NombreEtapesLimite
    // -------------------------------------------------------

    /**
     * Retourne le nombre maximum d'étapes autorisées pour une facture.
     *
     * OCL :
     *   Role.allInstances()
     *       ->select(r | r.name <> 'Admin' and r.name <> 'Fournisseur')
     *       ->size()
     *
     * @return int
     */
    public static function getNombreMaxEtapes(): int
    {
        // Role.allInstances() → tous les rôles du système
        // ->select(r | r.name <> 'Admin' and r.name <> 'Fournisseur')
        return Role::whereNotIn('name', self::ROLES_NON_VALIDATEURS)->count();
    }

    /**
     * Vérifie l'invariant OCL : NombreEtapesLimite
     *
     *   context Etapes
     *   inv NombreEtapesLimite:
     *     self.facture.etapes->size() <=
     *       Role.allInstances()
     *           ->select(r | r.name <> 'Admin' and r.name <> 'Fournisseur')
     *           ->size()
     *
     * À appeler AVANT d'insérer une nouvelle étape en base,
     * afin de vérifier que la future taille (existantes + 1)
     * ne dépassera pas le plafond OCL.
     *
     * @param  int  $factureId  Identifiant de la facture concernée
     * @throws \InvalidArgumentException si la contrainte est violée
     */
    public static function checkNombreEtapesLimite(int $factureId): void
    {
        // self.facture.etapes->size() : nombre d'étapes DÉJÀ enregistrées
        $etapesExistantes = Etapes::where('facture_id', $factureId)->count();

        // Après l'ajout, le nouveau total sera :
        $futurTotal = $etapesExistantes + 1;

        // Plafond OCL : nombre de rôles validateurs
        $maxEtapes = self::getNombreMaxEtapes();

        // OCL : self.facture.etapes->size() <= Role.allInstances()
        //           ->select(r | r.name <> 'Admin' and r.name <> 'Fournisseur')
        //           ->size()
        if ($futurTotal > $maxEtapes) {
            throw new \InvalidArgumentException(
                "Contrainte OCL violée [NombreEtapesLimite] : " .
                    "la facture (id={$factureId}) possède déjà {$etapesExistantes} étape(s). " .
                    "L'ajout d'une nouvelle étape porterait le total à {$futurTotal}, " .
                    "ce qui dépasse le nombre de rôles de validation autorisés ({$maxEtapes})."
            );
        }
    }
 
    // -------------------------------------------------------
    // Point d'entrée unique : vérifier toutes les contraintes
    // -------------------------------------------------------

    /**
     * Exécute toutes les contraintes OCL relatives à une étape.
     *
     * À appeler avant la persistance d'une nouvelle Etapes :
     *
     *   EtapeConstraints::checkAll($etape);
     *   $etape->save();
     *
     * @param  Etapes  $etape  Instance (non encore persistée) à valider
     * @throws \InvalidArgumentException dès la première contrainte violée
     */
    public static function checkAll(Etapes $etape): void
    {
        self::checkDateEtapePosterieure($etape);
        self::checkNombreEtapesLimite($etape->facture_id);
    }
}
