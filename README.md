# Adintel - Ad Intelligence Platform

A production-ready SaaS platform for multi-tenant ad intelligence and analytics, built with Laravel 11, Vue 3, and MySQL 8.

## 🚀 Features

### Core Features (MVP)
- **Multi-tenant Architecture** - Complete tenant isolation with role-based access (Admin/Viewer)
- **Objective-Aware Analytics** - Dynamic dashboards based on campaign objectives (Awareness, Leads, Sales, Calls)
- **Platform Integrations** - Connect Facebook Ads, Google Ads, and TikTok Ads
- **Real-time KPI Calculations** - Automatic computation of CPM, CPL, ROAS, CPA, and more
- **Advanced Filtering** - Filter by date range, platform, account, and campaign
- **Export Functionality** - CSV and Excel export with async processing
- **Responsive Design** - Mobile-first design with TailwindCSS

### Advanced Features (Roadmap)
- **Smart Alerts** - Automated notifications for performance anomalies
- **AI-Powered Insights** - Predictive analytics and optimization suggestions
- **Financial Intelligence** - Cost tracking and profitability analysis
- **Market Intelligence** - Competitor analysis and industry benchmarks
- **Collaboration Tools** - Team management and client portals

## 🏗️ Architecture

### Backend (Laravel 11)
- **Authentication**: Laravel Sanctum SPA tokens
- **Multi-tenancy**: Global scopes and middleware
- **Database**: MySQL 8 with optimized indexes
- **Queues**: Redis for background job processing
- **API**: RESTful API with comprehensive validation
- **Testing**: PestPHP for unit and integration tests

### Frontend (Vue 3 + TypeScript)
- **State Management**: Pinia stores
- **Routing**: Vue Router with auth guards
- **UI Components**: Headless UI + TailwindCSS
- **Charts**: Chart.js for data visualization
- **Internationalization**: Vue I18n (English/Arabic)
- **Build Tool**: Vite for fast development

### Database Schema
```
tenants (id, name, slug, status, settings)
users (id, name, email, password, default_tenant_id)
tenant_users (tenant_id, user_id, role)
integrations (id, tenant_id, platform, app_config, status)
ad_accounts (id, tenant_id, integration_id, external_account_id, account_name)
ad_campaigns (id, tenant_id, ad_account_id, external_campaign_id, name, objective)
ad_metrics (id, tenant_id, date, platform, spend, impressions, clicks, revenue, etc.)
dashboards (id, tenant_id, user_id, title, objective, is_default)
report_exports (id, tenant_id, user_id, format, status, file_path)
```

## 🛠️ Installation

### Prerequisites
- Docker & Docker Compose
- Git

### Quick Start
```bash
# Clone the repository
git clone <repository-url> RB benchmarks
cd RB-benchmarks

# Copy environment file
cp .env.example .env

# Setup the project (builds containers, installs dependencies, runs migrations, seeds data)
make setup

# Access the application
open https://rb-benchmarks.redbananas.com
```

### Manual Setup
```bash
# Build and start containers
make build
make up

# Install dependencies
make install

# Generate application key
make key

# Run migrations and seed demo data
make migrate
make seed
```

## 🔧 Development

### Available Commands
```bash
make up          # Start all services
make down        # Stop all services
make logs        # View logs
make test        # Run tests
make lint        # Code formatting
make analyze     # Static analysis
make fresh       # Fresh migration with seed data
make cache-clear # Clear all caches
```

### Demo Credentials
- **Admin**: admin@demo.com / password
- **Viewer**: viewer@demo.com / password

### Development Servers
- **Application**: https://rb-benchmarks.redbananas.com
- **Frontend Dev**: https://rb-benchmarks.redbananas.com:5173 (Vite)
- **API**: https://rb-benchmarks.redbananas.com/api

## 📊 Objective-Based Analytics

The platform adapts KPI calculations and dashboard layouts based on campaign objectives:

### Awareness Campaigns
- **Primary KPIs**: CPM (Cost per 1,000 impressions)
- **Secondary KPIs**: Reach, Frequency, VTR (Video View Rate), CTR
- **Charts**: Impressions over time, CPM by campaign

