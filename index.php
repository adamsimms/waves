<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/erddap.php';

$layout = $_GET['layout'] ?? 'default';
$show_station = !isset($_GET['station']) || $_GET['station'] !== '0';

$is_wide = $layout === 'wide';
$is_immersive = $is_wide && !$show_station;

$body_classes = ['layout-' . ($is_wide ? 'wide' : 'default')];
if ($is_immersive) {
    $body_classes[] = 'layout-immersive';
}
if (!$show_station) {
    $body_classes[] = 'station-hidden';
}

$station = station_data_with_fallback();

$page_title = "Live Ocean Waves — St. John's Buoy | SmartAtlantic";
$page_description = 'Real-time WebGL ocean wave simulation driven by live wind and wave data from the SmartAtlantic St. John\'s buoy station off Newfoundland.';
$canonical_path = 'index.php';
if ($is_wide) {
    $canonical_path .= '?layout=wide';
    if (!$show_station) {
        $canonical_path .= '&station=0';
    }
}
$canonical_url = 'https://www.pinchards.is/waves/' . $canonical_path;

$stale_notice = !empty($station['stale']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_description, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url, ENT_QUOTES, 'UTF-8'); ?>">

    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($page_description, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($canonical_url, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:locale" content="en_CA">

    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($page_description, ENT_QUOTES, 'UTF-8'); ?>">

    <meta name="theme-color" content="#0a1a2e">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,300,600,700,800&amp;display=swap" rel="stylesheet">
    <link href="waves.css" rel="stylesheet">

    <script type="application/ld+json">
    <?php
    echo json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'WebApplication',
        'name' => 'Waves — St. John\'s Buoy',
        'description' => $page_description,
        'url' => $canonical_url,
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

<body class="<?php echo htmlspecialchars(implode(' ', $body_classes), ENT_QUOTES, 'UTF-8'); ?>">
    <div id="overlay" aria-hidden="true"></div>

    <main id="ui">
        <section class="station-panel" id="station-panel" aria-label="Buoy conditions"<?php echo $show_station ? '' : ' hidden'; ?>>
            <h1 class="station-panel__title" id="station-name"><?php echo htmlspecialchars((string) $station['station_name'], ENT_QUOTES, 'UTF-8'); ?></h1>
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
                    <dt>Size</dt>
                    <dd><span id="station-size"></span> m</dd>
                </div>
                <div>
                    <dt>Choppiness</dt>
                    <dd><span id="station-choppiness"></span></dd>
                </div>
            </dl>
            <p class="station-panel__source">
                Live data from
                <a href="https://www.smartatlantic.ca/erddap/" rel="noopener noreferrer">SmartAtlantic ERDDAP</a>.
                Drag to orbit the view.
            </p>
        </section>
    </main>

    <div class="simulator-wrap">
        <canvas id="simulator" aria-label="Ocean wave simulation"></canvas>
    </div>

    <p id="error" role="alert">Your browser does not appear to support the required WebGL extensions.</p>

    <script>
        var INITIAL_SIZE = <?php echo json_encode($station['size']); ?>,
            INITIAL_WIND = [<?php echo json_encode($station['wind']); ?>, <?php echo json_encode($station['wind']); ?>],
            INITIAL_CHOPPINESS = <?php echo json_encode($station['choppiness']); ?>,
            STATION_NAME = <?php echo json_encode($station['station_name']); ?>,
            STATION_TIME = <?php echo json_encode($station['time_display']); ?>;
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
