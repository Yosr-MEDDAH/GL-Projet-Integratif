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
    // =============================================================
    // SOLID — Single Responsibility Principle (SRP)
    //
    // Avant refactoring : FactureController.php mélange tout dans
    // une seule méthode createInvoice() de 80+ lignes :
    //   - authentification & vérification de rôle
    //   - validation des inputs
    //   - résolution du fournisseur
    //   - fusion de fichiers PDF
    //   - gestion du bordereau
    //   - création de la Facture en base
    //   - envoi de notifications
    //
    // Après refactoring : FactureService découpe chacune de ces
    // responsabilités en méthodes indépendantes.
    // Une seule raison de changer par méthode → SRP respecté.
    // =============================================================



    // =========================================================
    // RESPONSABILITÉ 1 : Suppression d'une facture
    // Règle métier : un fournisseur ne peut supprimer que sa
    // propre facture en état "En attente" (etat_id = 1).
    // Un agent BOF ne peut supprimer que ce qu'il a créé.
    // =========================================================

    /**
     * Supprimer une facture selon les règles métier.
     *
     * SRP : cette méthode ne fait QUE vérifier les droits et
     * supprimer. Elle ne valide pas, ne notifie pas, ne fusionne
     * pas de PDF.
     *
     * @return array{success: bool, message: string}
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

    // =========================================================
    // RESPONSABILITÉ 2 : Gestion du bordereau du jour
    // Un seul bordereau par journée. S'il existe, on le réutilise.
    // S'il n'existe pas, on en crée un nouveau.
    // =========================================================

    /**
     * Récupérer ou créer le bordereau du jour.
     *
     * SRP : cette méthode ne fait QUE gérer le cycle de vie du
     * Bordereau. Elle ne touche ni aux factures ni aux PDF.
     *
     * @param  string $nature  Nature du bordereau ("3WM", "Lettre De Credit", "Operateur"…)
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

    // =========================================================
    // RESPONSABILITÉ 3 : Fusion de fichiers PDF
    // Merge tous les fichiers uploadés en un seul PDF nommé
    // d'après le fournisseur et le numéro d'ordre du bordereau.
    // =========================================================

    /**
     * Fusionner plusieurs fichiers PDF en un seul et le stocker.
     *
     * SRP : cette méthode ne fait QUE traiter les fichiers PDF.
     * Elle n'accède pas à la base de données.
     *
     * @param  array  $files     Tableau de UploadedFile
     * @param  string $fourName  Nom du fournisseur (pour le nom du fichier)
     * @param  int    $count     Numéro d'ordre dans le bordereau courant
     * @return array{fileName: string, filePath: string}
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


    // =========================================================
    // RESPONSABILITÉ 4 : Notifications
    // Envoie des notifications email + base de données aux
    // agents BOF quand une nouvelle facture est soumise.
    // =========================================================

    /**
     * Notifier tous les agents BOF qu'une nouvelle facture est arrivée.
     *
     * SRP : cette méthode ne fait QUE gérer les notifications.
     * Elle ne touche pas à la facture ni au bordereau.
     *
     * @param  Facture $facture     La facture qui vient d'être créée
     * @param  User    $user        L'utilisateur qui a soumis la facture
     * @param  string  $numFacture  Numéro de la facture (pour affichage)
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
                    'titre_reclamation' => null,
                    'nom_creator'      => $user->name,
                ]);
            }


            Notification::where('updated_at', '<', Carbon::now()->subHours(env('NOTIFICATION_DELETE_DELAY', 24)))
                ->where('lu', true)
                ->delete();
        } catch (\Exception $e) {
        }
    }

    // =========================================================
    // RESPONSABILITÉ 5 : Résolution du fournisseur
    // Détermine qui est le fournisseur et qui est l'agent BOF
    // selon le rôle de l'utilisateur connecté.
    // =========================================================

    /**
     * Identifier fournisseur_id et agent_bof_id selon le rôle.
     *
     * SRP : cette méthode ne fait QUE résoudre les identités.
     * Elle ne crée rien en base et ne notifie personne.
     *
     * @param  int         $roleId     ID du rôle de l'utilisateur (2 = BOF, 3 = Fournisseur)
     * @param  User        $user       Utilisateur connecté
     * @param  string|null $idFiscale  Identifiant fiscal fourni dans la requête
     * @return array{fournisseur_id: int|null, agent_bof_id: int|null}
     */


    private function resoudreFournisseur(int $roleId, User $user, ?string $idFiscale): array
    {
        if ($roleId === 3) {
            // Fournisseur qui soumet sa propre facture
            return [$user->id, null];
        }

        // Agent BOF qui saisit pour le compte d'un fournisseur
        $fournisseur = $idFiscale
            ? User::where('idFiscale', $idFiscale)->first()
            : null;

        return [$fournisseur?->id, $user->id];
    }


    // =========================================================
    // RESPONSABILITÉ 6 : Vérification des droits de modification
    // Vérifie qu'un utilisateur a le droit de modifier une facture
    // donnée selon son rôle et son identité.
    // =========================================================

    /**
     * Vérifier si l'utilisateur peut modifier la facture.
     *
     * SRP : cette méthode ne fait QUE vérifier les droits.
     * Elle ne modifie rien en base.
     *
     * @return array{success: bool, message: string}
     */
    public function verifierDroitModification(Facture $facture, User $user, object $role): array
    {
        // La facture doit être en état "En attente" (etat_id = 1)
        if ($facture->etat_id !== 1) {
            return [
                'success' => false,
                'message' => "Vous ne pouvez pas modifier une facture en cours de traitement",
            ];
        }

        // Un agent BOF ne peut modifier que ce qu'il a créé
        if ($role->id === 2 && $facture->agent_bof_id !== $user->id) {
            return [
                'success' => false,
                'message' => "Vous n'êtes pas autorisé à modifier cette facture",
            ];
        }

        // Un fournisseur ne peut modifier que sa propre facture
        if ($role->id === 3 && $facture->fournisseur_id !== $user->id) {
            return [
                'success' => false,
                'message' => "Vous n'êtes pas autorisé à modifier cette facture",
            ];
        }

        return ['success' => true, 'message' => 'Autorisation accordée'];
    }
 
    // =========================================================
    // RESPONSABILITÉ 7 : Mise à jour d'une facture existante
    // Persiste les modifications sur une facture déjà créée,
    // sans retoucher au PDF ni au bordereau.
    // =========================================================

    /**
     * Mettre à jour les champs d'une facture existante.
     *
     * SRP : cette méthode ne fait QUE mettre à jour les données
     * métier de la facture. Elle ne gère ni PDF ni notifications.
     *
     * @param  Facture $facture  La facture à modifier
     * @param  array   $data     Données validées et résolues
     */
    public function mettreAJourFacture(Facture $facture, array $data): Facture
    {
        $facture->update([
            'number'            => $data['number']            ?? $facture->number,
            'invoice_name'      => $data['invoice_name']      ?? $facture->invoice_name,
            'organization'      => $data['organization']      ?? $facture->organization,
            'billing_date'      => $data['billing_date']      ?? $facture->billing_date,
            'amount'            => $data['amount']            ?? $facture->amount,
            'currency'          => $data['currency']          ?? $facture->currency,
            'payment_period'    => $data['payment_period']    ?? $facture->payment_period,
            'objet_facture_id'  => $data['objet_facture_id']  ?? $facture->objet_facture_id,
            'pieces_jointes'    => $data['pieces_jointes']    ?? $facture->pieces_jointes,
            'bon_de_commande_id' => $data['bon_de_commande_id'] ?? $facture->bon_de_commande_id,
            'fournisseur_id'    => $data['fournisseur_id']    ?? $facture->fournisseur_id,
            'agent_bof_id'      => $data['agent_bof_id']      ?? $facture->agent_bof_id,
            'numOp'             => $data['numOp']             ?? $facture->numOp,
            'idFiscale'         => $data['idFiscale']         ?? $facture->idFiscale,
            'structureOrd'      => $data['structureOrd']      ?? $facture->structureOrd,
            'reception_date'    => Carbon::now(),
            'etat_id'           => 1,
            'isArchived'        => false,
        ]);

        return $facture;
    }
 
    // =========================================================
    // RESPONSABILITÉ 8 : Validation du bon de commande
    // Vérifie que le bon de commande existe et qu'il appartient
    // bien au fournisseur identifié par son ID fiscal.
    // =========================================================

    /**
     * Valider et récupérer le bon de commande.
     *
     * SRP : cette méthode ne fait QUE vérifier la cohérence
     * entre le bon de commande et l'identifiant fiscal.
     * Elle ne crée aucune facture.
     *
     * @param  string      $numCommande  Numéro du bon de commande
     * @param  string      $idFiscale    Identifiant fiscal du fournisseur
     * @return array{success: bool, message: string, bonDeCommande: BonDeCommande|null}
     */
    public function validerBonDeCommande(string $numCommande, string $idFiscale): array
    {
        $purOrder = BonDeCommande::where('num_commande', $numCommande)->first();

        if (!$purOrder) {
            return [
                'success'       => false,
                'message'       => 'Vérifiez votre numéro de bon de commande',
                'bonDeCommande' => null,
            ];
        }

        if ($purOrder->four_idFiscale !== $idFiscale) {
            return [
                'success'       => false,
                'message'       => "Ce bon de commande ne correspond pas à votre identifiant fiscal",
                'bonDeCommande' => null,
            ];
        }

        if ($purOrder->hasInvoice) {
            return [
                'success'       => false,
                'message'       => 'Une facture existe déjà pour ce bon de commande',
                'bonDeCommande' => null,
            ];
        }

        return [
            'success'       => true,
            'message'       => 'Bon de commande valide',
            'bonDeCommande' => $purOrder,
        ];
    }

      // =========================================================
    // RESPONSABILITÉ 9 : Création de la facture en base
    // Persiste les données de la facture après que toutes les
    // autres responsabilités ont été résolues.
    // =========================================================

    /**
     * Créer la facture en base de données.
     *
     * SRP : cette méthode ne fait QUE persister la facture.
     * Les PDF, le bordereau et les notifications sont déjà gérés
     * par les autres méthodes du service.
     *
     * @param  array $data  Données validées et résolues
     */
    public function creerFacture(array $data): Facture
    {
        $facture = Facture::create([
            'number'           => $data['number'],
            'invoice_name'     => $data['invoice_name']     ?? null,
            'organization'     => $data['organization']     ?? null,
            'billing_date'     => $data['billing_date'],
            'amount'           => $data['amount'],
            'currency'         => $data['currency'],
            'type_facture_id'  => $data['type_facture_id'],
            'invoice_file_path' => $data['invoice_file_path'],
            'reception_date'   => Carbon::now(),
            'isArchived'       => false,
            'etat_id'          => 1,
            'objet_facture_id' => $data['objet_facture_id'] ?? null,
            'pieces_jointes'   => $data['pieces_jointes']   ?? null,
            'borderau_id'      => $data['borderau_id'],
            'bon_de_commande_id' => $data['bon_de_commande_id'] ?? null,
            'created_by'       => $data['created_by'],
            'fournisseur_id'   => $data['fournisseur_id'],
            'agent_bof_id'     => $data['agent_bof_id'],
            'numOp'            => $data['numOp']            ?? null,
            'idFiscale'        => $data['idFiscale']        ?? null,
            'structureOrd'     => $data['structureOrd']     ?? null,
            'payment_period'   => $data['payment_period']   ?? null,
        ]);

        return $facture;
    }
}
