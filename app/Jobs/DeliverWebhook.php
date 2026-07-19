<?php

namespace App\Jobs;

use App\Models\WebhookEndpoint;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class DeliverWebhook implements ShouldQueue
{
    use Dispatchable,InteractsWithQueue,Queueable,SerializesModels;

    public int $tries = 4;

    public array $backoff = [30, 120, 600];

    public function __construct(public int $endpointId, public array $payload) {}

    public function handle(): void
    {
        $endpoint = WebhookEndpoint::withoutGlobalScope('organization')->find($this->endpointId);
        if (! $endpoint?->is_active) {
            return;
        }$body = json_encode($this->payload, JSON_UNESCAPED_SLASHES);
        $timestamp = now()->timestamp;
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, $endpoint->secret);
        try {
            Http::timeout(8)->withHeaders(['User-Agent' => 'PeopleOS-Webhooks/1.0', 'Content-Type' => 'application/json', 'X-PeopleOS-Event' => $this->payload['type'], 'X-PeopleOS-Delivery' => $this->payload['id'], 'X-PeopleOS-Signature' => 't='.$timestamp.',v1='.$signature])->withBody($body, 'application/json')->post($endpoint->url)->throw();
            $endpoint->update(['failure_count' => 0, 'last_delivered_at' => now()]);
        } catch (\Throwable $e) {
            $endpoint->increment('failure_count');
            if ($endpoint->failure_count >= 10) {
                $endpoint->update(['is_active' => false]);
            }throw $e;
        }
    }
}
