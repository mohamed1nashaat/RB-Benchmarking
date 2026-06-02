# Secret Rotation Guide — RB Benchmarks

**Classification**: CONFIDENTIAL — Internal Security Document  
**Date**: 2026-06-02  
**Scope**: Rotate all 12 credentials listed in [SECRETS_INVENTORY.md](SECRETS_INVENTORY.md)

---

## Pre-Rotation Checklist

- [ ] Notify team: rotation will force all users to re-authenticate OAuth connections
- [ ] Schedule maintenance window (30–60 minutes)
- [ ] Back up database: `mysqldump -u root -p rb_benchmarks > /tmp/rb_benchmarks_$(date +%Y%m%d_%H%M%S).sql`
- [ ] Ensure `RotateAppKey` artisan command is available: `php artisan list | grep key:rotate`
- [ ] Open all platform developer consoles in browser tabs

---

## Phase 1 — Immediate Revocation (Do First)

### 1.1 Revoke Facebook Default Access Token
**Risk**: FULL ACCESS to all Facebook ad accounts connected to this app/user.

1. Go to [Facebook Business Settings → System Users → Tokens](https://business.facebook.com/settings/system-users)
2. Or [Facebook Settings → Apps & Websites](https://www.facebook.com/settings?tab=applications)
3. Find "RB Benchmarks" (or your app name) → Remove / Revoke Access
4. Alternatively, use Graph API:
   ```bash
   curl -X DELETE "https://graph.facebook.com/v22.0/me/permissions?access_token=OLD_TOKEN"
   ```

**After revocation**: Users must re-connect their Facebook ad accounts in the RB Benchmarks UI.

---

### 1.2 Delete Google Service Account Keys
**Risk**: FULL ACCESS to Google Cloud project resources (Sheets API, etc.).

1. Go to [Google Cloud Console → IAM & Admin → Service Accounts](https://console.cloud.google.com/iam-admin/serviceaccounts)
2. Select `rb-benchmarks@rb-benchmarks.iam.gserviceaccount.com`
3. Go to **Keys** tab
4. Delete key ID `a704ef7707f765f0cd2cfcad01bdc3e858a84fb6`
5. Click **Add Key → Create new key → JSON**
6. Download the new key file
7. **Do not commit it**. Place it at:
   ```bash
   # On server
   mv ~/Downloads/new-key.json /var/www/rb-benchmarks/storage/app/google/rb-benchmarks-92561a9f40e5.json
   chmod 600 /var/www/rb-benchmarks/storage/app/google/rb-benchmarks-92561a9f40e5.json
   chown www-data:www-data /var/www/rb-benchmarks/storage/app/google/rb-benchmarks-92561a9f40e5.json
   ```

---

### 1.3 Change MySQL Database Password
**Risk**: Full database read/write access.

```bash
# 1. Generate a strong password
NEW_DB_PASS=$(openssl rand -base64 32 | tr -dc 'a-zA-Z0-9' | head -c 32)
echo "New DB password: $NEW_DB_PASS"

# 2. Change in MySQL
mysql -u root -p -e "ALTER USER 'rb_benchmarks'@'localhost' IDENTIFIED BY '$NEW_DB_PASS'; FLUSH PRIVILEGES;"

# 3. Update .env
sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$NEW_DB_PASS/" /var/www/rb-benchmarks/.env

# 4. Restart queue workers / php-fpm to pick up new env
sudo systemctl restart php8.2-fpm
sudo systemctl restart supervisor  # if using supervisor for queues
```

---

## Phase 2 — OAuth Client Secret Rotation

### 2.1 Facebook App Secret
1. [Facebook Developers → Your App → Settings → Basic](https://developers.facebook.com/apps/)
2. Click **Show** next to App Secret → **Reset**
3. Copy new secret
4. Update `.env`:
   ```bash
   sed -i 's/FACEBOOK_APP_SECRET=.*/FACEBOOK_APP_SECRET=NEW_SECRET/' .env
   ```

### 2.2 Snapchat Client Secret
1. [Snapchat Developer Portal → Your App → Credentials](https://developers.snapchat.com/)
2. Regenerate Client Secret
3. Update `.env`:
   ```bash
   sed -i 's/SNAPCHAT_CLIENT_SECRET=.*/SNAPCHAT_CLIENT_SECRET=NEW_SECRET/' .env
   ```

### 2.3 TikTok Client Key & Secret
1. [TikTok for Business → Developer → Apps](https://ads.tiktok.com/marketing_api/homepage)
2. Select your app → **Reset Secret**
3. Update `.env`:
   ```bash
   sed -i 's/TIKTOK_APP_ID=.*/TIKTOK_APP_ID=NEW_KEY/' .env
   sed -i 's/TIKTOK_APP_SECRET=.*/TIKTOK_APP_SECRET=NEW_SECRET/' .env
   ```

### 2.4 Google Ads Client Secret
1. [Google Cloud Console → APIs & Services → Credentials](https://console.cloud.google.com/apis/credentials)
2. Find your OAuth 2.0 Client ID → **Edit** → **Reset Secret**
3. Update `.env`:
   ```bash
   sed -i 's/GOOGLE_ADS_CLIENT_SECRET=.*/GOOGLE_ADS_CLIENT_SECRET=NEW_SECRET/' .env
   ```

### 2.5 LinkedIn Client Secret
1. [LinkedIn Developer Portal → Your App → Auth](https://www.linkedin.com/developers/apps)
2. **Regenerate Client Secret**
3. Update `.env`:
   ```bash
   sed -i 's/LINKEDIN_CLIENT_SECRET=.*/LINKEDIN_CLIENT_SECRET=NEW_SECRET/' .env
   ```

---

## Phase 3 — API Keys & Tokens

### 3.1 Google Ads Developer Token
**Complexity**: MEDIUM — requires Google approval.

1. [Google Ads API Center](https://ads.google.com/aw/apicenter)
2. Request a new developer token (may take 1–5 business days for approval)
3. Once approved, update `.env`:
   ```bash
   sed -i 's/GOOGLE_ADS_DEVELOPER_TOKEN=.*/GOOGLE_ADS_DEVELOPER_TOKEN=NEW_TOKEN/' .env
   ```

### 3.2 Google Cloud / PageSpeed API Key
1. [Google Cloud Console → APIs & Services → Credentials](https://console.cloud.google.com/apis/credentials)
2. Find the API key → **Delete**
3. Click **Create Credentials → API Key**
4. Restrict the key to:
   - PageSpeed Insights API
   - Your server IP(s)
5. Update `.env`:
   ```bash
   sed -i 's/PAGESPEED_API_KEY=.*/PAGESPEED_API_KEY=NEW_KEY/' .env
   sed -i 's/GOOGLE_ADS_API_KEY=.*/GOOGLE_ADS_API_KEY=NEW_KEY/' .env
   ```

### 3.3 Google OAuth2 Client (`service-account.json`)
1. [Google Cloud Console → APIs & Services → Credentials](https://console.cloud.google.com/apis/credentials)
2. Find OAuth 2.0 client ID `692140413544-0djlbfknfrs7uooh30j7e7n7rb300sbf.apps.googleusercontent.com`
3. **Delete** the old client ID
4. Create new **OAuth 2.0 Client ID → Web application**
5. Download `client_secret.json` and rename to `service-account.json`
6. Place it securely:
   ```bash
   mv ~/Downloads/client_secret.json /var/www/rb-benchmarks/storage/app/google/service-account.json
   chmod 600 /var/www/rb-benchmarks/storage/app/google/service-account.json
   ```

---

## Phase 4 — Application Encryption Key (APP_KEY)

**⚠️ WARNING**: This will invalidate all existing sessions, signed URLs, and encrypted database data. All users will be forced to log in again.

### 4.1 Before Rotation
```bash
# Ensure all queue workers are stopped
php artisan queue:flush

# Back up the current APP_KEY
OLD_KEY=$(grep '^APP_KEY=' .env | cut -d= -f2)
echo "Old key: $OLD_KEY" > /root/.rb_benchmarks_old_key
chmod 600 /root/.rb_benchmarks_old_key
```

### 4.2 Rotate Key
```bash
# Use the custom RotateAppKey command
php artisan key:rotate --old-key="$OLD_KEY"

# If command fails or isn't available, manual approach:
# 1. Generate new key
php artisan key:generate

# 2. Re-encrypt Integration records
php artisan tinker --execute='
$oldKey = env("APP_KEY");
$integrations = App\Models\Integration::all();
foreach ($integrations as $i) {
    try {
        $decrypted = decrypt($i->getAttributes()["app_config"] ?? $i->app_config);
        $i->app_config = $decrypted;
        $i->save();
    } catch (\Exception $e) {
        echo "Failed on ID {$i->id}: " . $e->getMessage() . "\n";
    }
}
'
```

### 4.3 After Rotation
```bash
# Clear all sessions
php artisan session:gc

# Clear cache
php artisan cache:clear
php artisan config:clear

# Restart services
sudo systemctl restart php8.2-fpm
sudo systemctl restart supervisor
```

---

## Phase 5 — Post-Rotation Verification

### 5.1 Verify `.env` Contains New Values
```bash
# Confirm no old secrets remain
grep -E 'REDACTED|REDACTED|REDACTED|REDACTED|REDACTED|REDACTED|REDACTED|REDACTED' .env && echo "OLD SECRETS STILL PRESENT!" || echo "Old secrets cleared ✓"
```

### 5.2 Verify File Permissions
```bash
ls -la .env storage/app/google/*.json
# Expected: -rw------- (600) for all three files
```

### 5.3 Verify Application Still Works
```bash
php artisan config:cache
php artisan migrate:status
php artisan tinker --execute='echo "DB OK: " . DB::connection()->getPdo()->getAttribute(PDO::ATTR_CONNECTION_STATUS) . "\n";'
```

### 5.4 Test OAuth Re-Connection
1. Log in to RB Benchmarks
2. Go to **Settings → Integrations**
3. Re-connect each platform (Facebook, Google Ads, LinkedIn, TikTok, Snapchat)
4. Verify data sync works for at least one platform

---

## Phase 6 — Long-Term Hardening

### Move Secrets Out of `.env`
Laravel's `.env` is convenient but dangerous if the server is compromised. Consider:

1. **Encrypted database storage** (already supported):
   - Move OAuth tokens from `.env` into `Integration.app_config` (encrypted array cast)
   - Store API keys in a new `settings` table with encrypted values

2. **Docker Secrets / Kubernetes Secrets**:
   ```yaml
   # docker-compose.yml
   secrets:
     db_password:
       file: ./secrets/db_password.txt
   ```

3. **AWS Secrets Manager / GCP Secret Manager / HashiCorp Vault**:
   - Use a Laravel package like `aws/aws-sdk-php` or `spatie/laravel-google-cloud-secret-manager`

### Enable Secret Scanning
- **GitHub**: Settings → Security → Secret scanning → Enable
- **GitLab**: Settings → Security & Compliance → Secret Detection

### Rotate Schedule
| Secret Type | Rotation Frequency |
|-------------|-------------------|
| Database password | Every 90 days |
| OAuth client secrets | Every 180 days |
| API keys | Every 180 days |
| Service account keys | Every 90 days |
| `APP_KEY` | Every 365 days or on personnel change |
| Access tokens | On user offboarding or suspected breach |

---

## Emergency Contacts

| Platform | Support URL |
|----------|-------------|
| Facebook | https://developers.facebook.com/support |
| Google Ads | https://support.google.com/google-ads/gethelp |
| Google Cloud | https://cloud.google.com/support |
| LinkedIn | https://linkedin.com/developers/support |
| TikTok | https://ads.tiktok.com/marketing_api/homepage?tab=support |
| Snapchat | https://developers.snapchat.com/support |

---

## Document History

| Date | Action |
|------|--------|
| 2026-06-02 | Created guide after security audit |
| 2026-06-02 | Verified git history is clean; secrets only exposed on disk |
| 2026-06-02 | Fixed `.env` and JSON key file permissions to 600 |
