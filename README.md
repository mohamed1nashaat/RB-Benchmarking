# RB Benchmarks

A comprehensive ad performance benchmarking platform for digital marketing agencies. Compare campaign metrics against industry standards, track client performance, and generate actionable insights across multiple advertising platforms.

## Features

### Multi-Platform Integration
- **Facebook Ads** - Full campaign sync with metrics and insights
- **Google Ads** - Campaign performance tracking and analysis
- **TikTok Ads** - Video campaign benchmarking
- **LinkedIn Ads** - B2B campaign metrics
- **Snapchat Ads** - Social campaign tracking
- **Twitter Ads** - Engagement and conversion metrics

### Industry Benchmarking
- Compare performance against industry standards
- Objective-based benchmarks (Awareness, Leads, Sales, Traffic, Engagement)
- Regional benchmarks with GCC focus
- Custom benchmark categories per industry

### Client Management
- Multi-tenant architecture with client isolation
- Client dashboards with logo and branding
- Health scores and performance alerts
- Automated client onboarding wizard

### Analytics & Reporting
- Real-time KPI calculations (CPM, CPL, CPA, ROAS, CTR)
- Objective-aware metric displays
- Currency conversion support (USD, SAR, AED, etc.)
- Scheduled PDF/Excel reports
- Custom date range filtering

### Alerts & Monitoring
- Performance anomaly detection
- Budget threshold alerts
- Sync health monitoring
- Email notifications

## Tech Stack

### Backend
- Laravel 11 (PHP 8.2+)
- MySQL 8
- Laravel Sanctum (Authentication)
- Queue workers for background jobs

### Frontend
- Vue 3 + TypeScript
- Pinia (State Management)
- TailwindCSS
- Chart.js
- Vue I18n (English/Arabic)

### Integrations
- Google Sheets API (Auto-sync)
- Platform OAuth flows
- CSV import/export

## Installation

```bash
# Clone repository
git clone https://github.com/mohamed1nashaat/RB-Benchmarking.git
cd RB-Benchmarking

# Install dependencies
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Seed industry data
php artisan db:seed --class=IndustrySeeder
php artisan db:seed --class=IndustryBenchmarkSeeder

# Build assets
npm run build

# Start server
php artisan serve
```

## Configuration

### Platform API Keys

Add to `.env`:

```env
# Facebook
FACEBOOK_APP_ID=
FACEBOOK_APP_SECRET=

# Google Ads
GOOGLE_ADS_CLIENT_ID=
GOOGLE_ADS_CLIENT_SECRET=
GOOGLE_ADS_DEVELOPER_TOKEN=

# TikTok
TIKTOK_APP_ID=
TIKTOK_APP_SECRET=

# LinkedIn
LINKEDIN_CLIENT_ID=
LINKEDIN_CLIENT_SECRET=

# Snapchat
SNAPCHAT_CLIENT_ID=
SNAPCHAT_CLIENT_SECRET=

# Google Sheets (for auto-sync)
GOOGLE_SHEETS_CREDENTIALS_PATH=
```

## Usage

### Syncing Campaigns

```bash
# Sync Facebook campaigns
php artisan sync:facebook-campaigns

# Sync Google Ads campaigns
php artisan sync:google-ads-campaigns

# Backfill historical metrics
php artisan backfill:facebook-metrics --months=6
```

### Scheduled Tasks

```bash
# Check performance alerts
php artisan alerts:check

# Generate scheduled reports
php artisan reports:generate

# Detect anomalies
php artisan detect:anomalies
```

## API Endpoints

### Authentication
- `POST /api/auth/login` - Login
- `POST /api/auth/logout` - Logout
- `GET /api/me` - Current user

### Metrics
- `GET /api/metrics/summary` - Dashboard summary
- `GET /api/metrics/timeseries` - Time-based metrics
- `GET /api/benchmarks` - Industry benchmarks

### Clients
- `GET /api/clients` - List clients
- `POST /api/clients` - Create client
- `GET /api/clients/{id}/dashboard` - Client dashboard

### Reports
- `POST /api/reports/export` - Generate export
- `GET /api/scheduled-reports` - Scheduled reports

## License

Proprietary - Red Bananas Digital Agency

## Support

For support, contact the development team.

### Semrush AI visibility exports

The AI Visibility tab includes a Semrush export explorer with brand, report month,
country, platform, and text filters. Source CSVs remain in private storage.
Import paired `brand_topics_<domain>_worldwide.csv` and
`prompts_<domain>_worldwide.csv` exports (including filenames with ` (1)` suffixes):

```bash
php artisan semrush:import storage/app/private/semrush-verified-exports \
  --tenant=160 --domain=alshifahoney.com --date=2026-11-10
```

The date is the capture date supplied by the operator; CSVs do not contain a report
period. Each domain must have both exports. Competitor domains must match websites
already registered for that client. Imports run in a transaction and replace the
same client/domain/month report when repeated. Only Semrush topic and prompt counts
and the client's top 15 topics update existing dashboard summaries; existing overall
AI scores, Google analytics and Clarity values are preserved.

The November 10 archive contains 286 topic-country rows and 680
prompt-country-platform records across AlShifa, Abou Nayef Honey and AlHawwaj.
AlShifa has 78 topic rows and 191 response records (149 distinct prompt texts).
There are no duplicate prompt/country/platform combinations in this archive.
Mentions and citations are independent flags and overlap. `sources_count` and
`mentioned_brands_count` describe all sources/brands in a response, not the target
brand's citation count. Topic IDs remain strings because some exceed signed 64-bit
integer limits. Topic visibility is not an overall brand AI score. Search volume,
intent distributions, undated 12-value volume trends and response excerpts are
retained; the files contain neither citation URLs nor complete response text.

### AI visibility workspace

The client AI visibility tab opens on Overview, with four headline metrics, two
traffic trends, and a compact score comparison. Competitors, Traffic & conversions,
Citations, and Topic & prompt research render separately. Data & settings contains
Google setup, imports, sync controls, and the snapshot editor; Google property lists
load only when settings is first opened.

Competitor metrics are grouped into Visibility, Cited sources, Audience estimates,
and Measured analytics. Semrush report dates and analytics periods are labelled
separately, with reported zeroes preserved. Research retains brand/month/country/
platform/search filters, and expanded record tables stay open during filtering.
Chart instances are destroyed when leaving a section or changing the traffic period.
