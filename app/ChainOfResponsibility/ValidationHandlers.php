<?php

namespace App\ChainOfResponsibility;

use App\Models\Etapes;
use App\Models\Facture;
use App\Models\Notification;
use App\Models\User;

/**
 * ============================================================
 * MAILLON 2 : Agent AP
 * ============================================================
 * Responsabilité : traiter les factures validées par BOF
 * (etat_id = 2 ET validePar = 'Agent Bof').
 * ============================================================
 */
class ApValidationHandler extends AbstractValidationHandler
{
    private const ROLE_ID         = 4;
    private const ETAT_ATTENDU    = 2;
    private const VALIDE_PAR_PREV = 'Agent Bof';
    private const VALIDE_PAR_MOI  = 'Agent Ap';

    public function handle(Facture $facture, User $user): array
    {
        if ($facture->etat_id !== self::ETAT_ATTENDU || $facture->validePar !== self::VALIDE_PAR_PREV) {
            return $this->passToNext($facture, $user);
        }

        if ($user->role_id !== self::ROLE_ID) {
            return ['success' => false, 'message' => "Seul un Agent AP peut traiter cette étape."];
        }

        Etapes::create([
            'facture_id'      => $facture->id,
            'etat_id'         => self::ETAT_ATTENDU,
            'traitParRoleNom' => self::VALIDE_PAR_MOI,
            'traitParId'      => $user->id,
            'traitParNom'     => $user->name,
        ]);

        $facture->validePar = self::VALIDE_PAR_MOI;
        $facture->save();

        $this->notifierRole(5, $facture, $user);

        return ['success' => true, 'message' => 'Facture validée par Agent AP. Transmise au Fiscaliste.'];
    }

    public function handleRejet(Facture $facture, User $user, string $motif): array
    {
        if ($facture->etat_id !== self::ETAT_ATTENDU || $facture->validePar !== self::VALIDE_PAR_PREV) {
            return $this->passRejetToNext($facture, $user, $motif);
        }

        if ($user->role_id !== self::ROLE_ID) {
            return ['success' => false, 'message' => "Seul un Agent AP peut rejeter à cette étape."];
        }

        Etapes::create([
            'facture_id'      => $facture->id,
            'etat_id'         => self::ETAT_ATTENDU,
            'traitParRoleNom' => self::VALIDE_PAR_MOI,
            'traitParId'      => $user->id,
            'traitParNom'     => $user->name,
        ]);

        $facture->etat_id = 6;
        $facture->save();

        if ($facture->fournisseur_id) {
            Notification::create([
                'user_id'     => $facture->fournisseur_id,
                'type'        => 'FactureRejetee',
                'titre'       => 'Votre facture a été rejetée',
                'num_facture' => $facture->number,
                'id_facture'  => $facture->id,
                'id_reclamation'    => null,
                'titre_reclamation' => null,
                'nom_creator' => $user->name,
            ]);
        }

        return ['success' => true, 'message' => "Facture rejetée par Agent AP. Motif : {$motif}"];
    }

    private function notifierRole(int $roleId, Facture $facture, User $validateur): void
    {
        $agents = User::where('role_id', $roleId)
            ->whereJsonContains('type_facture_ids', $facture->type_facture_id)
            ->get();

        foreach ($agents as $agent) {
            Notification::create([
                'user_id'           => $agent->id,
                'type'              => 'FactureAValider',
                'titre'             => 'Nouvelle facture à valider',
                'num_facture'       => $facture->number,
                'id_facture'        => $facture->id,
                'id_reclamation'    => null,
                'titre_reclamation' => null,
                'nom_creator'       => $validateur->name,
            ]);
        }
    }
}


/**
 * ============================================================
 * MAILLON 3 : Agent Fiscaliste
 * ============================================================
 * Responsabilité : traiter les factures validées par AP
 * (etat_id = 2 ET validePar = 'Agent Ap').
 * ============================================================
 */
class FiscalisteValidationHandler extends AbstractValidationHandler
{
    private const ROLE_ID         = 5;
    private const ETAT_ATTENDU    = 2;
    private const VALIDE_PAR_PREV = 'Agent Ap';
    private const VALIDE_PAR_MOI  = 'Agent Fiscaliste';

    public function handle(Facture $facture, User $user): array
    {
        if ($facture->etat_id !== self::ETAT_ATTENDU || $facture->validePar !== self::VALIDE_PAR_PREV) {
            return $this->passToNext($facture, $user);
        }

        if ($user->role_id !== self::ROLE_ID) {
            return ['success' => false, 'message' => "Seul un Agent Fiscaliste peut traiter cette étape."];
        }

        Etapes::create([
            'facture_id'      => $facture->id,
            'etat_id'         => self::ETAT_ATTENDU,
            'traitParRoleNom' => self::VALIDE_PAR_MOI,
            'traitParId'      => $user->id,
            'traitParNom'     => $user->name,
        ]);

        $facture->validePar = self::VALIDE_PAR_MOI;
        $facture->save();

        $this->notifierRole(6, $facture, $user);

        return ['success' => true, 'message' => 'Facture validée par Fiscaliste. Transmise à la Trésorerie.'];
    }

