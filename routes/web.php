<?php

use App\Http\Controllers\Api\FacebookIntegrationController;
use App\Http\Controllers\Api\GoogleAdsIntegrationController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Api\LinkedInIntegrationController;
use App\Http\Controllers\Api\SnapchatIntegrationController;
use App\Http\Controllers\Api\TikTokIntegrationController;
use App\Http\Controllers\Api\TwitterIntegrationController;
use App\Http\Controllers\AdAccountExportController;
use App\Http\Controllers\SeoReportExportController;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\AcceptInvitation;
use App\Livewire\Pages;
use App\Livewire\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
});

// Public pages (no auth required)
Route::view('/privacy-policy', 'pages.privacy-policy')->name('privacy-policy');
Route::get('/privacy', fn () => redirect('/privacy-policy'));
Route::view('/terms-of-service', 'pages.terms-of-service')->name('terms-of-service');
Route::get('/terms', fn () => redirect('/terms-of-service'));
Route::get('/invitation/{token}', AcceptInvitation::class)->name('accept-invitation');

// OAuth callbacks — controller redirects
Route::get('/facebook-callback', [FacebookIntegrationController::class, 'handleCallback'])->name('facebook-callback');
Route::get('/linkedin-callback', [LinkedInIntegrationController::class, 'handleCallback'])->name('linkedin-callback');

// Authenticated routes
Route::middleware(['auth', 'set-locale', 'web-tenant', 'csp'])->group(function () {
    Route::get('/', fn () => redirect('/benchmarks'));

    Route::get('/ad-accounts', Pages\AdAccounts::class)->name('ad-accounts');
    Route::get('/ad-accounts/export/excel', [AdAccountExportController::class, 'excel'])->name('ad-accounts-export-excel');
    Route::get('/ad-accounts/{id}', Pages\AdAccountDetail::class)->name('ad-account-detail');
    Route::get('/spend-verification', Pages\SpendVerification::class)->middleware('super-admin')->name('spend-verification');
    Route::get('/benchmarks', Pages\Benchmarks::class)->name('benchmarks');
    Route::get('/global-benchmarks', Pages\GlobalBenchmarks::class)->name('global-benchmarks');
    Route::get('/calculator', Pages\Calculator::class)->name('calculator');
    Route::get('/industry-overview', Pages\IndustryOverview::class)->middleware('super-admin')->name('industry-overview');
    Route::get('/industry-management', Pages\IndustryManagement::class)->middleware('super-admin')->name('industry-management');
    Route::get('/clients', Pages\Clients::class)->name('clients');
    Route::get('/clients/{id}/dashboard', Pages\ClientDashboard::class)->name('client-dashboard');
    Route::get('/clients/{id}/seo-report', Pages\SeoReport::class)->name('client-seo-report');
    Route::get('/clients/{id}/seo-report/pdf', [SeoReportExportController::class, 'pdf'])->name('client-seo-report-pdf');
    Route::get('/clients/{id}/seo-report/excel', [SeoReportExportController::class, 'excel'])->name('client-seo-report-excel');
    Route::get('/campaigns/{campaignId}/metrics', Pages\CampaignMetrics::class)->name('campaign-metrics');
    Route::get('/integrations', Pages\Integrations::class)->name('integrations');

    // OAuth redirect routes for platform connections
    Route::get('/auth/facebook/redirect', function (Request $request) {
        $controller = app(FacebookIntegrationController::class);
        $response = $controller->initiateOAuth($request);
        $data = $response->getData(true);
        return redirect($data['oauth_url'] ?? '/integrations?error=oauth_failed');
    })->name('auth.facebook.redirect');

    Route::get('/auth/google-ads/redirect', function (Request $request) {
        $controller = app(GoogleAdsIntegrationController::class);
        $response = $controller->getAuthUrl($request);
        $data = $response->getData(true);
        if (!($data['success'] ?? true)) {
            return redirect('/integrations?error=' . urlencode($data['message'] ?? 'Google Ads not configured'));
        }
        return redirect($data['oauth_url'] ?? '/integrations?error=oauth_failed');
    })->name('auth.google-ads.redirect');

    Route::get('/auth/google-sheets/redirect', function (Request $request) {
        $controller = app(GoogleAuthController::class);
        $response = $controller->getAuthUrl($request);
        $data = $response->getData(true);
        return redirect($data['auth_url'] ?? '/integrations?error=oauth_failed');
    })->name('auth.google-sheets.redirect');

    Route::get('/auth/tiktok/redirect', function (Request $request) {
        $controller = app(TikTokIntegrationController::class);
        $response = $controller->redirect($request);
        $data = $response->getData(true);
        return redirect($data['oauth_url'] ?? '/integrations?error=oauth_failed');
    })->name('auth.tiktok.redirect');

    Route::get('/auth/snapchat/redirect', function (Request $request) {
        $controller = app(SnapchatIntegrationController::class);
        $response = $controller->redirect($request);
        $data = $response->getData(true);
        return redirect($data['oauth_url'] ?? '/integrations?error=oauth_failed');
    })->name('auth.snapchat.redirect');

    Route::get('/auth/linkedin/redirect', function (Request $request) {
        $controller = app(LinkedInIntegrationController::class);
        $response = $controller->initiateOAuth($request);
        $data = $response->getData(true);
        return redirect($data['oauth_url'] ?? '/integrations?error=oauth_failed');
    })->name('auth.linkedin.redirect');

    Route::get('/auth/twitter/redirect', function (Request $request) {
        $controller = app(TwitterIntegrationController::class);
        $response = $controller->initiateOAuth($request);
        $data = $response->getData(true);
        return redirect($data['oauth_url'] ?? '/integrations?error=oauth_failed');
    })->name('auth.twitter.redirect');
    Route::get('/settings', Pages\Settings::class)->name('settings');
    Route::get('/users', Pages\Users::class)->middleware('can:view_users')->name('users');
    Route::get('/roles', Pages\RoleManagement::class)->middleware('can:manage_roles')->name('roles');

    Route::post('/logout', function () {
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();
        return redirect('/login');
    })->name('logout');
});

