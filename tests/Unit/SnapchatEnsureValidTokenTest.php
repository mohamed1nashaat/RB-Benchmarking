<?php

namespace Tests\Unit;

use App\Models\Integration;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SnapchatAdsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SnapchatEnsureValidTokenTest extends TestCase
{
    use RefreshDatabase;

    private SnapchatAdsService $service;
    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SnapchatAdsService();
        $this->tenant = Tenant::factory()->active()->create();
        $this->user = User::factory()->create(['default_tenant_id' => $this->tenant->id]);
    }

    public function test_returns_existing_token_when_not_expired(): void
    {
        $integration = Integration::factory()->create([
            'tenant_id' => $this->tenant->id,
            'created_by' => $this->user->id,
            'platform' => 'snapchat',
            'app_config' => [
                'access_token' => 'valid-token-123',
                'refresh_token' => 'refresh-token-456',
                'expires_at' => time() + 3600, // 1 hour from now
            ],
        ]);

        $token = $this->service->ensureValidToken($integration);

        $this->assertEquals('valid-token-123', $token);
    }

    public function test_refreshes_token_when_expired(): void
    {
        Http::fake([
            'accounts.snapchat.com/*' => Http::response([
                'access_token' => 'new-access-token',
                'refresh_token' => 'new-refresh-token',
                'expires_in' => 3600,
            ]),
        ]);

        $integration = Integration::factory()->create([
            'tenant_id' => $this->tenant->id,
            'created_by' => $this->user->id,
            'platform' => 'snapchat',
            'app_config' => [
                'access_token' => 'old-expired-token',
                'refresh_token' => 'refresh-token-456',
                'expires_at' => time() - 600, // expired 10 minutes ago
            ],
        ]);

        $token = $this->service->ensureValidToken($integration);

        $this->assertEquals('new-access-token', $token);

        // Verify integration was updated in database
        $integration->refresh();
        $this->assertEquals('new-access-token', $integration->app_config['access_token']);
        $this->assertEquals('new-refresh-token', $integration->app_config['refresh_token']);
    }

    public function test_returns_null_when_no_token(): void
    {
        $integration = Integration::factory()->create([
            'tenant_id' => $this->tenant->id,
            'created_by' => $this->user->id,
            'platform' => 'snapchat',
            'app_config' => [],
        ]);

        $token = $this->service->ensureValidToken($integration);

        $this->assertNull($token);
    }

    public function test_returns_existing_token_when_refresh_fails(): void
    {
        Http::fake([
            'accounts.snapchat.com/*' => Http::response([
                'error' => 'invalid_grant',
                'error_description' => 'Refresh token is invalid',
            ], 400),
        ]);

        $integration = Integration::factory()->create([
            'tenant_id' => $this->tenant->id,
            'created_by' => $this->user->id,
            'platform' => 'snapchat',
            'app_config' => [
                'access_token' => 'old-token',
                'refresh_token' => 'bad-refresh-token',
                'expires_at' => time() - 600,
            ],
        ]);

        $token = $this->service->ensureValidToken($integration);

        // Should fall back to existing token
        $this->assertEquals('old-token', $token);
    }
}
