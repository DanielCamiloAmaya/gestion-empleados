<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SecureActionLinkNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $subjectLine,
        private readonly string $message,
        private readonly string $actionLabel,
        private readonly string $url,
        private readonly string $expiry,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subjectLine)
            ->line($this->message)
            ->action($this->actionLabel, $this->url)
            ->line("El enlace es de un solo uso y vence {$this->expiry}. Si no solicitaste esta acción, ignora el mensaje.");
    }
}
