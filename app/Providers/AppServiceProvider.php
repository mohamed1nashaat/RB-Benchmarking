<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\GoogleSheetsService;
use App\Services\ConversionPixelService;
use App\Services\CurrencyConversionService;
use App\Services\LinkedInAdsService;
use App\Services\TwitterAdsService;
use App\Services\IndustryDetector;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Google Sheets Service
        $this->app->singleton(GoogleSheetsService::class, function ($app) {
            return new GoogleSheetsService();
        });

        // Register Conversion Pixel Service
        $this->app->singleton(ConversionPixelService::class, function ($app) {
            return new ConversionPixelService($app->make(GoogleSheetsService::class));
        });

        // Register Currency Conversion Service
        $this->app->singleton(CurrencyConversionService::class, function ($app) {
            return new CurrencyConversionService();
        });

        // Register Industry Detector Service
        $this->app->singleton(IndustryDetector::class, function ($app) {
            return new IndustryDetector();
        });

        // Register LinkedIn Ads Service
        $this->app->singleton(LinkedInAdsService::class, function ($app) {
            return new LinkedInAdsService(
                $app->make(CurrencyConversionService::class),
                $app->make(IndustryDetector::class)
            );
        });

        // Register Twitter Ads Service
        $this->app->singleton(TwitterAdsService::class, function ($app) {
            return new TwitterAdsService(
                $app->make(CurrencyConversionService::class),
                $app->make(IndustryDetector::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
