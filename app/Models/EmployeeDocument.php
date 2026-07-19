<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class EmployeeDocument extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'user_id', 'uploaded_by', 'title', 'category', 'original_name', 'storage_path', 'mime_type', 'size_bytes', 'sha256', 'requires_signature', 'expires_at'];

    protected function casts(): array
    {
        return ['requires_signature' => 'boolean', 'expires_at' => 'date', 'size_bytes' => 'integer'];
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function uploader()
    {
        return $this->belongsTo(Admin::class, 'uploaded_by');
    }

    public function signatures()
    {
        return $this->hasMany(DocumentSignature::class);
    }
}
