<?php

declare(strict_types=1);

const SITE_BASE_URL = 'https://www.pinchards.is/waves';
const ASSETS_URL = 'assets';

/**
 * @return array{
 *   layout: string,
 *   show_station: bool,
 *   is_wide: bool,
 *   is_cinematic: bool,
 *   body_classes: list<string>,
 *   canonical_url: string,
 *   page_title: string,
 *   page_description: string
 * }
 */
function resolve_page_layout(): array
{
    $layout = isset($_GET['layout']) ? (string) $_GET['layout'] : 'default';
    $show_station = !isset($_GET['station']) || (string) $_GET['station'] !== '0';
    $is_wide = $layout === 'wide';
    $is_cinematic = $is_wide && !$show_station;

    $body_classes = ['layout-' . ($is_wide ? 'wide' : 'default')];
    if ($is_cinematic) {
        $body_classes[] = 'layout-wide-cinematic';
    }
    if (!$show_station) {
        $body_classes[] = 'station-hidden';
    }

    $canonical_url = SITE_BASE_URL . '/';
    if ($is_wide) {
        $canonical_url = SITE_BASE_URL . '/?layout=wide';
        if (!$show_station) {
            $canonical_url .= '&station=0';
        }
    }

    return [
        'layout' => $layout,
        'show_station' => $show_station,
        'is_wide' => $is_wide,
        'is_cinematic' => $is_cinematic,
        'body_classes' => $body_classes,
        'canonical_url' => $canonical_url,
        'page_title' => "Live Ocean Waves — St. John's Buoy | SmartAtlantic",
        'page_description' => 'Real-time WebGL ocean wave simulation driven by live wind and wave data from the SmartAtlantic St. John\'s buoy station off Newfoundland.',
    ];
}

/**
 * @param array<string, mixed> $station
 * @return array<string, mixed>
 */
function station_client_payload(array $station): array
{
    return [
        'name' => $station['station_name'],
        'time' => $station['time_display'],
        'timeIso' => $station['time'],
        'wind' => $station['wind'],
        'windDir' => $station['wind_dir'] ?? null,
        'windX' => $station['wind_x'],
        'windY' => $station['wind_y'],
        'size' => $station['size'],
        'wavePeriod' => $station['wave_period'] ?? max(0.0, (float) $station['size'] - 100.0),
        'choppiness' => $station['choppiness'],
        'stale' => !empty($station['stale']),
    ];
}
