<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\PlatformAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlatformAuditService
{
    public function record(
        Request $request,
        string $event,
        Model|string $target,
        array $metadata = [],
        ?Organization $organization = null,
    ): PlatformAuditLog {
        return DB::transaction(function () use ($request, $event, $target, $metadata, $organization) {
            $actor = auth('platform')->user();
            $previous = PlatformAuditLog::query()->lockForUpdate()->latest('id')->first();
            $createdAt = now();
            $targetType = is_string($target) ? $target : $target::class;
            $targetId = is_string($target) ? null : (string) $target->getKey();
            $normalized = $this->normalize($metadata);
            $payload = json_encode([
                'actor_id' => $actor?->getKey(),
                'event' => $event,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'organization_id' => $organization?->getKey(),
                'metadata' => $normalized,
                'request_id' => $request->attributes->get('request_id'),
                'created_at' => $createdAt->toIso8601String(),
                'previous_hash' => $previous?->entry_hash,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            return PlatformAuditLog::create([
                'platform_user_id' => $actor?->getKey(),
                'actor_name' => $actor?->name ?? 'Sistema PeopleOS',
                'event' => $event,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'organization_id' => $organization?->getKey(),
                'metadata' => $normalized,
                'request_id' => $request->attributes->get('request_id'),
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
                'previous_hash' => $previous?->entry_hash,
                'entry_hash' => hash_hmac('sha256', $payload, (string) config('app.key')),
                'created_at' => $createdAt,
            ]);
        });
    }

    public function verifyChain(): bool
    {
        $previousHash = null;
        foreach (PlatformAuditLog::query()->orderBy('id')->get() as $entry) {
            if (! hash_equals((string) $entry->previous_hash, (string) $previousHash)) {
                return false;
            }
            $payload = json_encode([
                'actor_id' => $entry->platform_user_id,
                'event' => $entry->event,
                'target_type' => $entry->target_type,
                'target_id' => $entry->target_id,
                'organization_id' => $entry->organization_id,
                'metadata' => $this->normalize($entry->metadata ?? []),
                'request_id' => $entry->request_id,
                'created_at' => $entry->created_at->toIso8601String(),
                'previous_hash' => $entry->previous_hash,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if (! hash_equals($entry->entry_hash, hash_hmac('sha256', $payload, (string) config('app.key')))) {
                return false;
            }
            $previousHash = $entry->entry_hash;
        }

        return true;
    }

    private function normalize(array $values): array
    {
        ksort($values);
        foreach ($values as &$value) {
            if (is_array($value)) {
                $value = $this->normalize($value);
            }
        }

        return $values;
    }
}
