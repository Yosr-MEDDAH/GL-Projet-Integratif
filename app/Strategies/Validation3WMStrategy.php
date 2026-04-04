<?php

namespace App\Strategies;

use App\Models\Facture;
use App\Models\Etapes;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use GuzzleHttp\Client;

class Validation3WMStrategy implements ValidationStrategyInterface
{
    public function valider(Facture $facture, $user): array
    {
        $etatActuel = $facture->etat_id;

        $prochainEtat = $etatActuel + 1;

        if ($prochainEtat > 5) {
            return ['success' => false, 'message' => 'Facture déjà complètement validée'];
        }

        Etapes::create([
            'facture_id'  => $facture->id,
            'description' => 'Validée par ' . $user->name,
            'date'        => Carbon::now(),
            'statut'      => 'Validée',
            'validateur'  => $user->name,
        ]);

        $facture->etat_id = $prochainEtat;
        $facture->save();

        if ($prochainEtat < 5) {
            $this->notifierProchainAgent($facture, $prochainEtat, $user);
        }

        return ['success' => true, 'message' => 'Facture 3WM validée avec succès'];
    }

    public function rejeter(Facture $facture, $user, string $motif): array
    {
        Etapes::create([
            'facture_id'  => $facture->id,
            'description' => 'Rejetée : ' . $motif,
            'date'        => Carbon::now(),
            'statut'      => 'Rejetée',
            'validateur'  => $user->name,
        ]);

        $facture->etat_id = 6;
        $facture->save();

        if ($facture->fournisseur_id) {
            Notification::create([
                'user_id'           => $facture->fournisseur_id,
                'type'              => 'FactureRejetee',
                'titre'             => 'Votre facture a été rejetée',
                'num_facture'       => $facture->number,
                'id_facture'        => $facture->id,
                'id_reclamation'    => null,
                'titre_reclamation' => null,
                'nom_creator'       => $user->name,
            ]);
        }

        return ['success' => true, 'message' => 'Facture 3WM rejetée avec succès'];
    }

    private function notifierProchainAgent(Facture $facture, int $prochainEtat, $user): void
    {
        $roleMap = [2 => 2, 3 => 4, 4 => 5, 5 => 6];
        $roleId = $roleMap[$prochainEtat] ?? null;

        if (!$roleId) return;

        try {
            $agents = User::where('role_id', $roleId)->get();
            $client = new Client();

            foreach ($agents as $agent) {
                if ($agent->isNotificationsEnabled) {
                    $client->post(env('NOTIFICATION_MAIL_URL'), [
                        'json' => [
                            'emails'  => [$agent->email],
                            'message' => 'Une facture 3WM est prête pour validation'
                        ]
                    ]);
                }
                Notification::create([
                    'user_id'           => $agent->id,
                    'type'              => 'FactureAValider',
                    'titre'             => 'Nouvelle facture à valider',
                    'num_facture'       => $facture->number,
                    'id_facture'        => $facture->id,
                    'id_reclamation'    => null,
                    'titre_reclamation' => null,
                    'nom_creator'       => $user->name,
                ]);
            }
        } catch (\Exception $e) {
        }
    }
}