### Lead Generation Campaigns
- **Primary KPIs**: CPL (Cost per Lead), CVR (Conversion Rate)
- **Secondary KPIs**: CTR, CPC
- **Charts**: Leads over time, CPL by campaign

### Sales Campaigns
- **Primary KPIs**: ROAS (Return on Ad Spend), CPA (Cost per Acquisition)
- **Secondary KPIs**: AOV (Average Order Value), CVR, CPC
- **Charts**: Revenue over time, ROAS by campaign

### Call Campaigns
- **Primary KPIs**: Cost per Call
- **Secondary KPIs**: CTR, Call Conversion Rate
- **Charts**: Calls over time, Cost per Call by campaign

## 🔌 Platform Integrations

### Facebook Ads
- App ID, App Secret, Access Token
- Supports Facebook and Instagram campaigns
- Automatic account and campaign discovery

### Google Ads
- Client ID, Client Secret, Developer Token, Refresh Token
- Google Ads API integration
- Campaign performance metrics

### TikTok Ads
- App ID, Secret, Access Token
- TikTok for Business API
- Video-focused metrics

## 🧪 Testing

```bash
# Run all tests
make test

# Run specific test suites
make test-unit
make test-feature

# Run with coverage
docker-compose exec app php artisan test --coverage
```

### Test Coverage
- **Models**: Relationships, scopes, and business logic
- **Controllers**: API endpoints and validation
- **Services**: KPI calculations and data processing
- **Middleware**: Tenant isolation and authentication
- **Integration**: End-to-end API workflows

## 🚀 Deployment

### Production Setup
```bash
# Build production containers
make prod-build

# Start production services
make prod-up

# Run production migrations
docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force
```

### Environment Variables
```env
APP_NAME=Adintel
APP_ENV=production
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=mysql
DB_DATABASE=adintel
DB_USERNAME=adintel
DB_PASSWORD=secure_password

SANCTUM_STATEFUL_DOMAINS=your-domain.com
```

## 📈 Performance Optimization

### Database Optimization
- Composite indexes on (tenant_id, date, platform, ad_account_id)
- Partitioning for large metrics tables
- Query optimization for KPI calculations

### Caching Strategy
- Redis for session storage and queues
- Application-level caching for KPI results
- CDN for static assets

### Queue Processing
- Background jobs for data sync
- Async export processing
- Rate limiting for API calls

## 🔒 Security

### Multi-tenancy Security
- Global scopes ensure tenant isolation
- Middleware validates tenant access
- Policies enforce role-based permissions

### API Security
- Laravel Sanctum SPA authentication
- Rate limiting on all endpoints
- Input validation and sanitization
- CORS configuration

### Data Protection
- Encrypted sensitive configuration
- Secure credential storage
- Audit logging for data access

## 📚 API Documentation

### Authentication
```bash
POST /api/auth/login
POST /api/auth/logout
GET /api/me
```

### Metrics
```bash
GET /api/metrics/summary?from=2024-01-01&to=2024-01-31&objective=sales
GET /api/metrics/timeseries?metric=roas&group_by=date
```

### Exports
```bash
POST /api/reports/export
GET /api/reports/{id}
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Code Standards
- PSR-12 coding standards
- PHPStan level 8 analysis
- Vue 3 Composition API
- TypeScript strict mode

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For support and questions:
- Create an issue on GitHub
- Check the documentation
- Review the demo data and examples

## 🗺️ Roadmap

### Phase 1: MVP ✅
- Multi-tenant architecture
- Basic integrations
- Objective-aware dashboards
- Export functionality

### Phase 2: Automation
- Smart alerts and notifications
- Automated reporting
- Budget optimization suggestions

### Phase 3: Financial Intelligence
- Cost tracking and profitability
- ROI analysis
- Financial forecasting

### Phase 4: AI & Predictive Analytics
- Performance forecasting
- Optimization recommendations
- Anomaly detection

### Phase 5: Market Intelligence
- Competitor analysis
- Industry benchmarks
- Market trend analysis

### Phase 6: Collaboration
- Team management
- Client portals
- White-label solutions

---

Built with ❤️ using Laravel, Vue.js, and modern web technologies.
