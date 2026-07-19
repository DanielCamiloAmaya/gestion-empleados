<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function ready()
    {
        try {
            DB::select('select 1');
            $db = true;
        } catch (\Throwable) {
            $db = false;
        }$storage = is_writable(storage_path('framework')) && is_writable(storage_path('logs'));
        $ready = $db && $storage;

        return response()->json(['status' => $ready ? 'ready' : 'degraded', 'checks' => ['database' => $db ? 'ok' : 'failed', 'storage' => $storage ? 'ok' : 'failed'], 'timestamp' => now()->toIso8601String()], $ready ? 200 : 503)->header('Cache-Control', 'no-store');
    }
}
