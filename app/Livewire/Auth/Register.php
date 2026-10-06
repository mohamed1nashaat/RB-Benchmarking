<?php

namespace App\Livewire\Auth;

use App\Models\Tenant;
use App\Models\TenantRole;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class Register extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public bool $loading = false;
    public string $error = '';

    public function register()
    {
        $allowedDomain = 'redbananas.com';

        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|regex:/@' . preg_quote($allowedDomain, '/') . '$/i|unique:users,email',
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'email.regex' => __('pages.users.email_domain_not_allowed', ['domain' => $allowedDomain]),
        ]);

        $this->loading = true;
        $this->error = '';

        try {
            $tenant = Tenant::whereHas('roles', fn ($q) => $q->where('name', 'viewer'))
                ->first()
                ?? Tenant::first();

            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'default_tenant_id' => $tenant?->id,
                'is_super_admin' => true,
                'view_all_tenants' => true,
                'email_verified_at' => now(),
            ]);

            if ($tenant) {
                $role = TenantRole::where('tenant_id', $tenant->id)
                    ->where('name', 'viewer')
                    ->first()
                    ?? TenantRole::where('tenant_id', $tenant->id)->first();

                if ($role) {
                    $user->tenants()->attach($tenant->id, [
                        'role' => $role->is_system ? $role->name : 'viewer',
                        'tenant_role_id' => $role->id,
                        'joined_at' => now(),
                    ]);
                }
            }

            Auth::login($user);
            session()->regenerate();

            if ($tenant) {
                session(['current_tenant_id' => $tenant->id]);
            }

            return $this->redirect('/ad-accounts', navigate: false);
        } catch (\Exception $e) {
            $this->error = __('auth.registration_failed') . ': ' . $e->getMessage();
            $this->loading = false;
        }
    }

    public function render()
    {
        return view('livewire.auth.register')
            ->layout('layouts.guest', ['title' => __('auth.register')]);
    }
}
