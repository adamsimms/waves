# Waves

Live WebGL ocean wave simulation driven by buoy data from [SmartAtlantic ERDDAP](https://www.smartatlantic.ca/erddap/) (St. John's station). Deployed at [pinchards.is/waves/](https://www.pinchards.is/waves/).

## What it does

The page renders a GPU-accelerated FFT ocean simulation and feeds it with the latest wind, wave height, and wave period readings from the St. John's SmartAtlantic buoy. Conditions refresh every 10 seconds and update both the on-screen readout and the simulation parameters.

## Layouts

All views are served from `index.php` with query parameters:

| URL | Description |
|-----|-------------|
| `/waves/` | Default view with station readout |
| `/waves/?layout=wide` | Wide panoramic canvas |
| `/waves/?layout=wide&station=0` | Wide cinematic canvas, station panel hidden |

Legacy URLs (`wave.php`, `wave2.php`) redirect via `.htaccess` — no duplicate PHP entry points.

## Project structure

The site root keeps only PHP endpoints and web metadata. Static assets live under `assets/`.

```
waves/
├── index.php              # HTML entry point
├── call-api.php           # JSON polling endpoint
├── health.php             # Monitoring endpoint
├── lib/
│   ├── erddap.php         # ERDDAP fetch, cache, NaN handling
│   └── layout.php         # Layout flags, canonical URLs, asset paths
├── assets/
│   ├── css/
│   │   └── waves.css
│   ├── js/
│   │   ├── shared.js      # Constants, math, shader helpers
│   │   ├── simulation.js  # WebGL FFT ocean simulator
│   │   ├── waves.js       # Bootstrap, orbit, render loop
│   │   └── station-poll.js
│   └── images/
│       └── favicon.svg
├── cache/                 # Writable ERDDAP cache (gitignored data)
├── tests/                 # PHPUnit tests
├── .htaccess              # Legacy redirects, CSP, cache headers
├── robots.txt
├── sitemap.xml
├── composer.json
├── phpunit.xml
├── LICENSE                # MIT license + attributions
├── CHANGELOG.md
└── README.md
```

### Why this layout

| Layer | Location | Rationale |
|-------|----------|-----------|
| PHP endpoints | Site root | DreamHost serves `index.php` and JSON APIs directly |
| Shared PHP | `lib/` | Reusable server logic, no public URLs |
| CSS / JS / images | `assets/` | Cacheable static files, clear separation |
| Tests | `tests/` | Not deployed; excluded from rsync implicitly by size |

## Data mapping

ERDDAP columns are mapped by name in `lib/erddap.php`:

| UI / output | ERDDAP field | Notes |
|-------------|--------------|-------|
| Wind (m/s) | `wind_spd_avg` | Scalar wind speed |
| Wind direction | `wind_dir_avg` | Meteorological degrees → `wind_x` / `wind_y` |
| Wave period (s) | `wave_period_max` | Shown in the station panel |
| Wave height (m) | `wave_ht_max` | Shown in the panel; drives shader choppiness |
| Shader size | `100 + wave_period_max` | Internal simulator parameter (`size`) |

Wind components use the meteorological convention (direction wind is **from**):

```
wind_x = -speed * sin(direction)
wind_y = -speed * cos(direction)
```

Missing or `NaN` readings fall back to the previous cached value when possible.

Responses are cached for 60 seconds in `cache/erddap-latest.json` with a file lock in `cache/erddap.lock`.

## Local development

```bash
php -S localhost:8080
```

Open [http://localhost:8080/](http://localhost:8080/). The `cache/` directory must be writable by PHP.

### Requirements

- PHP 8.0+ with `allow_url_fopen`
- WebGL with `OES_texture_float` and `OES_texture_float_linear`

### Tests

```bash
composer install
composer exec phpunit
```

CI runs `php -l` and PHPUnit on every push/PR (`.github/workflows/php.yml`).

## Deploy

On **push to `main`**, `.github/workflows/deploy.yml` rsyncs to `waves/` on DreamHost.

The workflow ensures `cache/` is writable and smoke-tests `call-api.php` and `health.php`.

| Secret | Notes |
|--------|--------|
| `FTP_SERVER` | SSH hostname |
| `FTP_USERNAME` | Shell user |
| `FTP_SERVER_DIR` | Site root (workflow appends `/waves`) |
| `SSH_DEPLOY_KEY` | ed25519 private key |

Use **Actions → Deploy → Run workflow** with `dry_run: true` to preview.

## Monitoring

| Endpoint | Purpose |
|----------|---------|
| `health.php` | Cache writable + latest ERDDAP fetch |
| `call-api.php` | Live buoy JSON |

`.github/workflows/uptime.yml` checks production every 30 minutes.

## SEO

- Descriptive title and meta description
- Canonical URLs without `index.php`
- Open Graph and Twitter Card tags (`summary` card; no `og:image` until a preview asset is added under `assets/images/`)
- JSON-LD `WebApplication` with `dateModified`
- `robots.txt`, `sitemap.xml`, favicon at `assets/images/favicon.svg`

Typography: [Open Sans](https://fonts.google.com/specimen/Open+Sans) via Google Fonts.

## Security

`.htaccess` sets Content-Security-Policy for same-origin assets, Google Fonts, and Google Analytics.

## Accessibility

- Touch and keyboard (arrow keys) orbit controls
- `prefers-reduced-motion` lowers sensitivity and skips live simulator retuning during polls

## License

[LICENSE](LICENSE) — MIT license with attributions for the ocean simulation, ERDDAP data source, and fonts.

## Changelog

[CHANGELOG.md](CHANGELOG.md)
