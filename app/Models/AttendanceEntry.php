<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class AttendanceEntry extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'user_id', 'clocked_in_at', 'clocked_out_at', 'clock_in_ip', 'clock_out_ip', 'source', 'note'];

    protected function casts(): array
    {
        return ['clocked_in_at' => 'datetime', 'clocked_out_at' => 'datetime'];
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getDurationMinutesAttribute(): int
    {
        return $this->clocked_out_at ? (int) $this->clocked_in_at->diffInMinutes($this->clocked_out_at) : 0;
    }
}
