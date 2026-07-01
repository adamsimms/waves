<?php

declare(strict_types=1);

const ERDDAP_STATION_URL = 'https://www.smartatlantic.ca/erddap/tabledap/SMA_st_johns.json';
const ERDDAP_COLUMNS = 'station_name,time,longitude,latitude,wind_spd_avg,wind_spd_max,wind_dir_avg,air_temp_avg,air_pressure_avg,air_humidity_avg,air_dewpoint_avg,surface_temp_avg,wave_ht_max,wave_ht_sig,wave_period_max,wave_dir_avg,wave_spread_avg,curr_dir_avg,curr_spd_avg';
const ERDDAP_LOOKBACK_SECONDS = 3 * 24 * 60 * 60;
const ERDDAP_CACHE_TTL_SECONDS = 60;

/**
 * @return array{
 *   station_name: string,
 *   time: string,
 *   time_display: string,
 *   wind: float,
 *   size: float,
 *   choppiness: float,
 *   longitude: float|null,
 *   latitude: float|null
 * }
 */
function fetch_latest_station_data(bool $use_cache = true): array
{
    if ($use_cache) {
        $cached = erddap_read_cache();
        if ($cached !== null) {
            return $cached;
        }
    }

    $data = erddap_fetch_from_api();
    erddap_write_cache($data);

    return $data;
}

/**
 * @return array{
 *   station_name: string,
 *   time: string,
 *   time_display: string,
 *   wind: float,
 *   size: float,
 *   choppiness: float,
 *   longitude: float|null,
 *   latitude: float|null
 * }
 */
function erddap_fetch_from_api(): array
{
    date_default_timezone_set('UTC');

    $current_date = date('Y-m-d\TH:i:s\Z');
    $start_date = date('Y-m-d\TH:i:s\Z', strtotime($current_date) - ERDDAP_LOOKBACK_SECONDS);
    $url = ERDDAP_STATION_URL
        . '?'
        . ERDDAP_COLUMNS
        . '&time>='
        . rawurlencode($start_date)
        . '&time<='
        . rawurlencode($current_date);

    $context = stream_context_create([
        'http' => [
            'timeout' => 15,
            'header' => "Accept: application/json\r\n",
        ],
    ]);

    $result = @file_get_contents($url, false, $context);
    if ($result === false) {
        throw new RuntimeException('Unable to reach SmartAtlantic ERDDAP.');
    }

    $payload = json_decode($result, true);
    if (!is_array($payload)) {
        throw new RuntimeException('Invalid JSON from SmartAtlantic ERDDAP.');
    }

    $rows = $payload['table']['rows'] ?? null;
    if (!is_array($rows) || count($rows) === 0) {
        throw new RuntimeException('No buoy readings returned for the requested time range.');
    }

    $latest = $rows[count($rows) - 1];
    if (!is_array($latest) || count($latest) < 15) {
        throw new RuntimeException('Unexpected ERDDAP row shape.');
    }

    $observed_at = (string) $latest[1];
    $wind = erddap_to_float($latest[4]);
    $choppiness = erddap_to_float($latest[12]);
    $wave_period = erddap_to_float($latest[14]);

    return [
        'station_name' => (string) $latest[0],
        'time' => $observed_at,
        'time_display' => date('Y-m-d H:i:s', strtotime($observed_at)),
        'wind' => $wind,
        'size' => 100 + $wave_period,
        'choppiness' => $choppiness,
        'longitude' => erddap_optional_float($latest[2] ?? null),
        'latitude' => erddap_optional_float($latest[3] ?? null),
    ];
}

function erddap_to_float(mixed $value): float
{
    if ($value === null || $value === '') {
        return 0.0;
    }

    return (float) $value;
}

function erddap_optional_float(mixed $value): ?float
{
    if ($value === null || $value === '') {
        return null;
    }

    return (float) $value;
}

function erddap_cache_path(): string
{
    return dirname(__DIR__) . '/cache/erddap-latest.json';
}

/**
 * @return array<string, mixed>|null
 */
function erddap_read_cache(): ?array
{
    $path = erddap_cache_path();
    if (!is_file($path)) {
        return null;
    }

    $raw = @file_get_contents($path);
    if ($raw === false) {
        return null;
    }

    $payload = json_decode($raw, true);
    if (!is_array($payload) || !isset($payload['fetched_at'], $payload['data'])) {
        return null;
    }

    if (time() - (int) $payload['fetched_at'] > ERDDAP_CACHE_TTL_SECONDS) {
        return null;
    }

    return $payload['data'];
}

/**
 * @param array<string, mixed> $data
 */
function erddap_write_cache(array $data): void
{
    $path = erddap_cache_path();
    $directory = dirname($path);
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        return;
    }

    $payload = json_encode([
        'fetched_at' => time(),
        'data' => $data,
    ], JSON_UNESCAPED_SLASHES);

    if ($payload !== false) {
        @file_put_contents($path, $payload, LOCK_EX);
    }
}

/**
 * @param array<string, mixed> $data
 */
function station_data_to_json(array $data): string
{
    return json_encode([
        'station_name' => $data['station_name'],
        'time' => $data['time_display'],
        'wind' => $data['wind'],
        'size' => $data['size'],
        'choppiness' => $data['choppiness'],
    ], JSON_UNESCAPED_SLASHES) ?: '{}';
}

/**
 * @return array<string, mixed>
 */
function station_data_with_fallback(): array
{
    try {
        return fetch_latest_station_data();
    } catch (Throwable $exception) {
        $cached = erddap_read_stale_cache();
        if ($cached !== null) {
            $cached['stale'] = true;
            return $cached;
        }

        return [
            'station_name' => "St. John's Buoy",
            'time' => gmdate('Y-m-d\TH:i:s\Z'),
            'time_display' => gmdate('Y-m-d H:i:s'),
            'wind' => 10.0,
            'size' => 250.0,
            'choppiness' => 1.5,
            'longitude' => null,
            'latitude' => null,
            'stale' => true,
            'error' => $exception->getMessage(),
        ];
    }
}

/**
 * @return array<string, mixed>|null
 */
function erddap_read_stale_cache(): ?array
{
    $path = erddap_cache_path();
    if (!is_file($path)) {
        return null;
    }

    $raw = @file_get_contents($path);
    if ($raw === false) {
        return null;
    }

    $payload = json_decode($raw, true);
    if (!is_array($payload) || !isset($payload['data']) || !is_array($payload['data'])) {
        return null;
    }

    return $payload['data'];
}
