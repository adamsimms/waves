#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Export a static Waves index.html for assemble-into art Pages.
 *
 * Usage: php scripts/export-static.php --out=/path/to/dist/waves
 */

$repo = dirname(__DIR__);
$out = $repo . '/dist-static';
$siteBase = getenv('WAVES_SITE_BASE_URL') ?: 'https://art.adamsimms.xyz/waves';

foreach (array_slice($argv, 1) as $arg) {
	if (str_starts_with($arg, '--out=')) {
		$out = rtrim(substr($arg, 6), '/');
	}
}

require_once $repo . '/lib/erddap.php';
require_once $repo . '/lib/layout.php';

if (!is_dir($out) && !mkdir($out, 0755, true) && !is_dir($out)) {
	fwrite(STDERR, "Could not create output dir: {$out}\n");
	exit(1);
}

$station = erddap_default_station_data();
$station['stale'] = true;
$client = station_client_payload($station);

$pageTitle = "Live Ocean Waves — St. John's Buoy | SmartAtlantic";
$pageDescription = 'Real-time WebGL ocean wave simulation driven by live wind and wave data from the SmartAtlantic St. John\'s buoy station off Newfoundland.';
$canonical = rtrim($siteBase, '/') . '/';
$assets = 'assets';

$ld = json_encode([
	'@context' => 'https://schema.org',
	'@type' => 'WebApplication',
	'name' => 'Waves — St. John\'s Buoy',
	'description' => $pageDescription,
	'url' => $canonical,
	'applicationCategory' => 'VisualizationApplication',
	'operatingSystem' => 'Web browser',
	'isAccessibleForFree' => true,
	'creator' => ['@type' => 'Person', 'name' => 'Adam Simms'],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

$stationJson = json_encode($client, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

$html = <<<HTML
<!DOCTYPE html>
<html lang="en-CA">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$pageTitle}</title>
    <meta name="description" content="{$pageDescription}">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{$canonical}">

    <link rel="icon" href="{$assets}/images/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{$assets}/images/favicon.svg">

    <meta property="og:type" content="website">
    <meta property="og:title" content="{$pageTitle}">
    <meta property="og:description" content="{$pageDescription}">
    <meta property="og:url" content="{$canonical}">
    <meta property="og:locale" content="en_CA">

    <meta name="theme-color" content="#0a1a2e">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    <link href="{$assets}/css/waves.css" rel="stylesheet">

    <script type="application/ld+json">
    {$ld}
    </script>
</head>

<body>
    <div id="overlay" tabindex="0" aria-label="Drag, swipe, or use arrow keys to orbit the wave view"></div>

    <main id="ui">
        <section class="station-panel" id="station-panel" aria-label="Buoy conditions">
            <h1 class="station-panel__heading">Live Ocean Waves</h1>
            <p class="station-panel__station" id="station-name"></p>
            <p class="station-panel__meta">
                <time id="station-datetime"></time>
                <span class="station-panel__stale">Connecting to buoy…</span>
            </p>
            <dl class="station-panel__metrics">
                <div>
                    <dt>Wind</dt>
                    <dd><span id="station-wind"></span> m/s</dd>
                </div>
                <div>
                    <dt>Wave period</dt>
                    <dd><span id="station-period"></span> s</dd>
                </div>
                <div>
                    <dt>Wave height</dt>
                    <dd><span id="station-choppiness"></span> m</dd>
                </div>
            </dl>
            <p class="station-panel__source">
                Live data from
                <a href="https://www.smartatlantic.ca/erddap/" rel="noopener noreferrer">SmartAtlantic ERDDAP</a>.
                Drag, swipe, or use arrow keys to orbit the view.
            </p>
        </section>
    </main>

    <div class="simulator-wrap">
        <canvas id="simulator" aria-label="Ocean wave simulation"></canvas>
    </div>

    <p id="error" role="alert">Your browser does not appear to support the required WebGL extensions.</p>

    <script>
        window.STATION = {$stationJson};
        var INITIAL_SIZE = window.STATION.size;
        var INITIAL_WIND = [window.STATION.windX, window.STATION.windY];
        var INITIAL_CHOPPINESS = window.STATION.choppiness;
    </script>

    <script src="{$assets}/js/shared.js"></script>
    <script src="{$assets}/js/simulation.js"></script>
    <script src="{$assets}/js/waves.js"></script>
    <script src="{$assets}/js/station-poll.js"></script>
</body>
</html>
HTML;

file_put_contents($out . '/index.html', $html);
echo "Wrote {$out}/index.html\n";
