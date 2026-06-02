<?php

namespace Tests\Unit;

use App\Models\AdAccount;
use App\Models\AdCampaign;
use App\Models\AdMetric;
use App\Models\Alert;
use App\Models\Dashboard;
use App\Models\Integration;
use App\Models\ReportExport;
use App\Models\ScheduledReport;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected User $userA;
    protected User $userB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::factory()->active()->create();
        $this->tenantB = Tenant::factory()->active()->create();
        $this->userA = User::factory()->create(['default_tenant_id' => $this->tenantA->id]);
        $this->userB = User::factory()->create(['default_tenant_id' => $this->tenantB->id]);
    }

    protected function setTenant(Tenant $tenant): void
    {
        app()->instance('current_tenant_id', $tenant->id);
        app()->instance('is_super_admin', false);
    }

    protected function setSuperAdmin(): void
    {
        app()->instance('is_super_admin', true);
    }

    public function test_ad_accounts_are_filtered_by_tenant(): void
    {
        $integrationA = Integration::factory()->create(['tenant_id' => $this->tenantA->id, 'created_by' => $this->userA->id]);
        $integrationB = Integration::factory()->create(['tenant_id' => $this->tenantB->id, 'created_by' => $this->userB->id]);

        $accountA = AdAccount::factory()->create(['tenant_id' => $this->tenantA->id, 'integration_id' => $integrationA->id]);
        $accountB = AdAccount::factory()->create(['tenant_id' => $this->tenantB->id, 'integration_id' => $integrationB->id]);

        // Tenant A should only see their account
        $this->setTenant($this->tenantA);
        $accounts = AdAccount::all();
        $this->assertCount(1, $accounts);
        $this->assertEquals($accountA->id, $accounts->first()->id);

        // Tenant B should only see their account
        $this->setTenant($this->tenantB);
        $accounts = AdAccount::all();
        $this->assertCount(1, $accounts);
        $this->assertEquals($accountB->id, $accounts->first()->id);
    }

    public function test_ad_campaigns_are_filtered_by_tenant(): void
    {
        $integrationA = Integration::factory()->create(['tenant_id' => $this->tenantA->id, 'created_by' => $this->userA->id]);
        $accountA = AdAccount::factory()->create(['tenant_id' => $this->tenantA->id, 'integration_id' => $integrationA->id]);

        $integrationB = Integration::factory()->create(['tenant_id' => $this->tenantB->id, 'created_by' => $this->userB->id]);
        $accountB = AdAccount::factory()->create(['tenant_id' => $this->tenantB->id, 'integration_id' => $integrationB->id]);

        AdCampaign::factory()->create(['tenant_id' => $this->tenantA->id, 'ad_account_id' => $accountA->id]);
        AdCampaign::factory()->create(['tenant_id' => $this->tenantB->id, 'ad_account_id' => $accountB->id]);

        $this->setTenant($this->tenantA);
        $this->assertCount(1, AdCampaign::all());

        $this->setTenant($this->tenantB);
        $this->assertCount(1, AdCampaign::all());
    }

    public function test_ad_metrics_are_filtered_by_tenant(): void
    {
        $integrationA = Integration::factory()->create(['tenant_id' => $this->tenantA->id, 'created_by' => $this->userA->id]);
        $accountA = AdAccount::factory()->create(['tenant_id' => $this->tenantA->id, 'integration_id' => $integrationA->id]);
        $campaignA = AdCampaign::factory()->create(['tenant_id' => $this->tenantA->id, 'ad_account_id' => $accountA->id]);

        $integrationB = Integration::factory()->create(['tenant_id' => $this->tenantB->id, 'created_by' => $this->userB->id]);
        $accountB = AdAccount::factory()->create(['tenant_id' => $this->tenantB->id, 'integration_id' => $integrationB->id]);
        $campaignB = AdCampaign::factory()->create(['tenant_id' => $this->tenantB->id, 'ad_account_id' => $accountB->id]);

        AdMetric::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'ad_account_id' => $accountA->id,
            'ad_campaign_id' => $campaignA->id,
        ]);
        AdMetric::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'ad_account_id' => $accountB->id,
            'ad_campaign_id' => $campaignB->id,
        ]);

        $this->setTenant($this->tenantA);
        $this->assertCount(1, AdMetric::all());

        $this->setTenant($this->tenantB);
        $this->assertCount(1, AdMetric::all());
    }

    public function test_integrations_are_filtered_by_tenant(): void
    {
        Integration::factory()->create(['tenant_id' => $this->tenantA->id, 'created_by' => $this->userA->id]);
        Integration::factory()->create(['tenant_id' => $this->tenantB->id, 'created_by' => $this->userB->id]);

        $this->setTenant($this->tenantA);
        $this->assertCount(1, Integration::all());

        $this->setTenant($this->tenantB);
        $this->assertCount(1, Integration::all());
    }

    public function test_alerts_are_filtered_by_tenant(): void
    {
        Alert::factory()->create(['tenant_id' => $this->tenantA->id, 'user_id' => $this->userA->id]);
        Alert::factory()->create(['tenant_id' => $this->tenantB->id, 'user_id' => $this->userB->id]);

        $this->setTenant($this->tenantA);
        $this->assertCount(1, Alert::all());

        $this->setTenant($this->tenantB);
        $this->assertCount(1, Alert::all());
    }

    public function test_dashboards_are_filtered_by_tenant(): void
    {
        Dashboard::factory()->create(['tenant_id' => $this->tenantA->id, 'user_id' => $this->userA->id]);
        Dashboard::factory()->create(['tenant_id' => $this->tenantB->id, 'user_id' => $this->userB->id]);

        $this->setTenant($this->tenantA);
        $this->assertCount(1, Dashboard::all());

        $this->setTenant($this->tenantB);
        $this->assertCount(1, Dashboard::all());
    }

    public function test_report_exports_are_filtered_by_tenant(): void
    {
        ReportExport::factory()->create(['tenant_id' => $this->tenantA->id, 'user_id' => $this->userA->id]);
        ReportExport::factory()->create(['tenant_id' => $this->tenantB->id, 'user_id' => $this->userB->id]);

        $this->setTenant($this->tenantA);
        $this->assertCount(1, ReportExport::all());

        $this->setTenant($this->tenantB);
        $this->assertCount(1, ReportExport::all());
    }

    public function test_scheduled_reports_are_filtered_by_tenant(): void
    {
        ScheduledReport::factory()->create(['tenant_id' => $this->tenantA->id, 'user_id' => $this->userA->id]);
        ScheduledReport::factory()->create(['tenant_id' => $this->tenantB->id, 'user_id' => $this->userB->id]);

        $this->setTenant($this->tenantA);
        $this->assertCount(1, ScheduledReport::all());

        $this->setTenant($this->tenantB);
        $this->assertCount(1, ScheduledReport::all());
    }

    public function test_super_admin_sees_all_records(): void
    {
        Integration::factory()->create(['tenant_id' => $this->tenantA->id, 'created_by' => $this->userA->id]);
        Integration::factory()->create(['tenant_id' => $this->tenantB->id, 'created_by' => $this->userB->id]);

        Alert::factory()->create(['tenant_id' => $this->tenantA->id, 'user_id' => $this->userA->id]);
        Alert::factory()->create(['tenant_id' => $this->tenantB->id, 'user_id' => $this->userB->id]);

        $this->setSuperAdmin();
        $this->assertCount(2, Integration::all());
        $this->assertCount(2, Alert::all());
    }

    public function test_tenant_auto_assigned_on_creation(): void
    {
        $this->setTenant($this->tenantA);

        $integration = Integration::factory()->create([
            'tenant_id' => null,
            'created_by' => $this->userA->id,
        ]);

        $this->assertEquals($this->tenantA->id, $integration->fresh()->tenant_id);
    }

    public function test_cannot_access_other_tenant_record_by_id(): void
    {
        $integrationB = Integration::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'created_by' => $this->userB->id,
        ]);

        $this->setTenant($this->tenantA);

        // find() should return null because tenant scope filters it out
        $result = Integration::find($integrationB->id);
        $this->assertNull($result);
    }

    public function test_soft_deleted_records_are_hidden(): void
    {
        $integration = Integration::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'created_by' => $this->userA->id,
        ]);

        $this->setTenant($this->tenantA);
        $this->assertCount(1, Integration::all());

        $integration->delete();
        $this->assertCount(0, Integration::all());

        // withTrashed should still find it
        $this->assertCount(1, Integration::withTrashed()->get());
    }
}
