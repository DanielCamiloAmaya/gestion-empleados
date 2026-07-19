<?php

namespace App\Services;

use App\Jobs\DeliverWebhook;
use App\Models\WebhookEndpoint;
use Illuminate\Support\Str;

class WebhookService
{
    public function dispatch(string $event, array $data): void
    {
        WebhookEndpoint::where('is_active', true)->get()->filter(fn ($endpoint) => in_array('*', $endpoint->events ?? [], true) || in_array($event, $endpoint->events ?? [], true))->each(fn ($endpoint) => DeliverWebhook::dispatch($endpoint->id, ['id' => (string) Str::uuid(), 'type' => $event, 'created_at' => now()->toIso8601String(), 'data' => $data]));
    }
}
