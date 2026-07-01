# Waves

Live WebGL ocean wave simulation driven by [SmartAtlantic ERDDAP](https://www.smartatlantic.ca/erddap/) buoy data (St. John's). [pinchards.is/waves/](https://www.pinchards.is/waves/)

GPU FFT ocean simulation fed by live wind, wave height, and wave period. The station panel and simulator refresh every 10 seconds.

Legacy `wave.php`, `wave2.php`, and `?layout=` URLs redirect to `/waves/`.

## Structure

```
index.php, call-api.php, health.php   # endpoints
lib/                                  # ERDDAP fetch, layout
assets/css/, assets/js/, assets/images/
cache/                                # ERDDAP cache (writable)
tests/
```

## Data

Mapped in `lib/erddap.php` by column name:

| Output | ERDDAP field |
|--------|--------------|
| Wind (m/s) | `wind_spd_avg` |
| Wind vector | `wind_dir_avg` → `wind_x` / `wind_y` |
| Wave period (s) | `wave_period_max` |
| Wave height (m) | `wave_ht_max` |

Cached 60s in `cache/erddap-latest.json`. `NaN` values fall back to the previous reading when possible.

## Development

```bash
php -S localhost:8080
composer install && composer exec phpunit
```

Requires PHP 8.0+ (`allow_url_fopen`) and WebGL float textures.

## Deploy

Push to `main` → rsync to DreamHost (`/.github/workflows/deploy.yml`).

Secrets: `FTP_SERVER`, `FTP_USERNAME`, `FTP_SERVER_DIR`, `SSH_DEPLOY_KEY`.

MIT — see [LICENSE](LICENSE). Changes: [CHANGELOG.md](CHANGELOG.md).
