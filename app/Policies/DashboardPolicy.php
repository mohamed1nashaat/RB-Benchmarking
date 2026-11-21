<?php

namespace App\Policies;

use App\Models\Dashboard;
use App\Models\User;

class DashboardPolicy
{
    /**
     * Determine whether the user can view the dashboard.
     */
    public function view(User $user, Dashboard $dashboard): bool
    {
        return $dashboard->user_id === $user->id || $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can update the dashboard.
     */
    public function update(User $user, Dashboard $dashboard): bool
    {
        return $dashboard->user_id === $user->id || $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can delete the dashboard.
     */
    public function delete(User $user, Dashboard $dashboard): bool
    {
        return $dashboard->user_id === $user->id || $user->isSuperAdmin();
    }
}
