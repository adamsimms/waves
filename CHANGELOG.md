# Changelog

## Unreleased

### Changed
- Single wide layout is now the default; removed `?layout=wide` and `?layout=wide&station=0`
- Wide canvas styles merged into default CSS; layout query handling removed from `lib/layout.php`
- Legacy `wave.php`, `wave2.php`, and `?layout=` URLs redirect to `/waves/`

## 2026-07-01 — Restructure & docs

### Added
- `assets/` tree: `assets/css/`, `assets/js/`, `assets/images/`
- Deploy smoke tests for static asset URLs

### Changed
- Static files moved out of the site root into `assets/`
- `NOTICE` merged into `LICENSE` (Attributions section)
- README trimmed; Twitter Card uses `summary` (no social preview image for now)

### Removed
- `wave.php` and `wave2.php` (legacy URLs redirect via `.htaccess` only)
- `og-image.png`, `og-image.svg`, and Open Graph image meta tags
- Standalone `NOTICE` file

## 2026-07-01 — Pass 3

### Added
- PHPUnit CI, health endpoint, uptime workflow
- Keyboard orbit, CSP header, `wave_period` in API JSON

### Changed
- Station labels: wave period (s), wave height (m)
- Google Fonts via CSS2 API

## 2026-07-01 — Pass 2

### Added
- Named ERDDAP columns, wind direction, cache lock
- `robots.txt`, `sitemap.xml`, `favicon.svg`, `.htaccess` redirects
- Touch controls, shader error reporting, deploy hardening

### Changed
- Canonical URLs, wide layout fix, `window.STATION`, `layout-wide-cinematic`

### Removed
- Unused vector helpers, redundant station `hidden` attribute

## 2026-07-01 — Pass 1

### Added
- Single `index.php`, `lib/erddap.php`, `station-poll.js`, SEO meta, LICENSE

### Removed
- jQuery, `ui.js`, hidden 3D camera UI
