<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\EmployeeDocument;
use App\Models\User;
use App\Services\DeliverableMalwareScanner;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EmployeeDocumentController extends Controller
{
    public function index(Request $request)
    {
        $query = EmployeeDocument::with(['employee', 'signatures'])->latest();
        if (! auth('admin')->check()) {
            $query->where('user_id', auth()->id());
        }
        $query->when($request->filled('category'), fn ($q) => $q->where('category', $request->input('category')));

        return view('documents.index', ['documents' => $query->paginate(20)->withQueryString(), 'employees' => auth('admin')->check() ? User::orderBy('first_name')->get() : collect()]);
    }

    public function store(Request $request, DeliverableMalwareScanner $scanner, NotificationService $notifications)
    {
        abort_unless(auth('admin')->user()?->hasPermission('documents.manage'), 403);
        $data = $request->validate(['user_id' => ['required', Rule::exists('users', 'id')], 'title' => ['required', 'string', 'max:180'], 'category' => ['required', Rule::in(['contract', 'policy', 'certificate', 'payroll', 'identity', 'other'])], 'file' => ['required', 'file', 'max:25600', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,odt,ods,rtf,csv,txt,png,jpg,jpeg'], 'requires_signature' => ['nullable', 'boolean'], 'expires_at' => ['nullable', 'date', 'after:today']]);
        abort_unless(User::whereKey($data['user_id'])->exists(), 422, 'La persona no pertenece a este espacio de trabajo.');
        $scanner->assertClean($request->file('file')->getRealPath());
        $file = $request->file('file');
        $extension = Str::lower($file->getClientOriginalExtension());
        $path = $file->storeAs('org-'.auth('admin')->user()->organization_id.'/employee-'.$data['user_id'], Str::uuid().($extension ? '.'.$extension : ''), 'employee_documents');
        try {
            $document = DB::transaction(function () use ($request, $data, $file, $path) {
                $document = EmployeeDocument::create([...$data, 'uploaded_by' => auth('admin')->id(), 'original_name' => Str::limit(basename(str_replace('\\', '/', $file->getClientOriginalName())), 240, ''), 'storage_path' => $path, 'mime_type' => $file->getMimeType() ?: 'application/octet-stream', 'size_bytes' => $file->getSize(), 'sha256' => hash_file('sha256', $file->getRealPath()), 'requires_signature' => $request->boolean('requires_signature')]);
                AuditLog::record($request, 'document.published', $document, [], ['title' => $document->title, 'employee_id' => $document->user_id, 'requires_signature' => $document->requires_signature]);

                return $document;
            });
        } catch (\Throwable $e) {
            Storage::disk('employee_documents')->delete($path);
            throw $e;
        }
        $notifications->employee($document->employee, ['title' => 'Nuevo documento laboral', 'body' => $document->title, 'url' => route('documents.index'), 'category' => 'document']);

        return back()->with('success', 'Documento publicado de forma privada.');
    }

    public function download(EmployeeDocument $document)
    {
        abort_unless(auth('admin')->check() || $document->user_id === auth()->id(), 403);
        abort_unless(Storage::disk('employee_documents')->exists($document->storage_path), 404);

        return Storage::disk('employee_documents')->download($document->storage_path, $document->original_name, ['Content-Type' => $document->mime_type, 'X-Content-Type-Options' => 'nosniff']);
    }

    public function sign(Request $request, EmployeeDocument $document)
    {
        abort_unless(auth()->check() && $document->user_id === auth()->id() && $document->requires_signature, 403);
        abort_if($document->signatures()->where('user_id', auth()->id())->exists(), 409, 'El documento ya fue firmado.');
        $data = $request->validate(['signer_name' => ['required', 'string', 'max:180'], 'consent' => ['accepted']]);
        if (Str::lower(Str::squish($data['signer_name'])) !== Str::lower(Str::squish(auth()->user()->full_name))) {
            return back()->withErrors(['signer_name' => 'Escribe tu nombre completo exactamente como aparece en tu perfil.']);
        }
        $signedAt = now();
        $signature = $document->signatures()->create(['user_id' => auth()->id(), 'signer_name' => $data['signer_name'], 'document_sha256' => $document->sha256, 'signature_hash' => hash('sha256', implode('|', [$document->sha256, auth()->id(), $signedAt->toIso8601String(), Str::random(32)])), 'ip_address' => $request->ip(), 'user_agent' => Str::limit((string) $request->userAgent(), 500, ''), 'signed_at' => $signedAt]);
        AuditLog::record($request, 'document.signed', $signature, [], ['document_id' => $document->id, 'document_sha256' => $document->sha256]);

        return back()->with('success', 'Firma electrónica registrada con sello de integridad y trazabilidad.');
    }
}
