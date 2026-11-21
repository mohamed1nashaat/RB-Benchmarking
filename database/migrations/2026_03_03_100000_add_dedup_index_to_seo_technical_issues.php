<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('CREATE INDEX seo_issues_dedup_idx ON seo_technical_issues (tenant_id, issue_type, page_url(255))');
        } else {
            // SQLite doesn't support prefix indexes — use full column
            Schema::table('seo_technical_issues', function (Blueprint $table) {
                $table->index(['tenant_id', 'issue_type', 'page_url'], 'seo_issues_dedup_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::table('seo_technical_issues', function (Blueprint $table) {
            $table->dropIndex('seo_issues_dedup_idx');
        });
    }
};
