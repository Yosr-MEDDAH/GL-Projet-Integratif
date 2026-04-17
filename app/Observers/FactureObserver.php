<?php

namespace App\Observers;

use App\Models\Facture;
use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;

class FactureObserver
{
    public function updated(Facture $facture): void
    {
        // Status progression is represented by both `etat_id` and `validePar` in this codebase.
        if (!$facture->wasChanged('etat_id') && !$facture->wasChanged('validePar')) {
            return;
        }

        $this->notifyOnEtatChange($facture);
    }

    private function notifyOnEtatChange(Facture $facture): void
    {
        $etatId = $facture->etat_id;
        $validePar = $facture->validePar;

        if ($this->isPaid($etatId, $validePar)) {
            $this->notifyFournisseur(
                $facture,
                'Votre facture a été traitée.',
                'FacturePayee',
                'Votre facture a été traitée'
            );
            return;
        }

        if ($this->isRejected($etatId, $validePar)) {
            $this->notifyFournisseur(
                $facture,
                'Votre facture a été rejetée.',
                'FactureRejetee',
                'Votre facture a été rejetée'
            );
            return;
        }

        $nextRoleId = $this->resolveNextRoleId($validePar);
        if ($nextRoleId === null) {
            return;
        }

        $agents = $this->getAgentsForRoleAndType($nextRoleId, $facture);
        $this->notifyUsers(
            $agents,
            $facture,
            'Une nouvelle facture est prête pour validation.',
            'FactureAValider',
            'Nouvelle facture à valider'
        );
    }

    private function isPaid(?int $etatId, ?string $validePar): bool
    {
        return ($etatId === 3 && $validePar === 'Agent Trésorerie')
            || ($etatId === 2 && $validePar === 'Agent Trésorerie');
    }

    private function isRejected(?int $etatId, ?string $validePar): bool
    {
        if ($etatId === 6) {
            return true;
        }

        if ($etatId === 3 && $validePar !== 'Agent Trésorerie') {
            return true;
        }

        return false;
    }

    private function resolveNextRoleId(?string $validePar): ?int
    {
        return match ($validePar) {
            'Agent Bof' => 4,
            'Agent Ap' => 5,
            'Agent Fiscaliste' => 6,
            default => null,
        };
    }

    private function notifyFournisseur(Facture $facture, string $message, string $type, string $titre): void
    {
        if (!$facture->fournisseur_id) {
            return;
        }

        $fournisseur = User::find($facture->fournisseur_id);
        if (!$fournisseur) {
            return;
        }

        $this->notifyUsers(collect([$fournisseur]), $facture, $message, $type, $titre);
    }

    private function getAgentsForRoleAndType(int $roleId, Facture $facture)
    {
        $query = User::where('role_id', $roleId);
        if ($facture->type_facture_id !== null) {
            $query->whereJsonContains('type_facture_ids', $facture->type_facture_id);
        }

        return $query->get();
    }

    private function notifyUsers($users, Facture $facture, string $message, string $type, string $titre): void
    {
        $notificationService = NotificationService::getInstance();

        foreach ($users as $user) {
            if ($this->recentNotificationExists($user->id, $facture->id, $type)) {
                continue;
            }

            $notificationService->notifierParMail($user, $message, $type, $titre, [
                'num_facture' => $facture->number,
                'id_facture' => $facture->id,
                'nom_creator' => $facture->validePar,
            ]);
        }
    }

    private function recentNotificationExists(int $userId, int $factureId, string $type): bool
    {
        return Notification::where('user_id', $userId)
            ->where('id_facture', $factureId)
            ->where('type', $type)
            ->where('created_at', '>=', now()->subSeconds(30))
            ->exists();
    }
}
