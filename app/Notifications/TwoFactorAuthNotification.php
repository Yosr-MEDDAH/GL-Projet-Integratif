<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwoFactorAuthNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */

    protected $code;
    protected $role;
    public function __construct($code, $name)
    {
        $this->code = $code;
        $this->name = $name;
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

        return (new MailMessage)
            ->mailer('smtp')
            ->subject('Votre Code de Vérification')
            ->greeting($greeting . ', ' . $this->name)
            ->line('Vous avez récemment demandé un code de double authentification pour accéder à votre compte.')
            ->line('Votre code de double authentification est : ' . $this->code)
            ->line('Veuillez utiliser ce code pour finaliser le processus de connexion sécurisée.')
            ->line('Si vous n\'avez pas tenté de vous connecter, veuillez ignorer ce message.')
            ->salutation('Cordialement, ' . 'Direction Centrale Des Finances Tunisie Telecom');
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
