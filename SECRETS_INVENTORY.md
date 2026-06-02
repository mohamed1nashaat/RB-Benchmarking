# Secrets Inventory — RB Benchmarks

**Classification**: CONFIDENTIAL — Internal Security Document
**Date**: 2026-06-02

---

## Summary

| Category | Count | Immediate Rotation Required |
|----------|-------|----------------------------|
| Database Credentials | 1 | ✅ YES |
| OAuth Client Secrets | 5 | ✅ YES |
| OAuth Access Tokens | 1 | ✅ YES |
| API Keys | 2 | ✅ YES |
| Service Account Keys | 2 | ✅ YES |
| Application Encryption Keys | 1 | ✅ YES |
| **Total** | **12** | **12** |

> **⚠️ Git History Status**: Verified clean. `.env` and `storage/app/google/*.json` have **never** been committed to this repository. They are properly `.gitignore`d. However, these files exist on disk with live secrets and were previously world-readable — rotation is still critical. See [SECRET_ROTATION_GUIDE.md](SECRET_ROTATION_GUIDE.md) for step-by-step instructions.

---

## Detailed Inventory

### 1. Laravel Application Key
| Attribute | Value |
|-----------|-------|
| **Name** | `APP_KEY` |
| **Location** | `.env` line 3 |
| **Value** | `REDACTED` |
| **Type** | AES-256-GCM encryption key |
| **Impact** | Decrypts all Laravel-encrypted data including session cookies, signed URLs, and database-encrypted `Integration.app_config` |
| **Rotation Complexity** | HIGH — requires re-encrypting all database fields that use Laravel encryption |
| **Action** | 1. Generate new key: `php artisan key:generate`<br>2. Re-encrypt all `Integration` records<br>3. Force all users to re-login |

---

### 2. MySQL Database Password
| Attribute | Value |
|-----------|-------|
| **Name** | `DB_PASSWORD` |
| **Location** | `.env` line 18 |
| **Value** | `REDACTED` |
| **Host** | `localhost` (but production URL implies remote access possible) |
| **Impact** | Full database read/write access |
| **Rotation Complexity** | LOW |
| **Action** | 1. Change MySQL user password<br>2. Update `.env`<br>3. Restart application |

---

### 3. Facebook App Secret
| Attribute | Value |
|-----------|-------|
| **Name** | `FB_APP_SECRET` |
| **Location** | `.env` line 31 |
| **Value** | `REDACTED` |
| **Impact** | Can validate signed requests, perform app-level API calls |
| **Rotation Complexity** | LOW |
| **Action** | 1. Rotate in Facebook Developer Console<br>2. Update `.env` |

---

### 4. Facebook Default Access Token
| Attribute | Value |
|-----------|-------|
| **Name** | `FB_DEFAULT_ACCESS_TOKEN` |
| **Location** | `.env` line 32 |
| **Type** | Long-lived User Access Token |
| **Scopes** | `ads_read, ads_management, business_management` |
| **Impact** | FULL ACCESS to all Facebook ad accounts connected to this user/app |
| **Rotation Complexity** | LOW |
| **Action** | 1. Revoke token in Facebook Business Settings<br>2. Generate new token<br>3. Move to encrypted DB storage (already supported by `Integration` model) |

---

### 5. Snapchat Client Secret
| Attribute | Value |
|-----------|-------|
| **Name** | `SNAPCHAT_CLIENT_SECRET` |
| **Location** | `.env` line 37 |
| **Value** | `REDACTED` |
| **Impact** | OAuth client authentication |
| **Rotation Complexity** | LOW |
| **Action** | Rotate in Snapchat Developer Portal |

---

### 6. TikTok Client Key & Secret
| Attribute | Value |
|-----------|-------|
| **Name** | `TIKTOK_CLIENT_KEY` / `TIKTOK_CLIENT_SECRET` |
| **Location** | `.env` lines 38–39 |
| **Values** | `7585013420697600017` / `REDACTED` |
| **Impact** | OAuth client authentication |
| **Rotation Complexity** | LOW |
| **Action** | Rotate in TikTok for Business Developer Portal |

---

