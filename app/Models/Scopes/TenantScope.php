<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // Super admins and cross-tenant viewers bypass tenant filtering (see all data)
        if (app()->bound('is_super_admin') && app('is_super_admin')) {
            return;
        }

        if (app()->bound('view_all_tenants') && app('view_all_tenants')) {
            return;
        }

        // Apply tenant filter when a current tenant is set
        if (app()->bound('current_tenant_id')) {
            $builder->where($model->getTable() . '.tenant_id', app('current_tenant_id'));
        }
    }
}
