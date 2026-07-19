<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class EmployeeInvitation extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'user_id', 'token_hash', 'expires_at', 'accepted_at', 'created_by'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'accepted_at' => 'datetime'];
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
