# Upgrade Guide

## Upgrading to 4.10

### No breaking changes

v4.10 is fully backward-compatible. No migrations, no config changes, no action required for standard installations.

### Tracker JS — Vite pipeline unified (internal)

The JS beacon injected by `{{ statamic_analytics:tracker }}` is now compiled from a single source (`resources/js/tracker.js`) via the Vite/Rollup pipeline. The PHP heredoc that previously duplicated the tracker logic has been removed.

**For addon users (Composer install):** the compiled bundle (`resources/dist/tracker.js`) is shipped with the package. No build step required. The tracker behavior is identical.

**For installations that publish or override `ConsentBanner`:** if you have a local copy of `src/Tags/ConsentBanner.php` with the previous heredoc implementation, the `tracker()` method must be updated to read the bundle via `file_get_contents`. Refer to the published source for the new implementation.

**For maintainers forking this addon:** run `npm run build` after any modification to `resources/js/tracker.js`, then commit `resources/dist/tracker.js` before tagging a release.

### Composer constraint

```bash
composer require oliweb/statamic-privacy-analytics:^4.10
```

---

## Upgrading to 4.9

### No breaking changes

v4.9 is fully backward-compatible. No migrations, no config changes required.

### Privacy fixes

#### Server-side consent enforcement (full static mode)

Previously, when `tracking.consent.enabled` was `true`, consent was enforced only by the JS tracker. A direct call to `GET /statamic-analytics/track` bypassed the check entirely.

The beacon endpoint now verifies `session('analytics_consent')` server-side. A beacon is silently dropped (HTTP 204) if the visitor has not consented.

**What this means in practice:** The beacon endpoint now participates in the site's Laravel session when `consent.enabled` is active. The consent choice recorded by `POST /statamic-analytics/consent` is read on each beacon request. No additional cookie is set — the site's existing session cookie is used.

No action required if your installation uses `consent.enabled: false` (the default) — the endpoint remains stateless.

#### Granular geolocation opt-out now enforced server-side

The `geolocation` toggle in the consent banner UI was stored in the session but never read by the server. IP geolocation was performed regardless of the visitor's choice.

From v4.9, when a visitor declines geolocation, `GeolocationService::lookup()` is not called and `country_code`, `country_name`, `city` are stored as `NULL`. This applies to both the synchronous path and the async queue path (the flag is serialised in the job payload).

### Analytics metric corrections

#### Bounce rate

The previous bounce rate calculation was incorrect — it measured the ratio of new page URLs seen per session, not the proportion of single-page sessions.

The corrected formula:

```
bounce rate = sessions with exactly 1 page view / total sessions
```

**Your historical dashboard data will show different values from v4.9 onward.** The new figures are analytically meaningful; the previous ones were not.

#### Entry pages

Entry pages previously counted all URLs where `is_new_page_visit=true` within a session — this includes every new URL seen in a session, not just the first page visited.

Entry pages now uses `MIN(id)` per session to identify the single first page view, joined back to retrieve the URL. Concurrent beacons received in the same second are resolved deterministically by insertion order.

**Your historical entry pages data will show different (more accurate) values from v4.9 onward.**

### Other fixes

- **JS tracker date consistency** — The inline tracker script used `toISOString()` (UTC date) combined with `getHours()` (local time) for day/hour tracking, causing incorrect new-day detection around midnight in non-UTC timezones. Both now use local time, consistent with `tracker.js`.
- **`analytics:health` cache check** — The cache diagnostic now performs a full read/write/delete cycle and fails if the read returns an unexpected value.

### Composer constraint

```bash
composer require oliweb/statamic-privacy-analytics:^4.9
```

---

## Upgrading to 4.8

### No breaking changes

v4.8 is fully backward-compatible.

### New: dedicated database connection

Analytics tables can now live on a separate database connection, independently of your application's default connection.

```
# .env
ANALYTICS_DB_CONNECTION=analytics   # any connection defined in config/database.php
                                    # omit or set to null to keep the app default
```

All analytics queries, migrations, and the `analytics:health` command respect this setting. Useful when analytics data must be isolated (separate host, separate credentials, shared analytics DB across environments).

If you publish the config, the new key is:

```php
'database_connection' => env('ANALYTICS_DB_CONNECTION', null),
```

