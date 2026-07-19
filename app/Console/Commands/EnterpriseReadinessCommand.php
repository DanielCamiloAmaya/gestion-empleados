<?php

namespace App\Console\Commands;

use App\Services\PlatformAuditService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EnterpriseReadinessCommand extends Command
{
    protected $signature = 'peopleos:readiness
        {--profile=application : application or production}
        {--json : Emit machine-readable JSON}';

    protected $description = 'Verify application integrity and production topology requirements';

    public function handle(PlatformAuditService $platformAudit): int
    {
        $profile = (string) $this->option('profile');
        if (! in_array($profile, ['application', 'production'], true)) {
            $this->error('Profile must be application or production.');

            return self::INVALID;
        }

        $checks = [
            'application_key' => $this->check(filled(config('app.key')), 'APP_KEY is configured'),
            'database' => $this->attempt(function (): void {
                DB::select('select 1');
            }, 'Database accepts queries'),
            'storage' => $this->attempt(function (): void {
                $path = 'readiness/'.Str::uuid().'.probe';
                Storage::disk(config('filesystems.default'))->put($path, 'ok');
                if (Storage::disk(config('filesystems.default'))->get($path) !== 'ok') {
                    throw new \RuntimeException('Storage round trip failed.');
                }
                Storage::disk(config('filesystems.default'))->delete($path);
            }, 'Private storage supports a write/read/delete round trip'),
            'platform_audit_chain' => $this->check($platformAudit->verifyChain(), 'Platform audit hash chain is valid'),
        ];

        if ($profile === 'production') {
            $checks += [
                'debug_disabled' => $this->check(! config('app.debug'), 'APP_DEBUG is disabled'),
                'production_environment' => $this->check(app()->environment('production'), 'APP_ENV is production'),
                'durable_sessions' => $this->check(in_array(config('session.driver'), ['database', 'redis'], true), 'Sessions use database or Redis'),
                'distributed_cache' => $this->check(config('cache.default') === 'redis', 'Cache uses Redis'),
                'durable_queue' => $this->check(in_array(config('queue.default'), ['redis', 'sqs', 'database'], true), 'Queue is asynchronous and durable'),
                'object_storage' => $this->check(config('filesystems.default') === 's3', 'Files use private object storage'),
                'secure_cookies' => $this->check((bool) config('session.secure'), 'Session cookies require HTTPS'),
            ];
        }

        $ready = collect($checks)->every(fn (array $check) => $check['ok']);
        $payload = [
            'status' => $ready ? 'ready' : 'blocked',
            'profile' => $profile,
            'checked_at' => now()->toIso8601String(),
            'checks' => $checks,
        ];

        if ($this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        } else {
            $this->table(['Control', 'Resultado', 'Detalle'], collect($checks)->map(
                fn (array $check, string $name) => [$name, $check['ok'] ? 'PASS' : 'FAIL', $check['detail']]
            )->values()->all());
            $this->{$ready ? 'info' : 'error'}($ready ? 'Readiness verificado.' : 'La liberación está bloqueada.');
        }

        return $ready ? self::SUCCESS : self::FAILURE;
    }

    private function check(bool $ok, string $detail): array
    {
        return compact('ok', 'detail');
    }

    private function attempt(callable $callback, string $detail): array
    {
        try {
            $callback();

            return $this->check(true, $detail);
        } catch (\Throwable $exception) {
            return $this->check(false, $detail.': '.$exception->getMessage());
        }
    }
}
