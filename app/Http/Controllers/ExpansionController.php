<?php

namespace App\Http\Controllers;

use App\Models\AttendanceEntry;
use App\Models\Candidate;
use App\Models\CompensationRecord;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Departamento;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExpansionController extends Controller
{
    public function index()
    {
        return view('expansion.index', ['jobs' => JobPosting::with(['department', 'candidates'])->latest()->get(), 'attendance' => AttendanceEntry::with('employee')->latest('clocked_in_at')->limit(30)->get(), 'openEntry' => auth()->check() ? AttendanceEntry::where('user_id', auth()->id())->whereNull('clocked_out_at')->latest()->first() : null, 'courses' => Course::withCount(['enrollments', 'enrollments as completed_count' => fn ($q) => $q->where('status', 'completed')])->get(), 'enrollments' => CourseEnrollment::with(['course', 'employee'])->when(! auth('admin')->check(), fn ($q) => $q->where('user_id', auth()->id()))->latest()->get(), 'compensations' => auth('admin')->check() ? CompensationRecord::with('employee')->latest('effective_from')->limit(20)->get() : collect(), 'employees' => auth('admin')->check() ? User::where('employment_status', 'active')->orderBy('first_name')->get() : collect(), 'departments' => auth('admin')->check() ? Departamento::where('is_active', true)->orderBy('nombre')->get() : collect()]);
    }

    public function job(Request $r)
    {
        $d = $r->validate(['title' => ['required', 'string', 'max:150'], 'department_id' => ['nullable', Rule::exists('departamentos', 'id')], 'location' => ['nullable', 'string', 'max:120'], 'employment_type' => ['required', Rule::in(['full_time', 'part_time', 'contractor', 'intern'])], 'description' => ['required', 'string', 'min:30', 'max:5000'], 'closes_at' => ['nullable', 'date', 'after:today']]);
        JobPosting::create([...$d, 'status' => 'open']);

        return back()->with('success', 'Vacante publicada en el pipeline.');
    }

    public function candidate(Request $r)
    {
        $d = $r->validate(['job_posting_id' => ['required', Rule::exists('job_postings', 'id')], 'name' => ['required', 'string', 'max:150'], 'email' => ['required', 'email'], 'phone' => ['nullable', 'string', 'max:30'], 'notes' => ['nullable', 'string', 'max:2000']]);
        Candidate::create($d);

        return back()->with('success', 'Candidato incorporado al proceso.');
    }

    public function candidateStage(Request $r, Candidate $candidate)
    {
        $d = $r->validate(['stage' => ['required', Rule::in(['applied', 'screening', 'interview', 'offer', 'hired', 'rejected'])], 'score' => ['nullable', 'integer', 'between:1,5']]);
        $candidate->update($d);

        return back()->with('success', 'Etapa del candidato actualizada.');
    }

    public function clock(Request $r)
    {
        abort_unless(auth()->check(), 403);
        $open = AttendanceEntry::where('user_id', auth()->id())->whereNull('clocked_out_at')->latest()->first();
        if ($open) {
            $open->update(['clocked_out_at' => now(), 'clock_out_ip' => $r->ip()]);

            return back()->with('success', 'Jornada cerrada correctamente.');
        }AttendanceEntry::create(['user_id' => auth()->id(), 'clocked_in_at' => now(), 'clock_in_ip' => $r->ip(), 'source' => 'web']);

        return back()->with('success', 'Inicio de jornada registrado.');
    }

    public function course(Request $r)
    {
        $d = $r->validate(['title' => ['required', 'string', 'max:180'], 'provider' => ['nullable', 'string', 'max:120'], 'description' => ['nullable', 'string', 'max:3000'], 'duration_minutes' => ['required', 'integer', 'between:5,10000'], 'is_mandatory' => ['nullable', 'boolean']]);
        Course::create([...$d, 'is_mandatory' => $r->boolean('is_mandatory')]);

        return back()->with('success', 'Curso agregado al catálogo.');
    }

    public function enroll(Request $r)
    {
        $d = $r->validate(['course_id' => ['required', Rule::exists('courses', 'id')], 'user_id' => ['required', Rule::exists('users', 'id')], 'due_date' => ['nullable', 'date', 'after_or_equal:today']]);
        CourseEnrollment::updateOrCreate(['course_id' => $d['course_id'], 'user_id' => $d['user_id']], $d);

        return back()->with('success', 'Formación asignada.');
    }

    public function complete(Request $r, CourseEnrollment $enrollment)
    {
        abort_unless(auth()->check() && $enrollment->user_id === auth()->id(), 403);
        $d = $r->validate(['score' => ['nullable', 'integer', 'between:0,100']]);
        $enrollment->update([...$d, 'status' => 'completed', 'completed_at' => now()]);

        return back()->with('success', 'Formación completada y registrada.');
    }

    public function compensation(Request $r)
    {
        $d = $r->validate(['user_id' => ['required', Rule::exists('users', 'id')], 'base_salary' => ['required', 'numeric', 'min:0'], 'currency' => ['required', 'string', 'size:3'], 'frequency' => ['required', Rule::in(['hourly', 'monthly', 'annual'])], 'variable_target' => ['required', 'numeric', 'min:0'], 'pay_grade' => ['nullable', 'string', 'max:40'], 'effective_from' => ['required', 'date']]);
        CompensationRecord::create([...$d, 'created_by' => auth('admin')->id()]);

        return back()->with('success', 'Compensación registrada en el historial confidencial.');
    }
}
