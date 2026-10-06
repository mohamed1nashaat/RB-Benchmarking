<?php

namespace Tests\Feature;

use App\Livewire\Pages\Users;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\TenantRole;
use App\Models\User;
use App\Notifications\TenantInvitationNotification;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    private function adminUser(Tenant $tenant): User
    {
        $user = User::factory()->create(['default_tenant_id' => $tenant->id]);
        $adminRole = TenantRole::where('tenant_id', $tenant->id)->where('name', 'admin')->first();
        $tenant->users()->attach($user->id, [
            'role' => 'admin',
            'tenant_role_id' => $adminRole->id,
            'joined_at' => now(),
        ]);

        return $user;
    }

    private function viewerUser(Tenant $tenant): User
    {
        $user = User::factory()->create(['default_tenant_id' => $tenant->id]);
        $viewerRole = TenantRole::where('tenant_id', $tenant->id)->where('name', 'viewer')->first();
        $tenant->users()->attach($user->id, [
            'role' => 'viewer',
            'tenant_role_id' => $viewerRole->id,
            'joined_at' => now(),
        ]);

        return $user;
    }

    public function test_admin_can_access_users_page(): void
    {
        $tenant = Tenant::factory()->create();
        $user = $this->adminUser($tenant);

        $response = $this->actingAs($user)->get('/users');

        $response->assertStatus(200);
    }

    public function test_viewer_without_permission_cannot_access_users_page(): void
    {
        $tenant = Tenant::factory()->create();
        $user = $this->viewerUser($tenant);

        $response = $this->actingAs($user)->get('/users');

        $response->assertStatus(403);
    }

    public function test_admin_can_invite_user_with_tenant_role(): void
    {
        Notification::fake();

        $tenant = Tenant::factory()->create();
        $admin = $this->adminUser($tenant);
        $viewerRole = TenantRole::where('tenant_id', $tenant->id)->where('name', 'viewer')->first();

        $this->actingAs($admin);
        session(['current_tenant_id' => $tenant->id]);

        Livewire::test(Users::class)
            ->set('inviteEmail', 'invited@redbananas.com')
            ->set('inviteRoleId', $viewerRole->id)
            ->call('sendInvitation')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('user_invitations', [
            'tenant_id' => $tenant->id,
            'email' => 'invited@redbananas.com',
            'tenant_role_id' => $viewerRole->id,
        ]);

        $invitation = \App\Models\UserInvitation::where('email', 'invited@redbananas.com')->first();
        $this->assertNotNull($invitation);
        Notification::assertSentTo($invitation, TenantInvitationNotification::class);
    }

    public function test_admin_cannot_invite_user_with_non_redbananas_email(): void
    {
        Notification::fake();

        $tenant = Tenant::factory()->create();
        $admin = $this->adminUser($tenant);
        $viewerRole = TenantRole::where('tenant_id', $tenant->id)->where('name', 'viewer')->first();

        $this->actingAs($admin);
        session(['current_tenant_id' => $tenant->id]);

        Livewire::test(Users::class)
            ->set('inviteEmail', 'invited@example.com')
            ->set('inviteRoleId', $viewerRole->id)
            ->call('sendInvitation')
            ->assertHasErrors(['inviteEmail']);

        $this->assertDatabaseMissing('user_invitations', [
            'tenant_id' => $tenant->id,
            'email' => 'invited@example.com',
        ]);
    }

    public function test_admin_can_change_user_role(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = $this->adminUser($tenant);
        $viewer = $this->viewerUser($tenant);
        $adminRole = TenantRole::where('tenant_id', $tenant->id)->where('name', 'admin')->first();

        $this->actingAs($admin);
        session(['current_tenant_id' => $tenant->id]);

        Livewire::test(Users::class)
            ->call('changeUserRole', $viewer->id, $adminRole->id);

        $this->assertDatabaseHas('tenant_users', [
            'tenant_id' => $tenant->id,
            'user_id' => $viewer->id,
            'tenant_role_id' => $adminRole->id,
            'role' => 'admin',
        ]);
    }

    public function test_user_with_manage_roles_permission_can_access_roles_page(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['default_tenant_id' => $tenant->id]);
        $role = TenantRole::create([
            'tenant_id' => $tenant->id,
            'name' => 'role-manager',
            'display_name' => 'Role Manager',
            'is_system' => false,
        ]);
        $manageRolesPermission = Permission::where('name', 'manage_roles')->first();
        $viewUsersPermission = Permission::where('name', 'view_users')->first();
        $role->permissions()->sync([$manageRolesPermission->id, $viewUsersPermission->id]);

        $tenant->users()->attach($user->id, [
            'role' => 'viewer',
            'tenant_role_id' => $role->id,
            'joined_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/roles');

        $response->assertStatus(200);
    }

    public function test_user_without_manage_roles_permission_cannot_access_roles_page(): void
    {
        $tenant = Tenant::factory()->create();
        $user = $this->viewerUser($tenant);

        $response = $this->actingAs($user)->get('/roles');

        $response->assertStatus(403);
    }
}