```bash
php artisan vendor:publish --tag=statamic-analytics-config --force
```

### PostgreSQL 16 officially supported

PostgreSQL 16 is now included in the CI test matrix alongside SQLite, MySQL 8, and MariaDB 11. Time-based expressions in the heatmap and session calculations use driver-specific SQL (`EXTRACT` for PostgreSQL, `HOUR`/`DAYOFWEEK` for MySQL/MariaDB, `strftime` for SQLite).

### Composer constraint

```bash
composer require oliweb/statamic-privacy-analytics:^4.8
```

---

## Upgrading to 4.7

### No breaking changes

v4.7 is fully backward-compatible.

### New: `analytics:health` diagnostic command

```bash
php artisan analytics:health
```

Runs 11 diagnostic checks and prints a colour-coded summary with actionable suggestions for any issue found:

| Check | What is verified |
|---|---|
| Configuration | Config file loaded |
| Database | Connection + tables present |
| Cache | Write / read / delete cycle |
| Encryption | `APP_KEY` is set |
| Queue | Configured connection exists; synchronous if `null` |
| Geolocation | Provider configured |
| MaxMind | Credentials, `.mmdb` file, age ≤ 30 days |
| Storage permissions | Cache, logs, geoip directories writable |
| Scheduler | `analytics-scheduler.log` activity within last 25 hours |
| Failed jobs | `TrackPageViewJob` count in `failed_jobs` |
| Static cache | Warns if `full` strategy is active without the tracker tag |
| Beacon endpoint | Route `statamic-analytics.track` registered |

Exits with code `1` on any failure — usable in deployment scripts and monitoring.

### Composer constraint

```bash
composer require oliweb/statamic-privacy-analytics:^4.7
```

---

## Upgrading to 4.6

### No breaking changes

v4.6 is fully backward-compatible. Existing installations continue to work without any modification.

### New: JS tracker for `STATAMIC_STATIC_CACHING_STRATEGY=full`

When Statamic serves pages via full static caching, nginx bypasses PHP entirely — the tracking middleware never runs. v4.6 introduces a JS tracker tag that bridges this gap.

#### Who is affected?

Only installations that use (or plan to use) `STATAMIC_STATIC_CACHING_STRATEGY=full`. All other configurations are unaffected.

#### What to do

Add the tag to your Antlers layout, once, alongside the consent banner if used:

```antlers
{{ statamic_analytics:tracker }}
{{ statamic_analytics:consent_banner }}
```

The tag is a no-op when `strategy` is not `full` — it is safe and recommended to add it unconditionally. When `strategy=full`, it injects an inline JS beacon that sends visit data to a new server-side endpoint (`GET /statamic-analytics/track`). The endpoint applies the same rules as the middleware: bot detection, IP/path exclusions, queue/sync support.

After any change to the static caching strategy, clear the static cache so pages regenerate with or without the script:

```bash
php artisan statamic:static:clear
```

#### What moves to the browser (strategy=full only)

| Data | Storage | Notes |
|---|---|---|
| `visitor_id` | `localStorage` | Persistent across sessions — more accurate new/returning detection |
| `session_id` | `sessionStorage` | Resets on tab close |
| Visited pages within session | `sessionStorage` | Used for `is_new_page_visit` |
| Last visit date / hour | `localStorage` | Used for `is_new_day_visit` / `is_new_hour_visit` |

#### Composer constraint

```bash
composer require oliweb/statamic-privacy-analytics:^4.6
```

---

## Upgrading to 4.5

### Breaking change (data discontinuity, no config change): device detection library

The device detection library has been replaced from **jenssegers/agent** (abandoned since 2020, no longer recognises recent mobile devices) with **matomo/device-detector** (actively maintained by the Matomo team).

#### What does NOT change

- `device_type` values (`tablet` / `mobile` / `desktop`) are strictly identical.
- No configuration, command, or database schema changes.

#### What may differ

The `browser` and `platform` column values may be formatted differently by the new library for the same browser or operating system.
Examples: `"Chrome"` vs `"Chromium"`, `"Windows"` vs `"Windows 10"`.

This change is **intentional and documented** — it is not a bug to fix.
The **Browser usage** and **Platforms / OS** dashboard widgets may display distinct entries for the same real browser or OS around the update date, then consolidate as old purged data is replaced by new records.

