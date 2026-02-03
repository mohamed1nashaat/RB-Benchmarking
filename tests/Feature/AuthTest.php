<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'default_tenant_id' => $tenant->id
        ]);

        $tenant->users()->attach($user->id, ['role' => 'admin']);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'user' => ['id', 'name', 'email'],
                    'token',
                    'tenants',
                    'is_super_admin'
                ]);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['default_tenant_id' => $tenant->id]);
        $tenant->users()->attach($user->id, ['role' => 'admin']);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    public function test_authenticated_user_can_get_profile(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['default_tenant_id' => $tenant->id]);
        $tenant->users()->attach($user->id, ['role' => 'admin']);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/me');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'user' => ['id', 'name', 'email'],
                    'current_tenant',
                    'role',
                    'is_super_admin',
                    'tenants'
                ]);
    }
}
