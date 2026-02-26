# Product Requirements Document (PRD)
# RB Benchmarks Platform Upgrade: Customer Portal & Live Dashboard

**Version:** 1.0
**Date:** February 2026
**Author:** Red Bananas Digital
**Status:** Draft

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Background & Problem Statement](#2-background--problem-statement)
3. [Goals & Success Metrics](#3-goals--success-metrics)
4. [User Personas & Use Cases](#4-user-personas--use-cases)
5. [Functional Requirements](#5-functional-requirements)
6. [Non-Functional Requirements](#6-non-functional-requirements)
7. [Technical Architecture](#7-technical-architecture)
8. [Data Architecture](#8-data-architecture)
9. [Security & Compliance](#9-security--compliance)
10. [Integration Requirements](#10-integration-requirements)
11. [UI/UX Requirements](#11-uiux-requirements)
12. [Implementation Phases](#12-implementation-phases)
13. [Risk Assessment](#13-risk-assessment)
14. [Appendices](#14-appendices)

---

## 1. Executive Summary

### 1.1 Purpose

This PRD outlines the comprehensive upgrade of the RB Benchmarks platform to replace the existing Watergraph solution and establish a unified customer portal with live dashboard capabilities. The upgraded platform will serve as the primary interface for internal teams, external clients, and white-label agency partners to access real-time advertising performance data, analytics, and reporting.

### 1.2 Vision Statement

**"Transform RB Benchmarks into an enterprise-grade, real-time customer portal that empowers clients with collaborative self-service capabilities, live performance insights, and seamless multi-channel engagement."**

### 1.3 Scope Overview

| Aspect | Description |
|--------|-------------|
| **Replace** | Watergraph customer portal, dashboard, and reporting functionality |
| **Users** | Internal team, external clients, white-label agencies |
| **Scale** | Enterprise-level (5,000+ concurrent users) |
| **Timeline** | Phased rollout over 18+ months |
| **Branding** | Single brand (Red Bananas) |

### 1.4 Key Deliverables

1. **Customer Portal** - Self-service client access to performance data
2. **Live Dashboard System** - Real-time, auto-refresh, and TV display modes
3. **Collaborative Features** - Comments, annotations, team sharing
4. **Multi-Channel Notifications** - Email, in-app, Slack, SMS, WhatsApp
5. **Data Platform** - API access, warehouse exports, BI connectors
6. **Flexible Authentication** - Email/password, magic link, SSO

---

## 2. Background & Problem Statement

### 2.1 Current State

The existing RB Benchmarks platform provides:

- Multi-tenant ad performance benchmarking
- Integration with 6 advertising platforms (Facebook, Google, TikTok, LinkedIn, Snapchat, Twitter)
- Industry benchmarking and anomaly detection
- Basic dashboard and reporting capabilities
- Role-based access control

**Current Limitations:**
- Watergraph dependency for customer-facing portal
- No real-time data streaming capabilities
- Limited client self-service options
- Fragmented notification system (email only)
- No collaborative features (comments, annotations)
- Insufficient scale for enterprise workloads
- No API access for external data consumers
- Missing TV/display mode for office dashboards

### 2.2 Problem Statement

Red Bananas currently relies on Watergraph as a separate system for client portal and live dashboard functionality. This creates:

1. **Operational Complexity** - Managing two separate platforms
2. **Data Synchronization Issues** - Delays between RB Benchmarks and Watergraph
3. **Limited Customization** - Constrained by Watergraph's feature set
4. **Cost Inefficiency** - Paying for external platform licenses
5. **Poor Client Experience** - Disjointed user journey between systems
6. **Scalability Constraints** - Cannot support enterprise-level growth

### 2.3 Business Drivers

| Driver | Impact |
|--------|--------|
| Cost Reduction | Eliminate Watergraph licensing fees |
| Client Satisfaction | Unified, seamless experience |
| Competitive Advantage | Real-time insights differentiation |
| Operational Efficiency | Single platform management |
| Revenue Growth | Enable premium tier offerings |
| Market Expansion | Support white-label partnerships |

---

## 3. Goals & Success Metrics

### 3.1 Primary Goals

| # | Goal | Description |
|---|------|-------------|
| G1 | **Replace Watergraph** | Full feature parity + enhancements within 18 months |
| G2 | **Enterprise Scale** | Support 5,000+ concurrent users with sub-second response |
| G3 | **Real-Time Experience** | Data freshness within 1-5 minutes |
| G4 | **Client Self-Service** | 80%+ client tasks completable without support |
| G5 | **Unified Platform** | Single system for all user types |

### 3.2 Success Metrics (KPIs)

#### Technical KPIs

| Metric | Target | Measurement |
|--------|--------|-------------|
| Dashboard Load Time | < 2 seconds | P95 latency |
| Real-Time Data Latency | < 5 minutes | Source to display |
| Concurrent Users | 5,000+ | Simultaneous active sessions |
| API Response Time | < 200ms | P95 latency |
| System Uptime | 99.9% | Monthly availability |
| WebSocket Connections | 10,000+ | Concurrent connections |

#### Business KPIs

| Metric | Target | Measurement |
|--------|--------|-------------|
| Client Self-Service Rate | 80%+ | Tasks without support tickets |
| Client NPS Score | 50+ | Quarterly survey |
| Support Ticket Reduction | 40% decrease | Compared to Watergraph era |
| Client Portal Adoption | 90% | Active monthly users |
| Time to Value | < 5 minutes | New client first insight |
| Report Generation Time | < 30 seconds | PDF/Excel exports |

### 3.3 Non-Goals (Explicit Exclusions)

- Native mobile applications (responsive web only)
- Full white-label theming per client (single Red Bananas brand)
- Offline functionality beyond basic PWA caching
- Built-in video conferencing or screen sharing
- Custom domain per client (shared domain)

---

## 4. User Personas & Use Cases

### 4.1 User Personas

#### Persona 1: Agency Account Manager (Internal)

| Attribute | Description |
|-----------|-------------|
| **Role** | Red Bananas team member managing client accounts |
| **Goals** | Monitor all client performance, create reports, respond quickly |
| **Pain Points** | Switching between systems, manual report generation |
| **Tech Savviness** | High |
| **Usage Frequency** | Daily, 4-8 hours |

#### Persona 2: Client Marketing Director (External)

| Attribute | Description |
|-----------|-------------|
| **Role** | Client-side decision maker overseeing ad spend |
| **Goals** | Understand ROI, justify budgets, identify opportunities |
| **Pain Points** | Waiting for reports, unclear metrics, no real-time visibility |
| **Tech Savviness** | Medium |
| **Usage Frequency** | Weekly, 1-2 hours |

#### Persona 3: Client Analyst (External)

| Attribute | Description |
|-----------|-------------|
| **Role** | Client-side analyst needing detailed data |
| **Goals** | Deep-dive analysis, export data, build custom views |
| **Pain Points** | Limited data access, manual exports, no API |
| **Tech Savviness** | High |
| **Usage Frequency** | Daily, 2-4 hours |

#### Persona 4: Agency Partner (White-Label)

| Attribute | Description |
|-----------|-------------|
| **Role** | Partner agency using platform for their clients |
| **Goals** | Manage their own clients, generate branded reports |
| **Pain Points** | Limited customization, multi-tenant complexity |
| **Tech Savviness** | Medium-High |
| **Usage Frequency** | Daily, 4-6 hours |

#### Persona 5: Office Display Viewer

| Attribute | Description |
|-----------|-------------|
| **Role** | Any stakeholder viewing dashboard on office TV |
| **Goals** | At-a-glance performance visibility |
| **Pain Points** | Dashboards not optimized for large displays |
| **Tech Savviness** | N/A (passive viewer) |
| **Usage Frequency** | Continuous display |

### 4.2 Use Cases

#### UC1: Real-Time Performance Monitoring

**Actor:** Client Marketing Director
**Precondition:** User is authenticated and has dashboard access
**Flow:**
1. User logs into customer portal
2. System displays personalized live dashboard
3. KPIs update automatically every 1-5 minutes
4. User sees real-time spend, conversions, ROAS
5. Anomaly alerts highlight issues requiring attention
6. User clicks alert to drill down into details

**Success Criteria:** Data refreshes without manual action, anomalies visible within 5 minutes of occurrence

---

#### UC2: Collaborative Report Review

**Actor:** Client Analyst + Account Manager
**Precondition:** Report has been generated
**Flow:**
1. Account Manager generates performance report
2. System sends notification to Client Analyst
3. Client Analyst opens report in portal
4. Analyst adds comments/annotations to specific sections
5. Account Manager receives notification of comments
6. Both users discuss via threaded comments
7. Final report is approved and exported

**Success Criteria:** Full conversation history preserved, notifications delivered within 30 seconds

---

#### UC3: Self-Service Dashboard Creation

**Actor:** Client Analyst
**Precondition:** User has dashboard creation permissions
**Flow:**
1. User navigates to dashboard builder
2. Selects from available widgets (charts, tables, KPIs)
3. Configures data sources, date ranges, filters
4. Arranges layout with drag-and-drop
5. Saves dashboard and sets as default
6. Shares dashboard with team members
7. Sets up automated email delivery

**Success Criteria:** Dashboard created in < 5 minutes, shareable via link

---

#### UC4: TV Display Mode

**Actor:** Office Display Viewer (Passive)
**Precondition:** Display URL configured
**Flow:**
1. Display loads TV-mode URL with authentication token
2. System renders full-screen dashboard optimized for large display
3. Dashboard auto-cycles through configured views
4. Data refreshes automatically every 1-5 minutes
5. High-priority alerts display prominently
6. Display remains active 24/7 without session timeout

**Success Criteria:** No user interaction required, readable from 10+ feet distance

---

#### UC5: Multi-Channel Alert Notification

**Actor:** Client Marketing Director
**Precondition:** Alerts configured, notification preferences set
**Flow:**
1. System detects budget threshold exceeded
2. Alert engine evaluates user notification preferences
3. Notification sent via preferred channels (Slack + Email)
4. User receives mobile push via Slack
5. User clicks to open portal with alert context
6. User acknowledges alert, stopping further notifications

**Success Criteria:** Notification delivered within 60 seconds across all channels

---

#### UC6: API Data Access

**Actor:** Client Analyst
**Precondition:** API access enabled for client
**Flow:**
1. User generates API key in portal settings
2. User authenticates via API using key
3. User queries campaign metrics endpoint
4. System returns JSON data with requested metrics
5. User imports data into their BI tool
6. Data refreshes on schedule via automated API calls

**Success Criteria:** API response < 200ms, rate limits clearly documented

---

## 5. Functional Requirements

### 5.1 Customer Portal

#### 5.1.1 Authentication & Authorization

| ID | Requirement | Priority |
|----|-------------|----------|
| AUTH-01 | Email/password authentication with secure password requirements | P0 |
| AUTH-02 | Magic link (passwordless) authentication via email | P1 |
| AUTH-03 | SSO integration (Google, Microsoft, SAML 2.0) | P1 |
| AUTH-04 | Multi-factor authentication (MFA) via authenticator app | P1 |
| AUTH-05 | Remember device functionality (30-day sessions) | P2 |
| AUTH-06 | Session management (view/revoke active sessions) | P2 |
| AUTH-07 | Password reset with secure token expiration | P0 |
| AUTH-08 | Account lockout after failed attempts (5 attempts) | P0 |
| AUTH-09 | Audit log of authentication events | P1 |
| AUTH-10 | API key management for programmatic access | P1 |

#### 5.1.2 User Management

| ID | Requirement | Priority |
|----|-------------|----------|
| USER-01 | User invitation workflow with email verification | P0 |
| USER-02 | Role-based access control (Admin, Editor, Viewer, Custom) | P0 |
| USER-03 | Granular permission system (22+ permissions) | P0 |
| USER-04 | User profile management (name, email, avatar, preferences) | P0 |
| USER-05 | Notification preference settings per channel | P1 |
| USER-06 | Team/group management for access control | P1 |
| USER-07 | User activity logging and audit trail | P1 |
| USER-08 | Bulk user import via CSV | P2 |
| USER-09 | User deactivation (soft delete with data retention) | P0 |
| USER-10 | Last login tracking and inactive user reporting | P2 |

#### 5.1.3 Client Self-Service Features

| ID | Requirement | Priority |
|----|-------------|----------|
| SELF-01 | View performance dashboards with date range selection | P0 |
| SELF-02 | Export data to CSV, Excel, PDF | P0 |
| SELF-03 | Create and save custom dashboards | P1 |
| SELF-04 | Configure personal alert thresholds | P1 |
| SELF-05 | Schedule automated report delivery | P1 |
| SELF-06 | Add comments/annotations to reports and dashboards | P1 |
| SELF-07 | Share dashboards with team members | P1 |
| SELF-08 | Set default dashboard and homepage | P2 |
| SELF-09 | Bookmark/favorite specific views | P2 |
| SELF-10 | Download historical data (up to 24 months) | P1 |

### 5.2 Live Dashboard System

#### 5.2.1 Real-Time Updates (WebSocket)

| ID | Requirement | Priority |
|----|-------------|----------|
| RT-01 | WebSocket connection for live data streaming | P0 |
| RT-02 | Automatic reconnection on connection loss | P0 |
| RT-03 | Data push within 1-5 minutes of source update | P0 |
| RT-04 | Visual indicator showing data freshness/last update | P0 |
| RT-05 | Selective subscription to relevant data channels | P1 |
| RT-06 | Graceful degradation to polling if WebSocket fails | P0 |
| RT-07 | Connection status indicator (live/reconnecting/offline) | P0 |
| RT-08 | Bandwidth optimization (delta updates only) | P1 |

#### 5.2.2 Auto-Refresh (Polling)

| ID | Requirement | Priority |
|----|-------------|----------|
| POLL-01 | Configurable auto-refresh interval (1-15 minutes) | P0 |
| POLL-02 | Manual refresh button with loading indicator | P0 |
| POLL-03 | Pause auto-refresh when tab is inactive | P1 |
| POLL-04 | Resume refresh when tab becomes active | P1 |
| POLL-05 | Smart polling (reduce frequency during low-activity hours) | P2 |

#### 5.2.3 TV/Display Mode

| ID | Requirement | Priority |
|----|-------------|----------|
| TV-01 | Full-screen TV mode optimized for large displays | P1 |
| TV-02 | Auto-cycling through multiple dashboard views | P1 |
| TV-03 | Configurable cycle duration per view (30s - 5min) | P1 |
| TV-04 | Large, readable fonts and high-contrast design | P1 |
| TV-05 | No session timeout in TV mode | P1 |
| TV-06 | Display-specific authentication token | P1 |
| TV-07 | Prominent alert display overlay | P1 |
| TV-08 | Clock and last-updated timestamp display | P2 |
| TV-09 | Network status indicator | P2 |
| TV-10 | Kiosk mode URL with auto-launch support | P2 |

#### 5.2.4 Dashboard Widgets

| ID | Requirement | Priority |
|----|-------------|----------|
| WIDGET-01 | KPI summary cards with trend indicators | P0 |
| WIDGET-02 | Time-series line charts (spend, conversions, etc.) | P0 |
| WIDGET-03 | Bar charts for comparisons (platform, campaign) | P0 |
| WIDGET-04 | Pie/donut charts for distribution breakdowns | P0 |
| WIDGET-05 | Data tables with sorting, filtering, pagination | P0 |
| WIDGET-06 | Sparkline mini-charts for compact trend display | P1 |
| WIDGET-07 | Gauge charts for goal progress | P1 |
| WIDGET-08 | Heatmaps for time-based performance patterns | P2 |
| WIDGET-09 | Geographic maps for regional performance | P2 |
| WIDGET-10 | Benchmark comparison widgets | P1 |
| WIDGET-11 | Anomaly alert widgets | P1 |
| WIDGET-12 | Custom HTML/embed widgets | P3 |

### 5.3 Collaborative Features

#### 5.3.1 Comments & Annotations

| ID | Requirement | Priority |
|----|-------------|----------|
| COLLAB-01 | Add comments to dashboards and reports | P1 |
| COLLAB-02 | Threaded comment replies | P1 |
| COLLAB-03 | @mention users in comments | P1 |
| COLLAB-04 | Comment notifications (in-app + email) | P1 |
| COLLAB-05 | Pin/highlight important comments | P2 |
| COLLAB-06 | Resolve/archive comment threads | P2 |
| COLLAB-07 | Comment history and edit tracking | P2 |
| COLLAB-08 | Annotate specific data points on charts | P2 |
| COLLAB-09 | Attach files to comments | P3 |

#### 5.3.2 Sharing & Collaboration

| ID | Requirement | Priority |
|----|-------------|----------|
| SHARE-01 | Share dashboards via link (view-only) | P1 |
| SHARE-02 | Share with specific users/teams with permissions | P1 |
| SHARE-03 | Public share links with optional password | P2 |
| SHARE-04 | Share link expiration settings | P2 |
| SHARE-05 | Embedded iframe dashboards for external sites | P2 |
| SHARE-06 | Real-time collaborative editing (Google Docs-style) | P3 |

### 5.4 Notification System

#### 5.4.1 In-App Notifications

| ID | Requirement | Priority |
|----|-------------|----------|
| NOTIF-01 | Notification center/bell icon with unread count | P0 |
| NOTIF-02 | Real-time notification delivery via WebSocket | P0 |
| NOTIF-03 | Notification categories (alerts, reports, comments, system) | P1 |
| NOTIF-04 | Mark as read/unread, mark all as read | P0 |
| NOTIF-05 | Notification click navigation to relevant context | P0 |
| NOTIF-06 | Notification history with pagination | P1 |
| NOTIF-07 | Do not disturb mode | P2 |

#### 5.4.2 Email Notifications

| ID | Requirement | Priority |
|----|-------------|----------|
| EMAIL-01 | Alert notifications with context and call-to-action | P0 |
| EMAIL-02 | Scheduled report delivery attachments | P0 |
| EMAIL-03 | Comment/mention notifications | P1 |
| EMAIL-04 | Digest emails (daily/weekly summary) | P2 |
| EMAIL-05 | Unsubscribe link in all emails | P0 |
| EMAIL-06 | Email templates with Red Bananas branding | P0 |
| EMAIL-07 | Email delivery tracking (open, click) | P2 |

#### 5.4.3 Slack Integration

| ID | Requirement | Priority |
|----|-------------|----------|
| SLACK-01 | Slack workspace connection via OAuth | P1 |
| SLACK-02 | Channel selection for notifications | P1 |
| SLACK-03 | Alert notifications to Slack channels | P1 |
| SLACK-04 | Interactive Slack messages (acknowledge, snooze) | P2 |
| SLACK-05 | Slack slash commands for quick queries | P3 |

#### 5.4.4 SMS Notifications

| ID | Requirement | Priority |
|----|-------------|----------|
| SMS-01 | SMS delivery for critical alerts | P2 |
| SMS-02 | Phone number verification | P2 |
| SMS-03 | SMS opt-in/opt-out management | P2 |
| SMS-04 | SMS rate limiting (max per hour/day) | P2 |
| SMS-05 | International SMS support | P2 |

#### 5.4.5 WhatsApp Notifications

| ID | Requirement | Priority |
|----|-------------|----------|
| WA-01 | WhatsApp Business API integration | P2 |
| WA-02 | WhatsApp number verification | P2 |
| WA-03 | Alert notifications via WhatsApp | P2 |
| WA-04 | WhatsApp template messages (approved) | P2 |
| WA-05 | Two-way WhatsApp conversations | P3 |

### 5.5 Reporting & Data Platform

#### 5.5.1 Report Generation

| ID | Requirement | Priority |
|----|-------------|----------|
| RPT-01 | On-demand report generation (PDF, Excel, CSV) | P0 |
| RPT-02 | Scheduled report delivery (daily, weekly, monthly) | P0 |
| RPT-03 | Custom report templates | P1 |
| RPT-04 | Report history and re-download | P1 |
| RPT-05 | Branded reports with client logo | P1 |
| RPT-06 | Executive summary auto-generation | P2 |
| RPT-07 | Multi-client batch reporting | P2 |
| RPT-08 | Report comparison (period over period) | P2 |

#### 5.5.2 API Access

| ID | Requirement | Priority |
|----|-------------|----------|
| API-01 | RESTful API for all data endpoints | P1 |
| API-02 | API key authentication | P1 |
| API-03 | OAuth 2.0 authentication option | P2 |
| API-04 | Rate limiting with clear headers | P1 |
| API-05 | API documentation (OpenAPI/Swagger) | P1 |
| API-06 | Webhook support for event notifications | P2 |
| API-07 | GraphQL API for flexible queries | P3 |
| API-08 | SDK libraries (Python, JavaScript) | P3 |

#### 5.5.3 Data Warehouse & BI Connectors

| ID | Requirement | Priority |
|----|-------------|----------|
| DW-01 | Bulk data export (full historical data) | P1 |
| DW-02 | Scheduled data sync to client data warehouse | P2 |
| DW-03 | BigQuery connector | P2 |
| DW-04 | Snowflake connector | P2 |
| DW-05 | Amazon Redshift connector | P3 |
| DW-06 | Looker Studio (Data Studio) connector | P2 |
| DW-07 | Tableau connector | P2 |
| DW-08 | Power BI connector | P2 |
| DW-09 | Custom JDBC/ODBC connector | P3 |

---

## 6. Non-Functional Requirements

### 6.1 Performance

| ID | Requirement | Target |
|----|-------------|--------|
| PERF-01 | Dashboard initial load time | < 2 seconds (P95) |
| PERF-02 | Subsequent navigation | < 500ms |
| PERF-03 | API response time | < 200ms (P95) |
| PERF-04 | Report generation (PDF) | < 30 seconds |
| PERF-05 | Large data export (100K rows) | < 60 seconds |
| PERF-06 | WebSocket message latency | < 100ms |
| PERF-07 | Search results | < 1 second |
| PERF-08 | Concurrent users supported | 5,000+ |
| PERF-09 | Concurrent WebSocket connections | 10,000+ |
| PERF-10 | Database query time | < 100ms (P95) |

### 6.2 Scalability

| ID | Requirement | Description |
|----|-------------|-------------|
| SCALE-01 | Horizontal scaling | Auto-scale web/API servers based on load |
| SCALE-02 | Database scaling | Read replicas for query distribution |
| SCALE-03 | Caching layer | Redis cluster for session and data caching |
| SCALE-04 | CDN for static assets | Global content delivery |
| SCALE-05 | Queue scaling | Auto-scale job workers based on queue depth |
| SCALE-06 | WebSocket scaling | Redis pub/sub for multi-node WebSocket |
| SCALE-07 | Data partitioning | Time-based partitioning for metrics tables |

### 6.3 Availability & Reliability

| ID | Requirement | Target |
|----|-------------|--------|
| AVAIL-01 | System uptime | 99.9% (43.8 min downtime/month max) |
| AVAIL-02 | Planned maintenance window | < 4 hours/month, off-peak |
| AVAIL-03 | Recovery Time Objective (RTO) | < 1 hour |
| AVAIL-04 | Recovery Point Objective (RPO) | < 15 minutes |
| AVAIL-05 | Automated failover | < 30 seconds |
| AVAIL-06 | Backup frequency | Every 6 hours |
| AVAIL-07 | Backup retention | 90 days |
| AVAIL-08 | Multi-region redundancy | Primary + DR region |

### 6.4 Security

| ID | Requirement | Description |
|----|-------------|-------------|
| SEC-01 | HTTPS everywhere | TLS 1.3 for all connections |
| SEC-02 | Data encryption at rest | AES-256 encryption |
| SEC-03 | Data encryption in transit | TLS 1.3 |
| SEC-04 | Password hashing | bcrypt with cost factor 12 |
| SEC-05 | SQL injection prevention | Parameterized queries, ORM |
| SEC-06 | XSS prevention | Content Security Policy, output encoding |
| SEC-07 | CSRF protection | Token-based protection |
| SEC-08 | Rate limiting | API and login endpoints |
| SEC-09 | Security headers | HSTS, X-Frame-Options, etc. |
| SEC-10 | Vulnerability scanning | Weekly automated scans |
| SEC-11 | Penetration testing | Annual third-party testing |
| SEC-12 | Secret management | Vault or equivalent for secrets |

### 6.5 Compliance

| ID | Requirement | Description |
|----|-------------|-------------|
| COMP-01 | GDPR compliance | Data subject rights, consent, DPA |
| COMP-02 | Data retention policies | Configurable per client |
| COMP-03 | Right to be forgotten | Complete data deletion capability |
| COMP-04 | Data export (portability) | Machine-readable format |
| COMP-05 | Audit logging | All data access logged |
| COMP-06 | SOC 2 Type II | Target compliance (Phase 3) |

### 6.6 Usability

| ID | Requirement | Description |
|----|-------------|-------------|
| UX-01 | Mobile responsiveness | Full functionality on mobile devices |
| UX-02 | Browser support | Chrome, Firefox, Safari, Edge (latest 2 versions) |
| UX-03 | Accessibility | WCAG 2.1 AA compliance |
| UX-04 | Localization | English and Arabic support |
| UX-05 | Onboarding | Interactive tutorials for new users |
| UX-06 | Help documentation | In-app help and knowledge base |
| UX-07 | Loading states | Clear feedback for all async operations |
| UX-08 | Error handling | User-friendly error messages |

---

## 7. Technical Architecture

### 7.1 High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                        CLIENT LAYER                              │
├──────────────┬──────────────┬──────────────┬───────────────────┤
│   Web App    │   TV Mode    │  API Clients │  BI Tools         │
│   (Vue 3)    │   (Kiosk)    │  (REST/GQL)  │  (Connectors)     │
└──────┬───────┴──────┬───────┴──────┬───────┴──────┬────────────┘
       │              │              │              │
       ▼              ▼              ▼              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      EDGE / CDN LAYER                            │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │  CloudFlare / AWS CloudFront                             │    │
│  │  - Static asset caching                                  │    │
│  │  - DDoS protection                                       │    │
│  │  - SSL termination                                       │    │
│  └─────────────────────────────────────────────────────────┘    │
└───────────────────────────┬─────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│                    LOAD BALANCER LAYER                           │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │  AWS ALB / Nginx                                         │    │
│  │  - Health checks                                         │    │
│  │  - SSL termination                                       │    │
│  │  - WebSocket support                                     │    │
│  └─────────────────────────────────────────────────────────┘    │
└───────────────────────────┬─────────────────────────────────────┘
                            │
        ┌───────────────────┼───────────────────┐
        │                   │                   │
        ▼                   ▼                   ▼
┌───────────────┐   ┌───────────────┐   ┌───────────────┐
│   API Server  │   │   API Server  │   │   API Server  │
│   (Laravel)   │   │   (Laravel)   │   │   (Laravel)   │
│   + Octane    │   │   + Octane    │   │   + Octane    │
└───────┬───────┘   └───────┬───────┘   └───────┬───────┘
        │                   │                   │
        └───────────────────┼───────────────────┘
                            │
        ┌───────────────────┼───────────────────┐
        │                   │                   │
        ▼                   ▼                   ▼
┌───────────────┐   ┌───────────────┐   ┌───────────────┐
│  WebSocket    │   │  Job Queue    │   │  Scheduler    │
│  Server       │   │  Workers      │   │  (Cron)       │
│  (Reverb)     │   │  (Horizon)    │   │               │
└───────────────┘   └───────────────┘   └───────────────┘
        │                   │                   │
        └───────────────────┼───────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│                      DATA LAYER                                  │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐          │
│  │   MySQL 8    │  │    Redis     │  │  S3/MinIO    │          │
│  │   Primary    │  │   Cluster    │  │  (Files)     │          │
│  │   + Replicas │  │   (Cache)    │  │              │          │
│  └──────────────┘  └──────────────┘  └──────────────┘          │
└─────────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│                   EXTERNAL SERVICES                              │
│  ┌────────────┐ ┌────────────┐ ┌────────────┐ ┌────────────┐   │
│  │  Facebook  │ │  Google    │ │  TikTok    │ │  LinkedIn  │   │
│  │  Ads API   │ │  Ads API   │ │  Ads API   │ │  Ads API   │   │
│  └────────────┘ └────────────┘ └────────────┘ └────────────┘   │
│  ┌────────────┐ ┌────────────┐ ┌────────────┐ ┌────────────┐   │
│  │  Twilio    │ │  WhatsApp  │ │  Slack     │ │  SendGrid  │   │
│  │  (SMS)     │ │  Business  │ │  API       │ │  (Email)   │   │
│  └────────────┘ └────────────┘ └────────────┘ └────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

### 7.2 Technology Stack

#### Backend

| Component | Technology | Justification |
|-----------|------------|---------------|
| Framework | Laravel 11 | Current stack, mature ecosystem |
| PHP Version | PHP 8.3+ | Performance, type safety |
| App Server | Laravel Octane (Swoole) | High concurrency, keep-alive |
| WebSocket | Laravel Reverb | Native Laravel integration |
| Queue | Laravel Horizon + Redis | Job management, monitoring |
| Cache | Redis Cluster | Performance, pub/sub |
| Database | MySQL 8.0 | Mature, reliable |
| Search | Meilisearch or Typesense | Fast full-text search |
| File Storage | S3-compatible | Scalable object storage |

#### Frontend

| Component | Technology | Justification |
|-----------|------------|---------------|
| Framework | Vue 3 + TypeScript | Current stack, type safety |
| State | Pinia | Official Vue state management |
| Styling | TailwindCSS 3 | Utility-first, consistent |
| Charts | Chart.js 4 / ECharts | Feature-rich visualization |
| Real-time | Laravel Echo + Pusher/Reverb | WebSocket client |
| Build | Vite | Fast development, optimized builds |
| Testing | Vitest + Playwright | Unit and E2E testing |

#### Infrastructure

| Component | Technology | Justification |
|-----------|------------|---------------|
| Cloud | AWS or DigitalOcean | Scalable, reliable |
| Containers | Docker + Kubernetes | Scalability, orchestration |
| CI/CD | GitHub Actions | Integrated with repository |
| Monitoring | Datadog or Grafana | Observability |
| Logging | ELK Stack or CloudWatch | Centralized logging |
| CDN | CloudFlare | Performance, security |

### 7.3 Real-Time Architecture

```
┌──────────────┐      ┌──────────────┐      ┌──────────────┐
│   Browser    │      │   Browser    │      │   Browser    │
│   Client 1   │      │   Client 2   │      │   Client N   │
└──────┬───────┘      └──────┬───────┘      └──────┬───────┘
       │                     │                     │
       │   WebSocket         │   WebSocket         │   WebSocket
       │                     │                     │
       ▼                     ▼                     ▼
┌─────────────────────────────────────────────────────────────┐
│                   Laravel Reverb (WebSocket Server)          │
│  ┌─────────────────────────────────────────────────────┐    │
│  │  Connection Manager                                  │    │
│  │  - Auth validation                                   │    │
│  │  - Channel subscription                              │    │
│  │  - Presence tracking                                 │    │
│  └─────────────────────────────────────────────────────┘    │
└───────────────────────────┬─────────────────────────────────┘
                            │
                            │ Redis Pub/Sub
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                   Redis Cluster                              │
│  ┌─────────────────────────────────────────────────────┐    │
│  │  Channels:                                           │    │
│  │  - private-tenant.{id}                               │    │
│  │  - private-user.{id}                                 │    │
│  │  - private-dashboard.{id}                            │    │
│  │  - presence-dashboard.{id}                           │    │
│  └─────────────────────────────────────────────────────┘    │
└───────────────────────────┬─────────────────────────────────┘
                            │
                            │ Events
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                   Event Sources                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │  Metric Sync │  │  Alert       │  │  Comment     │      │
│  │  Jobs        │  │  Engine      │  │  System      │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
```

### 7.4 Data Flow for Live Updates

```
1. Data Source Updates
   Facebook Ads API → Sync Job → Database Update

2. Event Dispatch
   Database Update → Observer → MetricsUpdated Event

3. Broadcasting
   Event → Laravel Reverb → Redis Pub/Sub

4. Client Notification
   Redis → WebSocket Server → Connected Clients

5. UI Update
   Client receives event → Vuex mutation → Component re-render
```

---

## 8. Data Architecture

### 8.1 Database Schema Extensions

#### New Tables Required

```sql
-- Real-time presence tracking
CREATE TABLE dashboard_presence (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    dashboard_id BIGINT NOT NULL,
    connected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_seen_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    socket_id VARCHAR(255),
    INDEX idx_dashboard_presence (dashboard_id, last_seen_at)
);

-- Notifications table
CREATE TABLE notifications (
    id CHAR(36) PRIMARY KEY,
    user_id BIGINT NOT NULL,
    type VARCHAR(255) NOT NULL,
    notifiable_type VARCHAR(255),
    notifiable_id BIGINT,
    data JSON NOT NULL,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notifications_user (user_id, read_at, created_at)
);

-- Comments/Annotations
CREATE TABLE comments (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    commentable_type VARCHAR(255) NOT NULL,
    commentable_id BIGINT NOT NULL,
    parent_id BIGINT NULL,
    content TEXT NOT NULL,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_comments_target (commentable_type, commentable_id),
    INDEX idx_comments_parent (parent_id)
);

-- API Keys
CREATE TABLE api_keys (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    key_hash VARCHAR(255) NOT NULL,
    last_used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    revoked_at TIMESTAMP NULL,
    INDEX idx_api_keys_hash (key_hash)
);

-- Notification Preferences
CREATE TABLE notification_preferences (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    channel VARCHAR(50) NOT NULL, -- email, slack, sms, whatsapp, in_app
    notification_type VARCHAR(100) NOT NULL,
    enabled BOOLEAN DEFAULT TRUE,
    settings JSON,
    UNIQUE KEY uk_user_channel_type (user_id, channel, notification_type)
);

-- Shared Links
CREATE TABLE shared_links (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    shareable_type VARCHAR(255) NOT NULL,
    shareable_id BIGINT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NULL,
    permissions JSON, -- { view: true, export: false }
    expires_at TIMESTAMP NULL,
    view_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_shared_links_token (token)
);

-- TV Display Tokens
CREATE TABLE display_tokens (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    dashboard_ids JSON NOT NULL,
    cycle_duration INT DEFAULT 60, -- seconds
    last_used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    INDEX idx_display_tokens (token)
);
```

### 8.2 Caching Strategy

| Data Type | Cache TTL | Cache Key Pattern | Invalidation |
|-----------|-----------|-------------------|--------------|
| Dashboard metrics | 1-5 min | `metrics:{tenant}:{dashboard}:{date}` | On metric sync |
| User permissions | 15 min | `perms:{user}:{tenant}` | On role change |
| Industry benchmarks | 1 hour | `bench:{industry}:{metric}` | On recalculation |
| Report data | 5 min | `report:{tenant}:{type}:{hash}` | On export |
| API responses | Varies | `api:{endpoint}:{params_hash}` | On data change |

### 8.3 Data Retention

| Data Type | Retention Period | Archival |
|-----------|------------------|----------|
| Metrics (detailed) | 24 months | Archive to cold storage |
| Metrics (aggregated) | Indefinite | No archival |
| Audit logs | 36 months | Archive after 12 months |
| Notifications | 6 months | Delete after expiry |
| Session data | 30 days | Auto-expire |
| API logs | 90 days | Archive after 30 days |

---

## 9. Security & Compliance

### 9.1 Authentication Security

#### Password Requirements
- Minimum 12 characters
- At least one uppercase, lowercase, number, special character
- No common passwords (dictionary check)
- Password history (prevent reuse of last 5)
- Password expiration: Optional (configurable per tenant)

#### Session Security
- Secure, HTTP-only cookies
- Session timeout: 24 hours active, 30 days remembered
- Concurrent session limit: 5 per user
- Session invalidation on password change

#### API Security
- API keys: SHA-256 hashed storage
- Rate limiting: 1000 requests/minute per key
- IP allowlisting: Optional per API key
- Request signing: Optional HMAC

### 9.2 Data Security

#### Encryption
- At rest: AES-256 (database, file storage)
- In transit: TLS 1.3 (all connections)
- Application-level: Encrypt sensitive fields (API tokens)

#### Access Control
- Multi-tenant isolation via global scopes
- Row-level security for all queries
- API endpoint authorization
- Feature flags per tenant

### 9.3 Audit & Compliance

#### Audit Logging
```json
{
    "timestamp": "2026-02-03T10:30:00Z",
    "user_id": 123,
    "tenant_id": 456,
    "action": "dashboard.view",
    "resource_type": "Dashboard",
    "resource_id": 789,
    "ip_address": "1.2.3.4",
    "user_agent": "...",
    "metadata": { ... }
}
```

#### Logged Events
- Authentication (login, logout, failed attempts)
- Data access (view, export, download)
- Data modification (create, update, delete)
- Permission changes
- API access
- System configuration changes

---

## 10. Integration Requirements

### 10.1 Advertising Platforms (Existing)

| Platform | Status | Enhancement |
|----------|--------|-------------|
| Facebook Ads | Existing | Add real-time webhooks |
| Google Ads | Existing | Add change history API |
| TikTok Ads | Existing | Improve sync frequency |
| LinkedIn Ads | Existing | Add conversion tracking |
| Snapchat Ads | Existing | No changes |
| Twitter/X Ads | Existing | No changes |

### 10.2 Notification Services (New)

| Service | Purpose | Priority |
|---------|---------|----------|
| SendGrid | Email delivery | P0 (existing) |
| Twilio | SMS notifications | P2 |
| WhatsApp Business | WhatsApp messages | P2 |
| Slack API | Workspace integration | P1 |
| Microsoft Teams | Teams integration | P3 |

### 10.3 Data Connectors (New)

| Connector | Purpose | Priority |
|-----------|---------|----------|
| BigQuery | Data warehouse export | P2 |
| Snowflake | Data warehouse export | P2 |
| Looker Studio | Direct connector | P2 |
| Tableau | Data connector | P2 |
| Power BI | Data connector | P2 |
| Zapier | Workflow automation | P3 |

### 10.4 SSO Providers (New)

| Provider | Protocol | Priority |
|----------|----------|----------|
| Google | OAuth 2.0 | P1 |
| Microsoft | OAuth 2.0 / SAML | P1 |
| Okta | SAML 2.0 | P2 |
| OneLogin | SAML 2.0 | P3 |

---

## 11. UI/UX Requirements

### 11.1 Design Principles

1. **Data-First** - Prioritize data visibility and clarity
2. **Responsive** - Full functionality across all screen sizes
3. **Accessible** - WCAG 2.1 AA compliance
4. **Consistent** - Unified design language
5. **Performant** - Instant feedback, optimized loading

### 11.2 Key Screens

#### Customer Portal Home
- Personalized dashboard tiles
- Recent activity feed
- Quick actions (export, view report)
- Notification summary
- Search bar

#### Live Dashboard
- Full-width KPI cards with sparklines
- Interactive charts with drill-down
- Real-time update indicators
- Filter bar (date, platform, campaign)
- Alert banner for active issues

#### TV Display Mode
- Full-screen, distraction-free
- Large typography (readable at distance)
- High contrast (works in bright offices)
- Auto-rotating views
- Persistent clock and last-update time

#### Report Builder
- Drag-and-drop widget placement
- Live preview
- Template selection
- Schedule configuration
- Recipient management

#### Notification Center
- Categorized notification list
- Quick actions (mark read, delete)
- Notification settings shortcut
- Filter by type/date

### 11.3 Mobile Considerations

- Touch-optimized interactions
- Swipe gestures for navigation
- Bottom navigation bar
- Pull-to-refresh
- Optimized data loading

---

## 12. Implementation Phases

### Phase 1: Foundation (Months 1-6)

**Focus:** Core infrastructure and real-time capabilities

#### Deliverables
- [ ] WebSocket infrastructure (Laravel Reverb)
- [ ] Real-time dashboard updates
- [ ] Auto-refresh polling system
- [ ] Enhanced notification center (in-app)
- [ ] Email notification improvements
- [ ] Basic TV display mode
- [ ] Performance optimizations for scale
- [ ] Redis caching layer
- [ ] Database optimizations

#### Success Criteria
- Dashboard load time < 2 seconds
- Real-time updates within 5 minutes
- Support for 1,000 concurrent users
- 99.5% uptime

#### Team Requirements
- 2 Backend engineers
- 2 Frontend engineers
- 1 DevOps engineer
- 1 QA engineer

---

### Phase 2: Collaboration & Self-Service (Months 7-12)

**Focus:** Client empowerment and collaboration features

#### Deliverables
- [ ] Custom dashboard builder
- [ ] Comments and annotations system
- [ ] Dashboard sharing (internal)
- [ ] Slack integration
- [ ] SSO integration (Google, Microsoft)
- [ ] Magic link authentication
- [ ] Advanced TV mode (multi-dashboard cycling)
- [ ] API v1 with documentation
- [ ] Enhanced export capabilities

#### Success Criteria
- 80% client self-service rate
- Comments feature adoption > 50%
- Slack integration in 30% of tenants
- API documentation complete

#### Team Requirements
- 2 Backend engineers
- 2 Frontend engineers
- 1 DevOps engineer
- 1 QA engineer
- 1 Technical writer

---

### Phase 3: Enterprise & Data Platform (Months 13-18)

**Focus:** Enterprise scale and data platform capabilities

#### Deliverables
- [ ] SMS notifications (Twilio)
- [ ] WhatsApp notifications
- [ ] Data warehouse connectors (BigQuery, Snowflake)
- [ ] BI tool connectors (Looker, Tableau, Power BI)
- [ ] Public share links
- [ ] SAML SSO
- [ ] Advanced API features (webhooks, GraphQL)
- [ ] Multi-factor authentication
- [ ] Horizontal scaling infrastructure
- [ ] SOC 2 preparation

#### Success Criteria
- Support for 5,000+ concurrent users
- Data warehouse adoption in 20% of enterprise clients
- 99.9% uptime
- All notification channels operational

#### Team Requirements
- 3 Backend engineers
- 2 Frontend engineers
- 2 DevOps engineers
- 1 QA engineer
- 1 Security engineer

---

### Phase 4: Optimization & Advanced Features (Months 18+)

**Focus:** Continuous improvement and advanced capabilities

#### Deliverables
- [ ] AI-powered insights and recommendations
- [ ] Predictive anomaly detection
- [ ] Natural language queries
- [ ] Advanced collaboration (real-time co-editing)
- [ ] Custom branding options
- [ ] White-label preparation
- [ ] Mobile PWA enhancements
- [ ] Performance monitoring dashboard
- [ ] Customer success analytics

---

## 13. Risk Assessment

### 13.1 Technical Risks

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| WebSocket scalability issues | High | Medium | Load testing, Redis clustering |
| Database performance under load | High | Medium | Query optimization, read replicas |
| Third-party API changes | Medium | Medium | Abstraction layers, monitoring |
| Data migration complexity | High | Low | Incremental migration, rollback plan |
| Security vulnerabilities | Critical | Low | Security audits, penetration testing |

### 13.2 Business Risks

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| User adoption resistance | High | Medium | Training, gradual rollout |
| Feature scope creep | Medium | High | Strict prioritization, MVP focus |
| Resource constraints | High | Medium | Phased approach, clear priorities |
| Competitor features | Medium | Medium | Market monitoring, rapid iteration |

### 13.3 Operational Risks

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| Team knowledge gaps | Medium | Medium | Training, documentation |
| Deployment failures | High | Low | CI/CD, staging environment |
| Monitoring blind spots | Medium | Medium | Comprehensive observability |

---

## 14. Appendices

### Appendix A: Glossary

| Term | Definition |
|------|------------|
| **Tenant** | A client organization with isolated data |
| **Dashboard** | A configurable view of metrics and visualizations |
| **KPI** | Key Performance Indicator (metric) |
| **WebSocket** | Full-duplex communication protocol for real-time updates |
| **SSO** | Single Sign-On authentication |
| **ROAS** | Return on Ad Spend |
| **CPM** | Cost Per Mille (thousand impressions) |
| **CPC** | Cost Per Click |

### Appendix B: API Endpoints (Planned)

```
# Authentication
POST   /api/v2/auth/login
POST   /api/v2/auth/logout
POST   /api/v2/auth/magic-link
POST   /api/v2/auth/sso/{provider}
POST   /api/v2/auth/mfa/verify

# Dashboards
GET    /api/v2/dashboards
POST   /api/v2/dashboards
GET    /api/v2/dashboards/{id}
PUT    /api/v2/dashboards/{id}
DELETE /api/v2/dashboards/{id}
POST   /api/v2/dashboards/{id}/share
GET    /api/v2/dashboards/{id}/presence

# Metrics
GET    /api/v2/metrics/summary
GET    /api/v2/metrics/timeseries
GET    /api/v2/metrics/breakdown
GET    /api/v2/metrics/export

# Real-time
WS     /api/v2/ws/connect
POST   /api/v2/ws/subscribe
POST   /api/v2/ws/unsubscribe

# Comments
GET    /api/v2/comments
POST   /api/v2/comments
PUT    /api/v2/comments/{id}
DELETE /api/v2/comments/{id}
POST   /api/v2/comments/{id}/resolve

# Notifications
GET    /api/v2/notifications
PUT    /api/v2/notifications/{id}/read
PUT    /api/v2/notifications/read-all
GET    /api/v2/notifications/preferences
PUT    /api/v2/notifications/preferences

# Sharing
POST   /api/v2/shares
GET    /api/v2/shares/{token}
DELETE /api/v2/shares/{id}

# TV Mode
GET    /api/v2/display/{token}
POST   /api/v2/display/tokens

# Data Export
POST   /api/v2/exports/warehouse
GET    /api/v2/exports/{id}/status
GET    /api/v2/exports/{id}/download
```

### Appendix C: Event Types (WebSocket)

```javascript
// Metrics Events
'metrics.updated'        // New metrics available
'metrics.alert'          // Alert threshold crossed

// Dashboard Events
'dashboard.updated'      // Dashboard configuration changed
'dashboard.presence'     // User joined/left dashboard

// Notification Events
'notification.new'       // New notification received
'notification.read'      // Notification marked as read

// Comment Events
'comment.created'        // New comment added
'comment.updated'        // Comment edited
'comment.resolved'       // Comment thread resolved

// System Events
'system.maintenance'     // Upcoming maintenance notice
'system.alert'           // System-wide alert
```

### Appendix D: Notification Templates

#### Email: Alert Notification
```
Subject: [Alert] {alert_name} - {client_name}

Hi {user_name},

An alert has been triggered for {client_name}:

Alert: {alert_name}
Condition: {condition}
Current Value: {current_value}
Threshold: {threshold}

View details: {dashboard_link}

---
Red Bananas Benchmarks
```

#### Slack: Alert Notification
```
:warning: *Alert Triggered*

*{alert_name}*
Client: {client_name}
Condition: {condition}
Value: {current_value} (threshold: {threshold})

<{dashboard_link}|View Dashboard>
```

### Appendix E: Success Metrics Dashboard

| Metric | Phase 1 Target | Phase 2 Target | Phase 3 Target |
|--------|----------------|----------------|----------------|
| Concurrent Users | 1,000 | 2,500 | 5,000+ |
| Dashboard Load Time | < 3s | < 2s | < 2s |
| Data Freshness | 10 min | 5 min | 1-5 min |
| Uptime | 99.5% | 99.9% | 99.9% |
| Client Self-Service | 50% | 70% | 80%+ |
| API Adoption | N/A | 20% | 40% |
| NPS Score | 30 | 40 | 50+ |

---

**Document Approval**

| Role | Name | Date | Signature |
|------|------|------|-----------|
| Product Owner | | | |
| Engineering Lead | | | |
| Design Lead | | | |
| Security Lead | | | |

---

*This PRD is a living document and will be updated as requirements evolve.*