#### Composer constraint

```bash
composer require oliweb/statamic-privacy-analytics:^4.5
```

---

## Upgrading to 4.4

### Breaking change : CP permissions now required for non-super-admin users

Before v4.4, all authenticated CP users could access the analytics routes. From v4.4 onward, access is gated by two explicit permissions:

| Permission slug | Routes covered |
|---|---|
| `analytics.view` | Dashboard, data, geo-stats, real-time |
| `analytics.manage` | CSV export, geolocation stats reset |

**Super administrators are not affected** — they bypass permission checks automatically.

#### Who is affected?

Any CP user who is **not** a super administrator and who previously accessed the analytics dashboard. After updating, those users will receive a `403 Forbidden` response until one of the permissions above is assigned to their role.

#### How to grant access

1. Go to **CP → Users → Roles** and open (or create) the role assigned to your analytics users.
2. Under the **Analytics** permission group, tick **View analytics dashboard** and/or **Export and manage analytics data**.
3. Save the role.

Refer to [Statamic's documentation on roles and permissions](https://statamic.dev/users#permissions) for details on creating and assigning roles.

#### Minimal read-only access

If you only want users to see the dashboard without being able to export data or reset stats, assign `analytics.view` only. The export button and reset button will be hidden in the UI, and the corresponding routes return `403` server-side.

---

## Upgrading to 2.0

### Breaking change : default geolocation provider changed from `ip-api` to `maxmind`

Prior to 2.0, the addon used `ip-api` (external HTTP calls) as the default geolocation
provider. Starting with 2.0, the default is `maxmind` (local GeoLite2 database, no
external calls).

#### Who is affected?

You are affected if **you never set `ANALYTICS_GEO_PROVIDER` in your `.env`** and
relied on the implicit `ip-api` default. After upgrading, the addon will switch to
`maxmind` automatically.

#### Symptoms if MaxMind is not configured

- A warning banner will appear in the Control Panel analytics dashboard.
- Geolocation will silently return empty results (no country/city data recorded).
- Top countries and Top cities widgets will remain empty (but still visible).

#### Options

**Option A — Keep using `ip-api` (no credentials required):**
```
# .env
ANALYTICS_GEO_PROVIDER=ip-api
```

**Option B — Set up MaxMind GeoLite2 (recommended, full privacy):**
```
# .env
ANALYTICS_GEO_PROVIDER=maxmind
MAXMIND_ACCOUNT_ID=<your_account_id>
MAXMIND_LICENSE_KEY=<your_license_key>
```
Then download the database:
```bash
php artisan analytics:update-geoip
```
See the [README](README.md#maxmind-geolite2-recommended-for-full-privacy) for the
full setup procedure.

**Option C — Disable geolocation entirely:**
```
# .env
ANALYTICS_GEO_PROVIDER=disabled
```
Geographic widgets (Top countries, Top cities) will be hidden from the dashboard.
No IP data is sent to any external service.

---

### What does NOT change

- The `ip-api` and `disabled` providers continue to work exactly as before when
  explicitly configured via `ANALYTICS_GEO_PROVIDER`.
- Retrocompat: if your published config still uses the old boolean key `enabled`
  instead of `provider`, it continues to be honoured (`enabled=true` → `ip-api`,
  `enabled=false` → `disabled`). Migrate to `provider` at your next
  `vendor:publish --force`.
- All other configuration keys, dashboard widgets (non-geographic), and scheduled
  commands are unchanged.

---

### Composer constraint

Users installing via `^1.x` will **not** receive this update automatically — Composer
semver guarantees that. To upgrade explicitly:
```bash
composer require oliweb/statamic-privacy-analytics:^2.0
```

---

## Upgrading to 3.0

**Breaking change (destructive)**: an IP retention policy is now active by default. IP addresses and user-agents for page views older than 90 days will be automatically and irreversibly anonymised (set to NULL) by a daily scheduled command.

#### Who is affected?

All existing installations, from the moment they update to 3.0 and the scheduler runs for the first time after the upgrade.

#### What does NOT change

- Visit statistics (visits, page views, unique visitors) remain intact: they rely on `visitor_id`/`session_id`, not on `ip_address`.
- Already-resolved geolocation data (`country_code`, `city`) on existing page views are **not** affected — only the `ip_address` and `user_agent` columns are set to NULL.

#### Database migration

The `ip_address` column becomes nullable. Run migrations after updating the package:

```bash
php artisan migrate
```

#### Options

**Option A — Keep the default behaviour (recommended):**
Nothing to do. The 90-day retention applies automatically.

**Option B — Adjust the retention window:**
```
# .env
ANALYTICS_IP_RETENTION_DAYS=30
```
Or explicitly in `config/statamic-analytics.php` (after `vendor:publish`):
```php
'privacy' => [
    'ip_retention_days' => 30,
],
```

**Option C — Disable automatic anonymisation (not recommended):**
```
# .env
ANALYTICS_IP_RETENTION_DAYS=null
```
> ⚠️ The word `null` must be written literally, without quotes. A blank value
> (`ANALYTICS_IP_RETENTION_DAYS=`) is interpreted by Laravel as an empty string,
> not as `null`, and would NOT disable anonymisation — it would instead trigger
> immediate anonymisation of virtually all existing data on the next scheduler run.

Or explicitly in the config:
```php
'privacy' => [
    'ip_retention_days' => null,
],
```
> ⚠️ Unlimited IP retention is not recommended and may be difficult to justify depending on the requirements applicable to your deployment. Only enable this option knowingly.

#### Manual anonymisation

To trigger immediate anonymisation (without waiting for the scheduler):
```bash
# Preview affected rows
php artisan analytics:anonymize-ips --dry-run

# Run anonymisation
php artisan analytics:anonymize-ips
```

---

### Composer constraint

Users installing via `^2.x` will **not** receive this update automatically — Composer
semver guarantees that. To upgrade explicitly:
```bash
composer require oliweb/statamic-privacy-analytics:^3.0
```

---

## Upgrading to 4.0

### Breaking change: permanent raw event purge enabled by default

Starting with v4.0, a new daily scheduled command (`analytics:purge-raw-events`) **permanently and irreversibly** deletes rows from `statamic_analytics_page_views` older than 180 days. This is a row deletion, not anonymisation: the data is not recoverable.

#### Who is affected?

All existing installations with data older than 180 days, from the first scheduler run after the update.

#### What is preserved indefinitely

Daily aggregates in `statamic_analytics_aggregates` are **not affected** by the purge. The `_overview` dimension (new in v4.0) preserves per-day totals:
- Total visits, unique visitors, unique page views, returning visitors

Existing dimensions (country, device, browser, platform) are also preserved.

#### What does NOT survive beyond the raw retention window

The following widgets will show empty data for dates older than the retention window:
individual pages, traffic sources, referrers, hourly activity heatmap, session depth, avg. time on page, user flow.

#### Options

**Option A — Keep the default behaviour (recommended):**
Nothing to do. The 180-day raw retention applies automatically.

**Option B — Adjust the retention window:**
```
# .env
ANALYTICS_RAW_RETENTION_DAYS=365
```

**Option C — Disable automatic purge (not recommended):**
```
# .env
ANALYTICS_RAW_RETENTION_DAYS=null
```
> ⚠️ The word `null` must be written literally. A blank value (`ANALYTICS_RAW_RETENTION_DAYS=`) is read by Laravel as an empty string rather than `null` — the defensive guard treats this as unlimited retention, which is the intended fallback, but the intent remains ambiguous when reading the `.env` file.

#### Before updating

Run a `--dry-run` to assess the impact on your existing data:
```bash
php artisan analytics:purge-raw-events --dry-run
```

### Other changes in v4.0

- **CP route renamed**: `statamic-analytics.clear-cache` → `statamic-analytics.reset-stats` (since v3.2.0, noted here to flag the API change).
- **`AnalyticsSettingsController` removed**: controller with no route or view, leftover from the fork.
- **ip-api**: `file_get_contents` replaced by the `Http` facade (timeout 2 s / connect 1 s).
- **`visited_pages` session capped** at 20 entries maximum.
- **`_overview` in aggregates**: `analytics:process` now writes a daily summary without grouping (dimension `_overview`, dimension_value `_all`).

### Composer constraint

```bash
composer require oliweb/statamic-privacy-analytics:^4.0
```
