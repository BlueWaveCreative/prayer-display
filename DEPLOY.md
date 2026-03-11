# Prayer Display — Deployment Guide

## SiteGround Setup

The app lives at `bluewavecreativedesign.com/prayer` (subdirectory, not a subdomain).

1. Create a `prayer` folder inside the Blue Wave site's `public_html/`
2. Upload everything flat into `public_html/prayer/`:
   - From `public/`: `index.php`, `.htaccess`, `css/`, `images/`
   - From root: `src/`, `api/`, `pages/`, `templates/`, `database/`, `cron/`, `uploads/`, `logs/`
   - `index.php` auto-detects flat vs nested structure — no path changes needed
3. Create MySQL database + user
4. Create `src/config.php` from `src/config.example.php` with production values
5. Set `BASE_PATH` to `/prayer` in config
6. Import database: `database/schema.sql` then `database/seed.sql`
7. Generate admin password hash: `php -r "echo password_hash('yourpassword', PASSWORD_BCRYPT);"`
8. Update seed.sql hash or manually update `admin_users` table
9. Set `uploads/` writable: `chmod 775 uploads/`
10. Set `logs/` writable: `chmod 775 logs/`

## PCO OAuth App

1. Go to https://api.planningcenteronline.com/oauth/applications
2. Create new application:
   - Name: Blue Wave Prayer Display
   - URL: https://bluewavecreativedesign.com/prayer
   - Redirect URI: https://bluewavecreativedesign.com/prayer/oauth/callback
3. Copy Client ID and Secret into `src/config.php`

## Cron Job

Add in SiteGround Site Tools > Cron Jobs:
- Command: `php /path/to/prayer-display/cron/refresh-tokens.php >> /path/to/prayer-display/logs/cron.log 2>&1`
- Frequency: Every hour

## Adding AT Wilmington (Tenant #1)

1. Log into admin panel at `bluewavecreativedesign.com/prayer/admin`
2. AT Wilmington should already exist from seed.sql
3. Click "Authorize" to connect PCO
4. Log into PCO as admin, approve the app
5. Verify token status shows "Healthy"
6. Visit `bluewavecreativedesign.com/prayer/d/at-wilmington` to confirm display works

## Adding a New Church

1. Log into admin panel
2. Click "+ Add Church"
3. Fill in: name, slug, timezone, event ID (if known)
4. Upload background image (or use default)
5. Click "Authorize" — send the link to church admin
6. Church admin approves in PCO
7. Share display URL: `https://bluewavecreativedesign.com/prayer/d/{slug}`
