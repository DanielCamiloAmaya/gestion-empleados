<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Services\WebhookService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLog extends Model
{
    use BelongsToOrganization;

    public $timestamps = false;

    protected $fillable = [
        'organization_id', 'actor_type', 'actor_id', 'actor_name', 'event', 'auditable_type',
        'auditable_id', 'old_values', 'new_values', 'ip_address', 'user_agent', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public static function record(Request $request, string $event, Model $subject, array $old = [], array $new = []): void
    {
        $admin = auth('admin')->user();
        $user = auth()->user();
        $actor = $admin ?? $user;

        $audit = static::create([
            'actor_type' => $admin ? 'admin' : 'employee',
            'actor_id' => $actor?->getKey(),
            'actor_name' => $admin?->name ?? $user?->full_name ?? 'System',
            'event' => $event,
            'auditable_type' => $subject::class,
            'auditable_id' => $subject->getKey(),
            'old_values' => self::sanitize($old),
            'new_values' => self::sanitize($new),
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            'created_at' => now(),
        ]);

        app(WebhookService::class)->dispatch($event, [
            'audit_id' => $audit->id,
            'resource_type' => class_basename($subject),
            'resource_id' => $subject->getKey(),
            'actor_type' => $audit->actor_type,
            'actor_id' => $audit->actor_id,
        ]);
    }

    private static function sanitize(array $values): array
    {
        return collect($values)->except(['password', 'remember_token'])->all();
    }
}
