<?php

namespace App\Livewire\Pages;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class Settings extends Component
{
    use WithFileUploads;

    public string $activeTab = 'general';

    // Profile form
    public string $profileName = '';
    public bool $savingProfile = false;
    public bool $profileSuccess = false;
    public string $profileError = '';

    // Avatar
    public $avatarUpload;
    public bool $uploadingAvatar = false;
    public bool $avatarSuccess = false;
    public string $avatarError = '';

    // Password form
    public string $currentPassword = '';
    public string $newPassword = '';
    public string $newPasswordConfirmation = '';
    public bool $changingPassword = false;
    public bool $passwordSuccess = false;
    public string $passwordError = '';

    // Notification settings (stored in user preferences or session)
    public bool $emailAlerts = true;
    public bool $inAppNotifications = true;
    public bool $weeklySummary = false;

    // Display preferences
    public string $defaultDateRange = '30d';

    // Report branding
    public string $brandingLogoUrl = '';
    public string $brandingColor = '#EF4444';
    public string $brandingFooterText = '';
    public bool $brandingSuccess = false;
    public string $brandingError = '';

    public function mount(): void
    {
        $user = auth()->user();
        $this->profileName = $user->name ?? '';

        // Load preferences from session/user settings
        $this->emailAlerts = session('pref_emailAlerts', true);
        $this->inAppNotifications = session('pref_inApp', true);
        $this->weeklySummary = session('pref_weeklySummary', false);
        $this->defaultDateRange = session('pref_defaultDateRange', '30d');

        $tenant = auth()->user()->tenant;
        if ($tenant) {
            $this->brandingLogoUrl    = $tenant->logo_url ?? '';
            $this->brandingColor      = $tenant->brand_color ?? '#EF4444';
            $this->brandingFooterText = $tenant->report_footer_text ?? '';
        }
    }

    public function getUserProperty()
    {
        return auth()->user();
    }

    public function getUserInitialsProperty(): string
    {
        $user = auth()->user();
        if (!$user || !$user->name) return '';

        return strtoupper(
            implode('', array_map(fn($w) => $w[0] ?? '', array_slice(explode(' ', $user->name), 0, 2)))
        );
    }

    // Profile save
    public function saveProfile(): void
    {
        $this->savingProfile = true;
        $this->profileSuccess = false;
        $this->profileError = '';

        try {
            $this->validate([
                'profileName' => 'required|string|max:255',
            ]);

            $user = auth()->user();
            $user->update(['name' => $this->profileName]);

            $this->profileSuccess = true;
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->profileError = collect($e->errors())->flatten()->first();
        } catch (\Exception $e) {
            $this->profileError = __('pages.settings.profile.update_failed');
        } finally {
            $this->savingProfile = false;
        }
    }

    // Avatar upload
    public function updatedAvatarUpload(): void
    {
        $this->validate([
            'avatarUpload' => 'image|max:2048', // 2MB max
        ]);

        $this->uploadAvatar();
    }

    public function uploadAvatar(): void
    {
        if (!$this->avatarUpload) return;

        $this->uploadingAvatar = true;
        $this->avatarSuccess = false;
        $this->avatarError = '';

        try {
            $user = auth()->user();

            // Delete old avatar if exists
            if ($user->avatar) {
                Storage::disk('public')->delete('avatars/' . $user->avatar);
            }

            // Store new avatar
            $filename = 'avatar_' . $user->id . '_' . time() . '.' . $this->avatarUpload->getExtension();
            $this->avatarUpload->storeAs('avatars', $filename, 'public');

            $user->update(['avatar' => $filename]);

            $this->avatarSuccess = true;
            $this->avatarUpload = null;
        } catch (\Exception $e) {
            $this->avatarError = __('pages.settings.profile.avatar_upload_failed');
        } finally {
            $this->uploadingAvatar = false;
        }
    }

    public function removeAvatar(): void
    {
        $this->uploadingAvatar = true;
        $this->avatarSuccess = false;
        $this->avatarError = '';

        try {
            $user = auth()->user();

            if ($user->avatar) {
                Storage::disk('public')->delete('avatars/' . $user->avatar);
                $user->update(['avatar' => null]);
            }

            $this->avatarSuccess = true;
        } catch (\Exception $e) {
            $this->avatarError = __('pages.settings.profile.avatar_remove_failed');
        } finally {
            $this->uploadingAvatar = false;
        }
    }

    // Password change
    public function changePassword(): void
    {
        $this->changingPassword = true;
        $this->passwordSuccess = false;
        $this->passwordError = '';

        try {
            $this->validate([
                'currentPassword' => 'required',
                'newPassword' => 'required|min:8',
                'newPasswordConfirmation' => 'required|same:newPassword',
            ], [
                'newPasswordConfirmation.same' => __('pages.settings.password.mismatch'),
            ]);

            $user = auth()->user();

            if (!Hash::check($this->currentPassword, $user->password)) {
                $this->passwordError = __('pages.settings.password.current_incorrect');
                $this->changingPassword = false;
                return;
            }

            $user->update([
                'password' => Hash::make($this->newPassword),
            ]);

            // Clear form
            $this->currentPassword = '';
            $this->newPassword = '';
            $this->newPasswordConfirmation = '';

            $this->passwordSuccess = true;
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->passwordError = collect($e->errors())->flatten()->first();
        } catch (\Exception $e) {
            $this->passwordError = __('pages.settings.password.update_failed');
        } finally {
            $this->changingPassword = false;
        }
    }

    // Notification preferences
    public function updatedEmailAlerts(): void
    {
        session(['pref_emailAlerts' => $this->emailAlerts]);
    }

    public function updatedInAppNotifications(): void
    {
        session(['pref_inApp' => $this->inAppNotifications]);
    }

    public function updatedWeeklySummary(): void
    {
        session(['pref_weeklySummary' => $this->weeklySummary]);
    }

    // Display preferences
    public function updatedDefaultDateRange(): void
    {
        session(['pref_defaultDateRange' => $this->defaultDateRange]);
    }

    // Report branding
    public function saveBranding(): void
    {
        $this->brandingSuccess = false;
        $this->brandingError   = '';

        try {
            $this->validate([
                'brandingLogoUrl'    => 'nullable|url|max:500',
                'brandingColor'      => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
                'brandingFooterText' => 'nullable|string|max:255',
            ]);

            $tenant = auth()->user()->tenant;
            $tenant->update([
                'logo_url'            => $this->brandingLogoUrl ?: null,
                'brand_color'         => $this->brandingColor,
                'report_footer_text'  => $this->brandingFooterText ?: null,
            ]);

            $this->brandingSuccess = true;
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->brandingError = collect($e->errors())->flatten()->first();
        } catch (\Exception $e) {
            $this->brandingError = 'Failed to save branding settings.';
        }
    }

    public function render()
    {
        return view('livewire.pages.settings')
            ->layout('layouts.app', ['title' => __('pages.settings.title')]);
    }
}
