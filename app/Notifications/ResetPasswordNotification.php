<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public $code;

    /**
     * Create a new notification instance.
     */
    public function __construct($code)
    {
        $this->code = $code;
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

        $resetUrl = 'http://localhost:5173/newPassword?t=' . $this->code;

        return (new MailMessage)
            ->mailer('smtp')
            ->subject('Réinitialisation de votre mot de passe')
            ->greeting($greeting . ',')
            ->line('Vous recevez cet e-mail car nous avons reçu une demande de réinitialisation de mot de passe pour votre compte.')
            ->action('Réinitialiser le mot de passe', $resetUrl)
            ->line('Ce lien de réinitialisation de mot de passe expirera dans 10 minutes')
            ->line('Si vous n\'avez pas demandé de réinitialisation de mot de passe, aucune autre action n\'est requise.')
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
