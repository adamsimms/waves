# Waves

Live WebGL ocean wave simulation driven by buoy data from [SmartAtlantic ERDDAP](https://www.smartatlantic.ca/erddap/) (St. John's station). Deployed at [pinchards.is/waves/](https://www.pinchards.is/waves/).

## What it does

The page renders a GPU-accelerated FFT ocean simulation and feeds it with the latest wind, wave height, and wave period readings from the St. John's SmartAtlantic buoy. Conditions refresh every 10 seconds and update both the on-screen readout and the simulation parameters.

## Layouts

All views are served from `index.php` with query parameters:

| URL | Former page | Description |
|-----|-------------|-------------|
| `/` | `index.php` | Default view with station readout |
| `?layout=wide` | `wave.php` | Wide panoramic canvas |
| `?layout=wide&station=0` | `wave2.php` | Wide cinematic canvas, station panel hidden |

`wave.php` and `wave2.php` remain as permanent redirects (PHP and `.htaccess`) for old links.

## Project structure

```
index.php           Single HTML entry point (layouts via query string)
call-api.php        JSON endpoint for the 10s polling loop
health.php          Monitoring endpoint (cache + ERDDAP status)
lib/
  erddap.php        ERDDAP fetch, named columns, cache lock, NaN handling
  layout.php        Layout flags, canonical URLs, client payload helper
station-poll.js     Polls call-api.php; updates readout + simulator
shared.js           Shared constants, math helpers, shader utilities
simulation.js       WebGL FFT ocean simulator
waves.js            Page bootstrap, camera orbit, render loop
waves.css           Layout and station panel styles
tests/              PHPUnit tests for lib/erddap.php
cache/              Server-writable ERDDAP cache and lock files
robots.txt          Crawler rules
sitemap.xml         Public layout URLs
favicon.svg         Site icon
og-image.png        Social preview image (Open Graph / Twitter)
.htaccess           Legacy redirects, CSP, and static asset cache headers
```

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

Responses are cached for 60 seconds in `cache/erddap-latest.json` with a file lock in `cache/erddap.lock` to prevent upstream stampedes.

## Local development

PHP is required for the ERDDAP proxy and JSON endpoint.

```bash
php -S localhost:8080
```

Open [http://localhost:8080/](http://localhost:8080/). The `cache/` directory must be writable by PHP.

### Requirements

- PHP 8.0+ with `allow_url_fopen` (or swap in cURL in `lib/erddap.php`)
- A browser with WebGL and `OES_texture_float` / `OES_texture_float_linear`

### Tests

```bash
composer install
composer exec phpunit
```

GitHub Actions also runs `php -l` on every push/PR (`.github/workflows/php.yml`).

## Deploy

On **push to `main`**, `.github/workflows/deploy.yml` rsyncs this repo to `waves/` on DreamHost (same server as [pinchards.is](https://github.com/adamsimms/pinchards.is)).

The workflow also:

- Ensures `cache/` exists and is writable (`chmod 775`)
- Smoke-tests `call-api.php` and `health.php` after deploy

### Repository secrets

Reuse the DreamHost deploy secrets from pinchards.is:

| Secret | Notes |
|--------|--------|
| `FTP_SERVER` | SSH hostname |
| `FTP_USERNAME` | Shell user |
| `FTP_SERVER_DIR` | Site root, e.g. `/home/USER/pinchards.is` (workflow appends `/waves`) |
| `SSH_DEPLOY_KEY` | ed25519 private key (base64-encoded single line) |

Use **Actions → Deploy → Run workflow** with `dry_run: true` to preview changes.

## Monitoring

| Endpoint | Purpose |
|----------|---------|
| `health.php` | JSON status: cache writable, latest station fetch |
| `call-api.php` | Live buoy JSON used by the page |

`.github/workflows/uptime.yml` curls production every 30 minutes. You can also point [UptimeRobot](https://uptimerobot.com/) or similar at:

- `https://www.pinchards.is/waves/health.php`
- `https://www.pinchards.is/waves/call-api.php`

## SEO

`index.php` includes:

- Descriptive `<title>` and meta description
- Clean canonical URLs (`https://www.pinchards.is/waves/`, no `index.php`)
- Open Graph and Twitter Card tags with `og-image.png`
- JSON-LD `WebApplication` structured data with `dateModified`
- Semantic HTML (`main`, `section`, `dl` metrics, `time` element)
- `robots.txt`, `sitemap.xml`, and `favicon.svg`

Typography is loaded from [Google Fonts](https://fonts.google.com/specimen/Open+Sans) (Open Sans).

## Security

`.htaccess` sets a Content-Security-Policy allowing:

- Same-origin scripts and styles
- Google Fonts (`fonts.googleapis.com`, `fonts.gstatic.com`)
- Google Analytics / Tag Manager

## Accessibility

- Touch orbit controls on mobile (`touch-action: none` on the overlay)
- Keyboard orbit with arrow keys (focus the overlay)
- `prefers-reduced-motion` lowers orbit sensitivity and skips live simulator retuning during polls
- Overlay is exposed to assistive tech with an orbit label

## License

See [LICENSE](LICENSE) and [NOTICE](NOTICE).

## Changelog

See [CHANGELOG.md](CHANGELOG.md).
