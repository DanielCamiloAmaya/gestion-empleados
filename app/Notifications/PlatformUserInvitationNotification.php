<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PlatformUserInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $plainToken) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Invitación al PeopleOS Control Center')
            ->greeting('Acceso interno de alta confianza')
            ->line('Fuiste invitado al Control Center de PeopleOS. Tu acceso será individual, limitado por función y auditado.')
            ->action('Activar cuenta interna', route('control.invitation.show', $this->plainToken))
            ->line('La invitación vence en 24 horas. Después de definir tu contraseña deberás activar MFA.');
    }
}
