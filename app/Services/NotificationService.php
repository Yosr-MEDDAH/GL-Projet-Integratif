<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use GuzzleHttp\Client;

class NotificationService
{
    // Instance unique — patron Singleton
    private static ?NotificationService $instance = null;

    private Client $httpClient;

    // Constructeur privé — empêche l'instanciation directe
    private function __construct()
    {
        $this->httpClient = new Client();
    }

    // Clonage interdit
    private function __clone() {}

    // Point d'accès global unique
    public static function getInstance(): NotificationService
    {
        if (self::$instance === null) {
            self::$instance = new NotificationService();
        }
        return self::$instance;
    }

    /**
     * Envoyer une notification mail + créer en base
     */
    public function notifierParMail(
        User $destinataire,
        string $message,
        string $type,
        string $titre,
        array $extra = []
    ): void {
        if ($destinataire->isNotificationsEnabled) {
            try {
                $this->httpClient->post(env('NOTIFICATION_MAIL_URL'), [
                    'json' => [
                        'emails'  => [$destinataire->email],
                        'message' => $message,
                    ]
                ]);
            } catch (\Exception $e) {
                // on continue même si le mail échoue
            }
        }

        Notification::create(array_merge([
            'user_id'         => $destinataire->id,
            'type'            => $type,
            'titre'           => $titre,
            'num_facture'     => null,
            'id_facture'      => null,
            'id_reclamation'  => null,
            'titre_reclamation' => null,
            'nom_creator'     => null,
        ], $extra));
    }

    /**
     * Supprimer les notifications obsolètes lues
     */
    public function supprimerNotificationsObsoletes(): void
    {
        $delai = env('NOTIFICATION_DELETE_DELAY', 24);

        Notification::where('updated_at', '<', Carbon::now()->subHours($delai))
            ->where('lu', true)
            ->delete();
    }

    /**
     * Notifier un groupe d'utilisateurs par rôle
     */
    public function notifierParRole(
        int $roleId,
        string $message,
        string $type,
        string $titre,
        array $extra = []
    ): void {
        $users = User::where('role_id', $roleId)->get();

        foreach ($users as $user) {
            $this->notifierParMail($user, $message, $type, $titre, $extra);
        }

        $this->supprimerNotificationsObsoletes();
    }
}
