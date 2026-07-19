<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use BelongsToOrganization, HasFactory, Notifiable, SoftDeletes;

    protected $attributes = [
        'auth_version' => 1,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'organization_id',
        'last_name',
        'departamento_id',
        'employee_code',
        'job_title',
        'employment_status',
        'employment_type',
        'hire_date',
        'phone',
        'location',
        'manager_id',
        'email',
        'username',
        'password',
        'auth_version',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'hire_date' => 'date',
            'password' => 'hashed',
            'mfa_enabled' => 'boolean',
            'mfa_secret' => 'encrypted',
            'mfa_recovery_codes' => 'array',
            'mfa_confirmed_at' => 'datetime',
            'auth_version' => 'integer',
        ];
    }

    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'departamento_id');
    }

    public function manager()
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    public function directReports()
    {
        return $this->hasMany(self::class, 'manager_id');
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function onboardingTasks()
    {
        return $this->hasMany(OnboardingTask::class);
    }

    public function performanceGoals()
    {
        return $this->hasMany(PerformanceGoal::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getInitialsAttribute(): string
    {
        return mb_strtoupper(mb_substr($this->first_name, 0, 1).mb_substr($this->last_name, 0, 1));
    }
}
