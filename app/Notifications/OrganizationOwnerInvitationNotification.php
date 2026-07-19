<?php

namespace App\Notifications;

use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrganizationOwnerInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Organization $organization,
        private readonly string $plainToken,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Activa la administración de '.$this->organization->name)
            ->greeting('Tu espacio empresarial está listo')
            ->line('PeopleOS te ha designado como propietario inicial de '.$this->organization->name.'.')
            ->action('Activar cuenta de propietario', route('organization-owner-invitations.show', $this->plainToken))
            ->line('La invitación es de un solo uso y vence en 72 horas. PeopleOS nunca te enviará una contraseña.');
    }
}
