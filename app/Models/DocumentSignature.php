<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentSignature extends Model
{
    protected $fillable = ['employee_document_id', 'user_id', 'signer_name', 'document_sha256', 'signature_hash', 'ip_address', 'user_agent', 'signed_at'];

    protected function casts(): array
    {
        return ['signed_at' => 'datetime'];
    }

    public function document()
    {
        return $this->belongsTo(EmployeeDocument::class, 'employee_document_id');
    }

    public function signer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