    public function handleRejet(Facture $facture, User $user, string $motif): array
    {
        if ($facture->etat_id !== self::ETAT_ATTENDU || $facture->validePar !== self::VALIDE_PAR_PREV) {
            return $this->passRejetToNext($facture, $user, $motif);
        }

        if ($user->role_id !== self::ROLE_ID) {
            return ['success' => false, 'message' => "Seul un Agent Fiscaliste peut rejeter à cette étape."];
        }

        Etapes::create([
            'facture_id'      => $facture->id,
            'etat_id'         => self::ETAT_ATTENDU,
            'traitParRoleNom' => self::VALIDE_PAR_MOI,
            'traitParId'      => $user->id,
            'traitParNom'     => $user->name,
        ]);

        $facture->etat_id = 6;
        $facture->save();

        if ($facture->fournisseur_id) {
            Notification::create([
                'user_id'     => $facture->fournisseur_id,
                'type'        => 'FactureRejetee',
                'titre'       => 'Votre facture a été rejetée',
                'num_facture' => $facture->number,
                'id_facture'  => $facture->id,
                'id_reclamation'    => null,
                'titre_reclamation' => null,
                'nom_creator' => $user->name,
            ]);
        }

        return ['success' => true, 'message' => "Facture rejetée par Fiscaliste. Motif : {$motif}"];
    }

    private function notifierRole(int $roleId, Facture $facture, User $validateur): void
    {
        $agents = User::where('role_id', $roleId)
            ->whereJsonContains('type_facture_ids', $facture->type_facture_id)
            ->get();

        foreach ($agents as $agent) {
            Notification::create([
                'user_id'           => $agent->id,
                'type'              => 'FactureAValider',
                'titre'             => 'Nouvelle facture à valider',
                'num_facture'       => $facture->number,
                'id_facture'        => $facture->id,
                'id_reclamation'    => null,
                'titre_reclamation' => null,
                'nom_creator'       => $validateur->name,
            ]);
        }
    }
}


/**
 * ============================================================
 * MAILLON 4 : Agent Trésorerie (dernier maillon)
 * ============================================================
 * Responsabilité : valider définitivement les factures
 * (etat_id = 2 ET validePar = 'Agent Fiscaliste').
 * Après validation → etat_id = 3 (Payée / Traitée).
 * ============================================================
 */
class TresorerieValidationHandler extends AbstractValidationHandler
{
    private const ROLE_ID         = 6;
    private const ETAT_ATTENDU    = 2;
    private const VALIDE_PAR_PREV = 'Agent Fiscaliste';
    private const VALIDE_PAR_MOI  = 'Agent Trésorerie';
    private const ETAT_FINAL      = 3; // Facture complètement traitée

    public function handle(Facture $facture, User $user): array
    {
        if ($facture->etat_id !== self::ETAT_ATTENDU || $facture->validePar !== self::VALIDE_PAR_PREV) {
            return $this->passToNext($facture, $user);
        }

        if ($user->role_id !== self::ROLE_ID) {
            return ['success' => false, 'message' => "Seul un Agent Trésorerie peut traiter cette étape."];
        }

        Etapes::create([
            'facture_id'      => $facture->id,
            'etat_id'         => self::ETAT_ATTENDU,
            'traitParRoleNom' => self::VALIDE_PAR_MOI,
            'traitParId'      => $user->id,
            'traitParNom'     => $user->name,
        ]);

        $facture->etat_id   = self::ETAT_FINAL;
        $facture->validePar = self::VALIDE_PAR_MOI;
        $facture->save();

        // Notifier le fournisseur que sa facture est payée
        if ($facture->fournisseur_id) {
            Notification::create([
                'user_id'     => $facture->fournisseur_id,
                'type'        => 'FacturePayee',
                'titre'       => 'Votre facture a été traitée',
                'num_facture' => $facture->number,
                'id_facture'  => $facture->id,
                'id_reclamation'    => null,
                'titre_reclamation' => null,
                'nom_creator' => $user->name,
            ]);
        }

        return ['success' => true, 'message' => 'Facture validée par Trésorerie. Traitement complet.'];
    }

    public function handleRejet(Facture $facture, User $user, string $motif): array
    {
        if ($facture->etat_id !== self::ETAT_ATTENDU || $facture->validePar !== self::VALIDE_PAR_PREV) {
            return $this->passRejetToNext($facture, $user, $motif);
        }

        if ($user->role_id !== self::ROLE_ID) {
            return ['success' => false, 'message' => "Seul un Agent Trésorerie peut rejeter à cette étape."];
        }

        Etapes::create([
            'facture_id'      => $facture->id,
            'etat_id'         => self::ETAT_ATTENDU,
            'traitParRoleNom' => self::VALIDE_PAR_MOI,
            'traitParId'      => $user->id,
            'traitParNom'     => $user->name,
        ]);

        $facture->etat_id = 6;
        $facture->save();

        if ($facture->fournisseur_id) {
            Notification::create([
                'user_id'     => $facture->fournisseur_id,
                'type'        => 'FactureRejetee',
                'titre'       => 'Votre facture a été rejetée',
                'num_facture' => $facture->number,
                'id_facture'  => $facture->id,
                'id_reclamation'    => null,
                'titre_reclamation' => null,
                'nom_creator' => $user->name,
            ]);
        }

        return ['success' => true, 'message' => "Facture rejetée par Trésorerie. Motif : {$motif}"];
    }
}
