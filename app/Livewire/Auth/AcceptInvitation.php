<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Models\UserInvitation;
use App\Services\UserInvitationService;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class AcceptInvitation extends Component
{
    public string $token = '';
    public string $status = 'loading'; // loading, valid, expired, invalid, success
    public ?array $invitation = null;

    // Form fields for new users
    public string $name = '';
    public string $password = '';
    public string $password_confirmation = '';

    public bool $loading = false;
    public string $error = '';

    public function mount(string $token)
    {
        $this->token = $token;
        $this->verifyInvitation();
    }

    protected function verifyInvitation()
    {
        try {
            $service = app(UserInvitationService::class);
            $result = $service->verify($this->token);

            if (!$result) {
                $this->status = 'invalid';
                return;
            }

            if ($result['expired']) {
                $this->status = 'expired';
                return;
            }

            if ($result['accepted']) {
                $this->status = 'invalid';
                return;
            }

            $this->invitation = [
                'email' => $result['email'],
                'role' => $result['role'],
                'tenant_name' => $result['tenant']['name'] ?? '',
                'invited_by' => $result['inviter']['name'] ?? '',
                'is_existing_user' => $result['existing_user'],
            ];

            $this->status = 'valid';
        } catch (\Exception $e) {
            $this->status = 'invalid';
        }
    }

    public function accept()
    {
        $this->loading = true;
        $this->error = '';

        try {
            $service = app(UserInvitationService::class);

            if ($this->invitation['is_existing_user']) {
                $service->accept($this->token);
            } else {
                if ($this->password !== $this->password_confirmation) {
                    $this->error = __('pages.invitation.passwords_mismatch');
                    $this->loading = false;
                    return;
                }

                $this->validate([
                    'name' => 'required|min:2',
                    'password' => 'required|min:8',
                ]);

                $service->accept($this->token, $this->name, $this->password);
            }

            $this->status = 'success';
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        } finally {
            $this->loading = false;
        }
    }

    public function render()
    {
        return view('livewire.auth.accept-invitation')
            ->layout('layouts.guest', ['title' => __('pages.invitation.title')]);
    }
}
