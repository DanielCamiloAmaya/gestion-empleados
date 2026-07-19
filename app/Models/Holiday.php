<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'date', 'name', 'country_code'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }
}