### 7. Google Ads Client Secret
| Attribute | Value |
|-----------|-------|
| **Name** | `GOOGLE_ADS_CLIENT_SECRET` |
| **Location** | `.env` line 42 |
| **Value** | `REDACTED` |
| **Impact** | OAuth client authentication for Google Ads |
| **Rotation Complexity** | LOW |
| **Action** | Rotate in Google Cloud Console |

---

### 8. Google Ads Developer Token
| Attribute | Value |
|-----------|-------|
| **Name** | `GOOGLE_ADS_DEVELOPER_TOKEN` |
| **Location** | `.env` line 46 |
| **Value** | `REDACTED` |
| **Impact** | Required for all Google Ads API calls; tied to developer account |
| **Rotation Complexity** | MEDIUM — requires Google approval for new token |
| **Action** | Request new developer token from Google Ads API Center |

---

### 9. Google Cloud API Key
| Attribute | Value |
|-----------|-------|
| **Name** | `GOOGLE_ADS_API_KEY` / `PAGESPEED_API_KEY` |
| **Location** | `.env` lines 49, 52 |
| **Value** | `REDACTED` |
| **Impact** | Google Cloud API access (PageSpeed, potentially others) |
| **Rotation Complexity** | LOW |
| **Action** | Rotate in Google Cloud Console → APIs & Services → Credentials |

---

### 10. LinkedIn Client Secret
| Attribute | Value |
|-----------|-------|
| **Name** | `LINKEDIN_CLIENT_SECRET` |
| **Location** | `.env` line 50 |
| **Value** | `REDACTED` |
| **Impact** | OAuth client authentication |
| **Rotation Complexity** | LOW |
| **Action** | Rotate in LinkedIn Developer Portal |

---

### 11. Google Service Account — rb-benchmarks-92561a9f40e5.json
| Attribute | Value |
|-----------|-------|
| **Name** | Service Account Key |
| **Location** | `storage/app/google/rb-benchmarks-92561a9f40e5.json` |
| **Client Email** | `rb-benchmarks@rb-benchmarks.iam.gserviceaccount.com` |
| **Client ID** | `113662941395509454771` |
| **Key ID** | `a704ef7707f765f0cd2cfcad01bdc3e858a84fb6` |
| **Type** | RSA private key (full PEM block) |
| **Impact** | **FULL ACCESS** to Google Cloud project resources including Sheets API |
| **Rotation Complexity** | MEDIUM |
| **Action** | 1. Delete key in Google Cloud IAM → Service Accounts<br>2. Create new key<br>3. Store outside repository (env var or secrets manager) |

---

### 12. Google Service Account — service-account.json
| Attribute | Value |
|-----------|-------|
| **Name** | Service Account Key (OAuth2 Client) |
| **Location** | `storage/app/google/service-account.json` |
| **Client ID** | `692140413544-0djlbfknfrs7uooh30j7e7n7rb300sbf.apps.googleusercontent.com` |
| **Impact** | OAuth2 client credentials for Google APIs |
| **Rotation Complexity** | LOW |
| **Action** | Rotate in Google Cloud Console → APIs & Services → Credentials |

---

## Rotation Checklist

- [ ] MySQL `DB_PASSWORD`
- [ ] Laravel `APP_KEY` (with database re-encryption)
- [ ] Facebook `FB_APP_SECRET`
- [ ] Facebook `FB_DEFAULT_ACCESS_TOKEN`
- [ ] Snapchat `SNAPCHAT_CLIENT_SECRET`
- [ ] TikTok `TIKTOK_CLIENT_SECRET`
- [ ] Google Ads `GOOGLE_ADS_CLIENT_SECRET`
- [ ] Google Ads `GOOGLE_ADS_DEVELOPER_TOKEN`
- [ ] Google Cloud `GOOGLE_ADS_API_KEY` / `PAGESPEED_API_KEY`
- [ ] LinkedIn `LINKEDIN_CLIENT_SECRET`
- [ ] Google Service Account `rb-benchmarks-92561a9f40e5.json`
- [ ] Google OAuth2 Client `service-account.json`
- [x] Verify `.gitignore` blocks future commits (already configured)
- [x] Audit GitHub/GitLab for exposed secrets in PR history (verified clean — 0 commits)
- [ ] Enable secret scanning on repository
- [ ] Move secrets out of `.env` into encrypted DB fields or secrets manager
- [ ] Restrict `.env` and JSON key file permissions to `600` (done)
