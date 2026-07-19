<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Departamento;
use App\Models\EmployeeInvitation;
use App\Models\User;
use App\Services\PlanEnforcementService;
use App\Support\OrganizationContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EmployeeTransferController extends Controller
{
    private const HEADERS = ['employee_code', 'first_name', 'last_name', 'email', 'username', 'job_title', 'department', 'employment_type', 'hire_date', 'phone', 'location'];

    public function create()
    {
        return view('empleados.import');
    }

    public function template()
    {
        return $this->csvResponse('plantilla-empleados.csv', function ($stream) {
            fputcsv($stream, self::HEADERS);
            fputcsv($stream, ['EMP-001', 'Ana', 'Torres', 'ana@empresa.com', 'ana.torres', 'Analista', 'Tecnologia', 'full_time', now()->format('Y-m-d'), '+57 3000000000', 'Bogota']);
        });
    }

    public function store(Request $request, PlanEnforcementService $plans)
    {
        $request->validate(['file' => ['required', 'file', 'max:5120', 'mimes:csv,txt']]);
        [$headers, $rows] = $this->readCsv($request->file('file')->getRealPath());
        $missing = array_diff(self::HEADERS, $headers);
        if ($missing) {
            return back()->withErrors(['file' => 'Faltan columnas obligatorias: '.implode(', ', $missing).'.']);
        }
        if (count($rows) > 2000) {
            return back()->withErrors(['file' => 'Cada importacion admite hasta 2.000 personas. Divide el archivo en lotes controlados.']);
        }

        $organizationId = app(OrganizationContext::class)->id();
        $departments = Departamento::query()->get()->keyBy(fn ($department) => Str::lower(Str::ascii($department->nombre)));
        $validatedRows = [];
        $errors = [];

        foreach ($rows as $index => $values) {
            if (count(array_filter($values, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }
            $row = array_combine($headers, array_pad($values, count($headers), null));
            $department = $departments->get(Str::lower(Str::ascii(trim((string) ($row['department'] ?? '')))));
            $validator = Validator::make($row, [
                'employee_code' => ['required', 'string', 'max:30', Rule::unique('users')->where(fn ($query) => $query->where('organization_id', $organizationId))],
                'first_name' => ['required', 'string', 'max:100'],
                'last_name' => ['required', 'string', 'max:100'],
                'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users')->where(fn ($query) => $query->where('organization_id', $organizationId))],
                'username' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users')->where(fn ($query) => $query->where('organization_id', $organizationId))],
                'job_title' => ['nullable', 'string', 'max:150'],
                'employment_type' => ['required', Rule::in(['full_time', 'part_time', 'contractor', 'intern'])],
                'hire_date' => ['nullable', 'date_format:Y-m-d'],
                'phone' => ['nullable', 'string', 'max:30'],
                'location' => ['nullable', 'string', 'max:150'],
            ]);
            if (! $department) {
                $validator->after(fn ($validator) => $validator->errors()->add('department', 'No existe en este espacio de trabajo.'));
            }
            if ($validator->fails()) {
                $errors[] = 'Fila '.($index + 2).': '.$validator->errors()->first();

                continue;
            }
            $validatedRows[] = [$validator->validated(), $department];
        }

        if ($errors) {
            return back()->withErrors(['file' => $errors]);
        }
        if (! $validatedRows) {
            return back()->withErrors(['file' => 'El archivo no contiene filas para importar.']);
        }
        $plans->assertCanAddEmployees(app(OrganizationContext::class)->organization(), count($validatedRows));

        $invitations = DB::transaction(function () use ($validatedRows, $organizationId, $request) {
            $results = [];
            foreach ($validatedRows as [$data, $department]) {
                unset($data['department']);
                $employee = User::create([
                    ...$data,
                    'organization_id' => $organizationId,
                    'departamento_id' => $department->id,
                    'employment_status' => 'active',
                    'password' => Str::password(40),
                ]);
                $token = Str::random(64);
                EmployeeInvitation::create([
                    'organization_id' => $organizationId,
                    'user_id' => $employee->id,
                    'token_hash' => hash('sha256', $token),
                    'expires_at' => now()->addDays(7),
                    'created_by' => auth('admin')->id(),
                ]);
                AuditLog::record($request, 'employee.imported', $employee, [], ['employee_code' => $employee->employee_code, 'email' => $employee->email]);
                $results[] = [$employee, $token];
            }

            return $results;
        });

        return $this->csvResponse('invitaciones-peopleos-'.now()->format('Ymd-His').'.csv', function ($stream) use ($invitations) {
            fputcsv($stream, ['employee_code', 'name', 'email', 'invitation_url', 'expires_at']);
            foreach ($invitations as [$employee, $token]) {
                fputcsv($stream, [$employee->employee_code, $employee->full_name, $employee->email, route('invitations.accept', ['token' => $token, 'workspace' => app(OrganizationContext::class)->organization()->slug]), now()->addDays(7)->toIso8601String()]);
            }
        });
    }

    public function export(Request $request)
    {
        $employees = User::with('departamento')->orderBy('employee_code')->get();

        return $this->csvResponse('empleados-'.now()->format('Ymd').'.csv', function ($stream) use ($employees) {
            fputcsv($stream, self::HEADERS);
            foreach ($employees as $employee) {
                fputcsv($stream, array_map($this->safeCsv(...), [
                    $employee->employee_code, $employee->first_name, $employee->last_name, $employee->email,
                    $employee->username, $employee->job_title, $employee->departamento?->nombre,
                    $employee->employment_type, $employee->hire_date?->format('Y-m-d'), $employee->phone, $employee->location,
                ]));
            }
        });
    }

    private function readCsv(string $path): array
    {
        $content = file_get_contents($path);
        $content = mb_convert_encoding($content, 'UTF-8', mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true) ?: 'UTF-8');
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $content);
        rewind($handle);
        $firstLine = fgets($handle) ?: '';
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
        rewind($handle);
        $headers = array_map(fn ($header) => Str::lower(trim((string) $header, " \t\n\r\0\x0B\xEF\xBB\xBF")), fgetcsv($handle, separator: $delimiter) ?: []);
        $rows = [];
        while (($row = fgetcsv($handle, separator: $delimiter)) !== false) {
            $rows[] = $row;
        }
        fclose($handle);

        return [$headers, $rows];
    }

    private function csvResponse(string $filename, callable $writer)
    {
        return response()->streamDownload(function () use ($writer) {
            $stream = fopen('php://output', 'wb');
            fwrite($stream, "\xEF\xBB\xBF");
            $writer($stream);
            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8', 'X-Content-Type-Options' => 'nosniff']);
    }

    private function safeCsv(mixed $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@]/', $value) ? "'".$value : $value;
    }
}
