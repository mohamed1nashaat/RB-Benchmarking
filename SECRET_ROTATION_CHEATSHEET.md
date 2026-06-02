# Secret Rotation Cheat Sheet — RB Benchmarks

**Run this in order. Estimated time: 45–60 minutes.**

---

## 1. BACKUP

```bash
cd /var/www/rb-benchmarks
mysqldump -u root -p rb_benchmarks > /tmp/rb_benchmarks_$(date +%Y%m%d_%H%M%S).sql
cp .env /tmp/.env.backup.$(date +%Y%m%d_%H%M%S)
OLD_KEY=$(grep '^APP_KEY=' .env | cut -d= -f2)
```

---

## 2. FACEBOOK (Highest Priority)

**Console**: https://business.facebook.com/settings/system-users  
**Action**: Revoke access token + reset App Secret

```bash
# After resetting in console:
sed -i 's/FACEBOOK_APP_SECRET=.*/FACEBOOK_APP_SECRET=NEW_SECRET/' .env
sed -i 's/FB_DEFAULT_ACCESS_TOKEN=.*/FB_DEFAULT_ACCESS_TOKEN=NEW_TOKEN/' .env
```

---

## 3. GOOGLE SERVICE ACCOUNT KEYS

**Console**: https://console.cloud.google.com/iam-admin/serviceaccounts  
**Action**: Delete old key → Create new JSON → upload to server

```bash
mv ~/Downloads/new-key.json storage/app/google/rb-benchmarks-92561a9f40e5.json
chmod 600 storage/app/google/rb-benchmarks-92561a9f40e5.json
chown www-data:www-data storage/app/google/rb-benchmarks-92561a9f40e5.json
```

---

## 4. DATABASE PASSWORD

```bash
NEW_DB_PASS=$(openssl rand -base64 32 | tr -dc 'a-zA-Z0-9' | head -c 32)
mysql -u root -p -e "ALTER USER 'rb_benchmarks'@'localhost' IDENTIFIED BY '$NEW_DB_PASS'; FLUSH PRIVILEGES;"
sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$NEW_DB_PASS/" .env
```

---

## 5. OAUTH CLIENT SECRETS (Batch)

| Platform | Console URL | sed command |
|----------|-------------|-------------|
| **Snapchat** | https://developers.snapchat.com/ | `sed -i 's/SNAPCHAT_CLIENT_SECRET=.*/SNAPCHAT_CLIENT_SECRET=NEW/' .env` |
| **TikTok** | https://ads.tiktok.com/marketing_api/homepage | `sed -i 's/TIKTOK_APP_SECRET=.*/TIKTOK_APP_SECRET=NEW/' .env` |
| **Google Ads** | https://console.cloud.google.com/apis/credentials | `sed -i 's/GOOGLE_ADS_CLIENT_SECRET=.*/GOOGLE_ADS_CLIENT_SECRET=NEW/' .env` |
| **LinkedIn** | https://www.linkedin.com/developers/apps | `sed -i 's/LINKEDIN_CLIENT_SECRET=.*/LINKEDIN_CLIENT_SECRET=NEW/' .env` |

---

## 6. API KEYS & TOKENS

| Secret | Console URL | sed command |
|--------|-------------|-------------|
| **Google Ads Dev Token** | https://ads.google.com/aw/apicenter | `sed -i 's/GOOGLE_ADS_DEVELOPER_TOKEN=.*/GOOGLE_ADS_DEVELOPER_TOKEN=NEW/' .env` |
| **PageSpeed API Key** | https://console.cloud.google.com/apis/credentials | `sed -i 's/PAGESPEED_API_KEY=.*/PAGESPEED_API_KEY=NEW/' .env` |
| **Google OAuth2** | https://console.cloud.google.com/apis/credentials | Replace `storage/app/google/service-account.json` with new download |

---

## 7. APP_KEY (Last — Forces Re-login)

```bash
php artisan key:rotate --old-key="$OLD_KEY"
php artisan session:gc
php artisan cache:clear
php artisan config:clear
```

---

## 8. RESTART & VERIFY

```bash
sudo systemctl restart php8.2-fpm
sudo systemctl restart supervisor

# Verify no old secrets remain
grep -E 'REDACTED|REDACTED|REDACTED|REDACTED|REDACTED|REDACTED|REDACTED|REDACTED' .env && echo "❌ OLD SECRETS STILL PRESENT" || echo "✅ Old secrets cleared"

# Verify app boots
php artisan tinker --execute='echo "DB OK\n";'
```

---

## Quick Console Links

```
Facebook    https://business.facebook.com/settings/system-users
Google IAM  https://console.cloud.google.com/iam-admin/serviceaccounts
Google APIs https://console.cloud.google.com/apis/credentials
Google Ads  https://ads.google.com/aw/apicenter
LinkedIn    https://www.linkedin.com/developers/apps
Snapchat    https://developers.snapchat.com/
TikTok      https://ads.tiktok.com/marketing_api/homepage
```
