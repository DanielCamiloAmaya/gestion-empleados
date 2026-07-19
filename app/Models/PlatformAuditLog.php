<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'platform_user_id', 'actor_name', 'event', 'target_type', 'target_id',
        'organization_id', 'metadata', 'request_id', 'ip_address', 'user_agent',
        'previous_hash', 'entry_hash', 'created_at',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('La auditoría de plataforma es inmutable.'));
        static::deleting(fn () => throw new \LogicException('La auditoría de plataforma es inmutable.'));
    }

    public function actor()
    {
        return $this->belongsTo(PlatformUser::class, 'platform_user_id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
