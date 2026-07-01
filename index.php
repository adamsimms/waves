<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/erddap.php';
require_once __DIR__ . '/lib/layout.php';

$page = resolve_page_layout();
$station = station_data_with_fallback();
$client_station = station_client_payload($station);
$stale_notice = !empty($station['stale']);
$og_image = SITE_BASE_URL . '/og-image.png';
?>
<!DOCTYPE html>
<html lang="en-CA">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($page['page_title'], ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page['page_description'], ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?php echo htmlspecialchars($page['canonical_url'], ENT_QUOTES, 'UTF-8'); ?>">

    <link rel="icon" href="favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="favicon.svg">

    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo htmlspecialchars($page['page_title'], ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($page['page_description'], ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($page['canonical_url'], ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($og_image, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:locale" content="en_CA">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($page['page_title'], ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($page['page_description'], ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($og_image, ENT_QUOTES, 'UTF-8'); ?>">

    <meta name="theme-color" content="#0a1a2e">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    <link href="waves.css" rel="stylesheet">

    <script type="application/ld+json">
    <?php
    echo json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'WebApplication',
        'name' => 'Waves — St. John\'s Buoy',
        'description' => $page['page_description'],
        'url' => $page['canonical_url'],
        'dateModified' => $station['time'],
        'applicationCategory' => 'VisualizationApplication',
        'operatingSystem' => 'Web browser',
        'isAccessibleForFree' => true,
        'creator' => [
            '@type' => 'Person',
            'name' => 'Adam Simms',
        ],
        'keywords' => 'ocean waves, WebGL, buoy data, St. John\'s, Newfoundland, SmartAtlantic, ERDDAP',
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    ?>
    </script>
</head>

<body class="<?php echo htmlspecialchars(implode(' ', $page['body_classes']), ENT_QUOTES, 'UTF-8'); ?>">
    <div id="overlay" tabindex="0" aria-label="Drag, swipe, or use arrow keys to orbit the wave view"></div>

    <main id="ui">
        <section class="station-panel" id="station-panel" aria-label="Buoy conditions">
            <h1 class="station-panel__heading">Live Ocean Waves</h1>
            <p class="station-panel__station" id="station-name"><?php echo htmlspecialchars((string) $station['station_name'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p class="station-panel__meta">
                <time id="station-datetime" datetime="<?php echo htmlspecialchars((string) $station['time'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars((string) $station['time_display'], ENT_QUOTES, 'UTF-8'); ?>
                </time>
                <?php if ($stale_notice): ?>
                    <span class="station-panel__stale">Data may be stale</span>
                <?php endif; ?>
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
        window.STATION = <?php echo json_encode($client_station, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
        var INITIAL_SIZE = window.STATION.size;
        var INITIAL_WIND = [window.STATION.windX, window.STATION.windY];
        var INITIAL_CHOPPINESS = window.STATION.choppiness;
    </script>

    <script src="shared.js"></script>
    <script src="simulation.js"></script>
    <script src="waves.js"></script>
    <script src="station-poll.js"></script>

    <script async src="https://www.googletagmanager.com/gtag/js?id=G-G1XKSQNT5M"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());
        gtag('config', 'G-G1XKSQNT5M');
    </script>
</body>
</html>
