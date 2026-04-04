<?php

namespace App\Services;

use App\Models\Facture;
use App\Models\BonDeCommande;
use App\Models\Bordereau;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Webklex\PDFMerger\Facades\PDFMergerFacade;
use GuzzleHttp\Client;

class FactureService
{
    /**
     * Supprimer une facture
     */
    public function supprimerFacture(Facture $facture, $user, $role)
    {
        if ($role->id === 3) {
            if ($facture->fournisseur_id !== $user->id) {
                return ['success' => false, 'message' => 'Cette facture ne vous concerne pas'];
            }
            if ($facture->etat()->first()->id !== 1) {
                return ['success' => false, 'message' => 'Facture en cours de traitement, suppression impossible'];
            }
        } else {
            if (($facture->agent_bof_id !== $user->id) || ($facture->etat()->first()->id !== 1)) {
                return ['success' => false, 'message' => "Vous ne pouvez pas supprimer une facture que vous n'avez pas créée"];
            }
        }

        $facture->delete();
        return ['success' => true, 'message' => 'Facture supprimée avec succès'];
    }

    /**
     * Gérer le bordereau du jour
     */
    public function gererBordereau(string $nature): Bordereau
    {
        $bord = Bordereau::whereDate('created_at', Carbon::today()->toDateString())->first();

        if (!$bord) {
            $bord = new Bordereau();
            $bord->date_sent = Carbon::now();
            $bord->folder = Carbon::now()->toDateString();
            $bord->status = 'En cours';
            $bord->reference = Str::random(8) . '/' . Carbon::now()->toDateString();
            $bord->nature = $nature;
            $bord->save();
        } else {
            $bord->date_sent = Carbon::now();
        }

        return $bord;
    }

    /**
     * Fusionner les fichiers PDF
     */
    public function fusionnerPdfs(array $files, string $fourName, int $count): array
    {
        $pdf = PDFMergerFacade::init();
        foreach ($files as $file) {
            $pdf->addPDF($file->getPathName(), 'all');
        }
        $fileName = 'facture_' . $fourName . ' ' . ($count + 1) . '.pdf';
        $pdf->merge();
        $filePath = Carbon::now()->toDateString() . '/' . $fileName;
        Storage::disk('facture')->put($filePath, $pdf->output());

        return ['fileName' => $fileName, 'filePath' => $filePath];
    }

    /**
     * Notifier les agents BOF
     */
    public function notifierAgentsBof(Facture $facture, $user, string $numFacture): void
    {
        try {
            $users = User::where('role_id', 2)->get();
            $client = new Client();

            foreach ($users as $agent) {
                if ($agent->isNotificationsEnabled) {
                    $client->post(env('NOTIFICATION_MAIL_URL'), [
                        'json' => [
                            'emails' => [$agent->email],
                            'message' => 'Une nouvelle facture a été ajoutée'
                        ]
                    ]);
                }
                Notification::create([
                    'user_id'          => $agent->id,
                    'type'             => 'FactureEnvoyee',
                    'titre'            => 'Une nouvelle facture a été envoyée',
                    'num_facture'      => $numFacture,
                    'id_facture'       => $facture->id,
                    'id_reclamation'   => null,
                    'titre_reclamation'=> null,
                    'nom_creator'      => $user->name,
                ]);
            }

            // Nettoyer les notifications obsolètes
            Notification::where('updated_at', '<', Carbon::now()->subHours(env('NOTIFICATION_DELETE_DELAY', 24)))
                ->where('lu', true)
                ->delete();

        } catch (\Exception $e) {
            // Log silencieux — les notifications ne bloquent pas la création
        }
    }
}