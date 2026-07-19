<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = AuditLog::query()
            ->when($request->filled('event'), fn ($query) => $query->where('event', $request->input('event')))
            ->latest('created_at')->paginate(20)->withQueryString();

        return view('audit.index', compact('logs'));
    }
}
