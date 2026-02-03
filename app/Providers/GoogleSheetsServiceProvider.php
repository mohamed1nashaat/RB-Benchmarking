<?php

namespace App\Providers;

use App\Events\CampaignCreated;
use App\Listeners\AutoCreateGoogleSheet;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class GoogleSheetsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register event listeners for Google Sheets automation
        Event::listen(
            CampaignCreated::class,
            AutoCreateGoogleSheet::class
        );
    }
}