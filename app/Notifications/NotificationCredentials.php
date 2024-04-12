<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotificationCredentials extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */

    public $email;

    public $password;


    public function __construct($email, $password)
    {
        $this->email = $email;
        $this->password = $password;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {

        $hour = date('H');

        if ($hour >= 5 && $hour < 18) {
            $greeting = 'Bonjour';
        } else {
            $greeting = 'Bonsoir';
        }

        $url = "url de login";


        return (new MailMessage)
            ->mailer('smtp')
            ->subject("Détails d'accès à notre application")
            ->greeting($greeting . ',')
            ->line('Cher fournisseur,')
            ->line('Nous vous informons que votre accès à notre application a été créé avec succès.')
            ->line('Voici vos identifiants de connexion :')
            ->line('Adresse e-mail : ' . $this->email)
            ->line('Mot de passe : ' . $this->password)
            ->line('Nous vous recommandons vivement de modifier votre mot de passe dès votre première connexion.')
            ->action('Accéder à l\'application', $url)
            ->line('Merci d\'utiliser notre application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
