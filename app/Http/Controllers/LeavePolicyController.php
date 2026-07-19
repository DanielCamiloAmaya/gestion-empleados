<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Models\LeaveBalance;
use App\Models\LeavePolicy;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeavePolicyController extends Controller
{
    public function index()
    {
        return view('leave.settings', ['policies' => LeavePolicy::withCount('balances')->orderBy('name')->get(), 'balances' => LeaveBalance::with(['employee', 'policy'])->where('year', now()->year)->orderBy('user_id')->get(), 'holidays' => Holiday::whereYear('date', now()->year)->orderBy('date')->get(), 'employees' => User::whereIn('employment_status', ['active', 'onboarding'])->orderBy('first_name')->get()]);
    }

    public function policy(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'type' => ['required', Rule::in(['vacation', 'medical', 'personal', 'parental', 'other']), Rule::unique('leave_policies')->where(fn ($q) => $q->where('organization_id', auth('admin')->user()->organization_id))], 'annual_allowance' => ['required', 'numeric', 'between:0,365'], 'carryover_max' => ['required', 'numeric', 'between:0,365'], 'minimum_notice_days' => ['required', 'integer', 'between:0,365'], 'maximum_consecutive_days' => ['nullable', 'integer', 'between:1,365']]);
        LeavePolicy::create($data);

        return back()->with('success', 'Politica de ausencia creada.');
    }

    public function balance(Request $request)
    {
        $data = $request->validate(['user_id' => ['required', Rule::exists('users', 'id')], 'leave_policy_id' => ['required', Rule::exists('leave_policies', 'id')], 'year' => ['required', 'integer', 'between:2020,2100'], 'allocated' => ['required', 'numeric', 'between:0,365'], 'carried_over' => ['required', 'numeric', 'between:0,365'], 'adjustment' => ['required', 'numeric', 'between:-365,365']]);
        LeaveBalance::updateOrCreate(['user_id' => $data['user_id'], 'leave_policy_id' => $data['leave_policy_id'], 'year' => $data['year']], $data);

        return back()->with('success', 'Saldo actualizado con trazabilidad.');
    }

    public function holiday(Request $request)
    {
        $data = $request->validate(['date' => ['required', 'date'], 'name' => ['required', 'string', 'max:120']]);
        Holiday::updateOrCreate(['date' => $data['date']], $data);

        return back()->with('success', 'Festivo agregado al calendario laboral.');
    }
}
