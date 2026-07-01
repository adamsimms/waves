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
| `?layout=wide&station=0` | `wave2.php` | Wide canvas, station panel hidden |

`wave.php` and `wave2.php` remain as permanent redirects for old links.

## Project structure

```
index.php           Single HTML entry point (layouts via query string)
call-api.php        JSON endpoint for the 10s polling loop
lib/erddap.php      Shared ERDDAP fetch, cache, and error handling
station-poll.js     Polls call-api.php; updates readout + simulator
shared.js           Shared constants and math helpers
simulation.js       WebGL FFT ocean simulator
waves.js            Page bootstrap, camera orbit, render loop
waves.css           Layout and station panel styles
cache/              Server-writable ERDDAP response cache (gitignored data file)
```

## Data mapping

ERDDAP columns are mapped in `lib/erddap.php`:

| Simulation input | ERDDAP field | Notes |
|------------------|--------------|-------|
| Wind (m/s) | `wind_spd_avg` | Applied equally on X and Y axes |
| Choppiness | `wave_ht_max` | Significant wave height proxy |
| Size | `100 + wave_period_max` | Wave period (seconds) offset by 100 for the shader range |

Responses are cached for 60 seconds in `cache/erddap-latest.json` to limit upstream requests. If ERDDAP is unreachable, the app serves the last cached reading or safe fallback defaults.

## Local development

PHP is required for the ERDDAP proxy and JSON endpoint.

```bash
php -S localhost:8080
```

Open [http://localhost:8080/](http://localhost:8080/). The `cache/` directory must be writable by PHP.

### Requirements

- PHP 8.0+ with `allow_url_fopen` (or swap in cURL in `lib/erddap.php`)
- A browser with WebGL and `OES_texture_float` / `OES_texture_float_linear`

## Deploy

On **push to `main`**, `.github/workflows/deploy.yml` rsyncs this repo to `waves/` on DreamHost (same server as [pinchards.is](https://github.com/adamsimms/pinchards.is)).

### Repository secrets

Reuse the DreamHost deploy secrets from pinchards.is:

| Secret | Notes |
|--------|--------|
| `FTP_SERVER` | SSH hostname |
| `FTP_USERNAME` | Shell user |
| `FTP_SERVER_DIR` | Site root, e.g. `/home/USER/pinchards.is` (workflow appends `/waves`) |
| `SSH_DEPLOY_KEY` | ed25519 private key (base64-encoded single line) |

Use **Actions → Deploy SFTP → Run workflow** with `dry_run: true` to preview changes.

Ensure the deployed `cache/` directory is writable by the web server user after the first deploy.

## SEO

`index.php` includes:

- Descriptive `<title>` and meta description
- Canonical URL per layout
- Open Graph and Twitter Card tags
- JSON-LD `WebApplication` structured data
- Semantic HTML (`main`, `section`, `dl` metrics, `time` element)
- Mobile viewport meta tag

Optional: add `og-image.png` at the site root and wire `og:image` / `twitter:image` meta tags if you want rich link previews.

## License

See [LICENSE](LICENSE) and [NOTICE](NOTICE).
