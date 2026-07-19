<?php

namespace App\Models\Concerns;

use App\Models\Organization;
use App\Support\OrganizationContext;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToOrganization
{
    protected static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope('organization', function (Builder $builder) {
            if ($organizationId = app(OrganizationContext::class)->id()) {
                $builder->where($builder->qualifyColumn('organization_id'), $organizationId);
            }
        });

        static::creating(function ($model) {
            if (blank($model->organization_id)) {
                $model->organization_id = app(OrganizationContext::class)->id()
                    ?? Organization::query()->where('is_active', true)->orderBy('id')->value('id');
            }
        });
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        $slug = request()->input('workspace')
            ?? request()->session()->get('organization.slug')
            ?? request()->cookie('peopleos_workspace')
            ?? config('app.default_organization');

        return $query->where($field ?? $this->getRouteKeyName(), $value)
            ->whereHas('organization', fn ($organizations) => $organizations->where('slug', $slug)->where('is_active', true));
    }
}
