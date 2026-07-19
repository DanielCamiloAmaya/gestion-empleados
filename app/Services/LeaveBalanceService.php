<?php

namespace App\Services;

use App\Models\LeaveBalance;
use App\Models\LeavePolicy;
use App\Models\User;

class LeaveBalanceService
{
    public function balance(User $employee, LeavePolicy $policy, ?int $year = null): LeaveBalance
    {
        $year ??= (int) now()->year;

        return LeaveBalance::firstOrCreate(
            ['user_id' => $employee->id, 'leave_policy_id' => $policy->id, 'year' => $year],
            ['organization_id' => $employee->organization_id, 'allocated' => $policy->annual_allowance, 'carried_over' => 0, 'adjustment' => 0, 'used' => 0]
        );
    }

    public function syncUsed(User $employee, LeavePolicy $policy, int $year): LeaveBalance
    {
        $balance = $this->balance($employee, $policy, $year);
        $used = $employee->leaveRequests()->where('leave_policy_id', $policy->id)->where('status', 'approved')->whereYear('start_date', $year)->sum('days');
        $balance->update(['used' => $used]);

        return $balance;
    }
}
