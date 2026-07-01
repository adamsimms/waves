# Changelog

## Unreleased

### Added
- `og-image.png` for social link previews
- PHP lint + PHPUnit workflow (`.github/workflows/php.yml`)
- `health.php` monitoring endpoint
- Scheduled production uptime checks (`.github/workflows/uptime.yml`)
- Keyboard orbit controls (arrow keys)
- PHPUnit coverage for `lib/erddap.php`
- Content-Security-Policy header in `.htaccess`
- `wave_period` exposed in API JSON and station readout

### Changed
- Station panel labels: **Wave period** (seconds) and **Wave height** (metres)
- Google Fonts loaded via CSS2 API (`fonts.googleapis.com/css2`)
- Open Graph / Twitter images point to `og-image.png`

## 2026-07-01 — Pass 2

### Added
- Named ERDDAP column parsing via `columnNames`
- Wind direction support (`wind_dir_avg` → `wind_x` / `wind_y`)
- ERDDAP cache lock to prevent upstream stampedes
- `robots.txt`, `sitemap.xml`, `favicon.svg`, and `og-image.svg`
- `.htaccess` legacy redirects and static asset cache headers
- Touch orbit controls and `prefers-reduced-motion` handling
- Shader compile/link error reporting
- Deploy workflow cache permissions step and `call-api.php` smoke test

### Changed
- Canonical URLs no longer include `index.php`
- Wide layout CSS no longer overridden by `waves.js` resize inline styles
- `layout-immersive` renamed to `layout-wide-cinematic`
- Station boot config consolidated into `window.STATION`
- Deploy workflow renamed from "Deploy SFTP" to "Deploy"

### Removed
- Unused vector helpers from `shared.js`
- Redundant `hidden` attribute on the station panel (CSS `station-hidden` only)
- Legacy orbit mode state (`NONE` / `ORBITING`)

### Fixed
- Wide layout canvas positioning
- `<time datetime>` updates during polling
- ERDDAP `NaN` / missing values preserving last good readings
- `call-api.php` `Cache-Control: max-age=10`

## 2026-07-01

### Added
- Initial GitHub import and consolidation pass
- Shared `lib/erddap.php`, single `index.php`, `station-poll.js`
- SEO meta tags, README, LICENSE, and NOTICE

### Removed
- jQuery, `ui.js`, and hidden 3D camera UI
