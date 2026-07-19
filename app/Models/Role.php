<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'name', 'slug', 'description', 'is_system'];

    protected function casts(): array
    {
        return ['is_system' => 'boolean'];
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }

    public function admins()
    {
        return $this->belongsToMany(Admin::class);
    }
}
