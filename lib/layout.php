<?php

declare(strict_types=1);

const SITE_BASE_URL = 'https://www.pinchards.is/waves';
const ASSETS_URL = 'assets';

/**
 * @return array{
 *   canonical_url: string,
 *   page_title: string,
 *   page_description: string
 * }
 */
function resolve_page_layout(): array
{
    return [
        'canonical_url' => SITE_BASE_URL . '/',
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
