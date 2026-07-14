# Waves

Live WebGL ocean wave simulation driven by [SmartAtlantic ERDDAP](https://www.smartatlantic.ca/erddap/) buoy data (St. John's).

**Canonical:** [art.adamsimms.xyz/waves/](https://art.adamsimms.xyz/waves/)

GPU FFT ocean simulation fed by live wind, wave height, and wave period. The station panel and simulator refresh about every 10 seconds. Live data comes from the art Pages Function at `/waves/call-api` (health: `/waves/health`).

## Structure

```
assets/css/, assets/js/, assets/images/   # front-end
lib/                                      # ERDDAP mapping helpers (build/test)
scripts/                                  # static export for art assemble
tests/
```

## Data

| Output | ERDDAP field |
|--------|--------------|
| Wind (m/s) | `wind_spd_avg` |
| Wind vector | `wind_dir_avg` → `wind_x` / `wind_y` |
| Wave period (s) | `wave_period_max` |
| Wave height (m) | `wave_ht_max` |

## Development

Serve `assets/` + exported static page, or prefer verifying on art after assemble. WebGL float textures required.

```bash
composer install && composer exec phpunit   # unit tests for ERDDAP helpers
```

Static HTML for Pages:

```bash
# used by art assemble; see art PHASE4-SIBLINGS
```

## Ship

Production is **art.adamsimms.xyz** (assembled into `/waves/` + Functions). Uptime checks hit art.

MIT — see [LICENSE](LICENSE). Changes: [CHANGELOG.md](CHANGELOG.md).
