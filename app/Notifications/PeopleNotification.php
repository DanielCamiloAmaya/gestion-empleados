<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PeopleNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly array $payload) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->payload['title'],
            'body' => $this->payload['body'],
            'url' => $this->payload['url'] ?? null,
            'severity' => $this->payload['severity'] ?? 'info',
            'category' => $this->payload['category'] ?? 'operations',
            'occurred_at' => now()->toIso8601String(),
        ];
    }
}
