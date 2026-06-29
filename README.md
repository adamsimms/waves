# Waves

WebGL ocean wave simulation driven by live buoy data from [SmartAtlantic ERDDAP](https://www.smartatlantic.ca/erddap/) (St. John's station). Live at [pinchards.is/waves/](https://www.pinchards.is/waves/).

| Entry point | Notes |
|-------------|--------|
| `index.php` | Default view with station readout |
| `wave.php` | Wide canvas layout |
| `wave2.php` | Wide canvas, hidden station panel |

`call-api.php` returns the latest wind, wave height, and period as JSON for the 10s polling loop.

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

## Local dev

```bash
php -S localhost:8080
```

Open `http://localhost:8080/` — PHP is required for the ERDDAP proxy on first load and `call-api.php`.
