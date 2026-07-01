# Changelog

## Unreleased

### Added
- `lib/layout.php` for layout flags, canonical URLs, and client payload shaping
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
