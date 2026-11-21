<?php

namespace Tests\Feature;

use App\Livewire\Auth\Register;
use App\Models\AdAccount;
use App\Models\Integration;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PublicRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_page_loads(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_user_can_register_with_redbananas_email(): void
    {
        $tenant = Tenant::factory()->create();

        Livewire::test(Register::class)
            ->set('name', 'Test User')
            ->set('email', 'test@redbananas.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect('/ad-accounts');

        $this->assertDatabaseHas('users', [
            'email' => 'test@redbananas.com',
            'name' => 'Test User',
        ]);

        $user = User::where('email', 'test@redbananas.com')->first();
        $this->assertTrue($user->tenants()->where('tenants.id', $tenant->id)->exists());
        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_rejects_non_redbananas_email(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['email']);

        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_registration_requires_password_confirmation(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Test User')
            ->set('email', 'test@redbananas.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'different')
            ->call('register')
            ->assertHasErrors(['password']);
    }

    public function test_registered_viewer_can_see_ad_accounts_across_all_tenants(): void
    {
        $tenantA = Tenant::factory()->active()->create();
        $tenantB = Tenant::factory()->active()->create();

        $integrationA = Integration::factory()->create(['tenant_id' => $tenantA->id]);
        $integrationB = Integration::factory()->create(['tenant_id' => $tenantB->id]);

        AdAccount::factory()->create([
            'tenant_id' => $tenantA->id,
            'integration_id' => $integrationA->id,
            'account_name' => 'Account Alpha',
        ]);
        AdAccount::factory()->create([
            'tenant_id' => $tenantB->id,
            'integration_id' => $integrationB->id,
            'account_name' => 'Account Beta',
        ]);

        Livewire::test(Register::class)
            ->set('name', 'Test User')
            ->set('email', 'test@redbananas.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect('/ad-accounts');

        $user = User::where('email', 'test@redbananas.com')->first();
        $this->assertTrue($user->canViewAllTenants());

        $response = $this->actingAs($user)->get('/ad-accounts');
        $response->assertStatus(200);
        $response->assertSeeText('Account Alpha');
        $response->assertSeeText('Account Beta');
    }

    public function test_registered_user_is_super_admin(): void
    {
        Tenant::factory()->active()->create();

        Livewire::test(Register::class)
            ->set('name', 'Test User')
            ->set('email', 'test@redbananas.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect('/ad-accounts');

        $user = User::where('email', 'test@redbananas.com')->first();
        $this->assertTrue($user->isSuperAdmin());
    }

    public function test_registered_super_admin_can_access_industry_management(): void
    {
        Tenant::factory()->active()->create();

        Livewire::test(Register::class)
            ->set('name', 'Test User')
            ->set('email', 'test@redbananas.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect('/ad-accounts');

        $user = User::where('email', 'test@redbananas.com')->first();

        $this->actingAs($user)->get('/industry-management')->assertStatus(200);
    }

    public function test_registered_super_admin_can_access_spend_verification(): void
    {
        Tenant::factory()->active()->create();

        Livewire::test(Register::class)
            ->set('name', 'Test User')
            ->set('email', 'test@redbananas.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect('/ad-accounts');

        $user = User::where('email', 'test@redbananas.com')->first();

        $this->actingAs($user)->get('/spend-verification')->assertStatus(200);
    }

    public function test_registered_super_admin_can_access_industry_overview(): void
    {
        Tenant::factory()->active()->create();

        Livewire::test(Register::class)
            ->set('name', 'Test User')
            ->set('email', 'test@redbananas.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect('/ad-accounts');

        $user = User::where('email', 'test@redbananas.com')->first();

        $this->actingAs($user)->get('/industry-overview')->assertStatus(200);
    }
}
