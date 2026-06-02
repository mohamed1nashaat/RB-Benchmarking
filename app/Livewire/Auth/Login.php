<?php

namespace App\Livewire\Auth;

use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Login extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;
    public string $error = '';
    public bool $loading = false;

    protected $rules = [
        'email' => 'required|email',
        'password' => 'required|min:1',
    ];

    public function login()
    {
        $this->validate();
        $this->loading = true;
        $this->error = '';

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            session()->regenerate();

            $user = Auth::user();
            $isSuperAdmin = $user->isSuperAdmin();

            // Set default tenant in session for all users
            if ($isSuperAdmin) {
                $defaultTenant = $user->default_tenant_id
                    ? Tenant::find($user->default_tenant_id)
                    : null;
                $defaultTenant = $defaultTenant ?? Tenant::first();
            } else {
                $defaultTenant = $user->tenants()->where('tenants.id', $user->default_tenant_id)->first()
                    ?? $user->tenants()->first();
            }

            if ($defaultTenant) {
                session(['current_tenant_id' => $defaultTenant->id]);
            }

            return $this->redirect('/ad-accounts', navigate: false);
        }

        $this->loading = false;
        $this->error = __('auth.login_error');
    }

    public function render()
    {
        return view('livewire.auth.login')
            ->layout('layouts.guest', ['title' => __('auth.login')]);
    }
}
