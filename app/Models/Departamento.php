<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Departamento extends Model
{
    use BelongsToOrganization, HasFactory;

    protected $fillable = ['organization_id', 'nombre', 'description', 'cost_center', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function empleados()
    {
        return $this->hasMany(User::class);
    }
}
