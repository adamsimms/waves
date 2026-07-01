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
 *   wind_dir: float|null,
 *   wind_x: float,
 *   wind_y: float,
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

    $lock = erddap_acquire_lock();
    try {
        if ($use_cache) {
            $cached = erddap_read_cache();
            if ($cached !== null) {
                return $cached;
            }
        }

        $data = erddap_fetch_from_api();
        erddap_write_cache($data);

        return $data;
    } finally {
        erddap_release_lock($lock);
    }
}

/**
 * @return array{
 *   station_name: string,
 *   time: string,
 *   time_display: string,
 *   wind: float,
 *   wind_dir: float|null,
 *   wind_x: float,
 *   wind_y: float,
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
    $column_names = $payload['table']['columnNames'] ?? null;
    if (!is_array($rows) || count($rows) === 0 || !is_array($column_names)) {
        throw new RuntimeException('No buoy readings returned for the requested time range.');
    }

    $latest = $rows[count($rows) - 1];
    if (!is_array($latest) || count($latest) !== count($column_names)) {
        throw new RuntimeException('Unexpected ERDDAP row shape.');
    }

    $fields = erddap_row_to_fields($column_names, $latest);
    $previous = erddap_read_stale_cache() ?? erddap_default_station_data();

    return erddap_build_station_data($fields, $previous);
}

/**
 * @param array<string, mixed> $data
 * @return array<string, mixed>
 */
function erddap_normalize_station_data(array $data): array
{
    if (!isset($data['wind_x'], $data['wind_y'])) {
        $components = erddap_wind_components(
            (float) ($data['wind'] ?? 10.0),
            erddap_optional_float($data['wind_dir'] ?? null),
            $data
        );
        $data['wind_x'] = $components['wind_x'];
        $data['wind_y'] = $components['wind_y'];
    }

    return $data;
}

/**
 * @param list<string> $column_names
 * @param list<mixed> $row
 * @return array<string, mixed>
 */
function erddap_row_to_fields(array $column_names, array $row): array
{
    $fields = [];
    foreach ($column_names as $index => $name) {
        $fields[$name] = $row[$index] ?? null;
    }

    return $fields;
}

/**
 * @param array<string, mixed> $fields
 * @param array<string, mixed> $previous
 * @return array<string, mixed>
 */
function erddap_build_station_data(array $fields, array $previous): array
{
    $observed_at = erddap_string_value($fields['time'] ?? null, (string) ($previous['time'] ?? gmdate('Y-m-d\TH:i:s\Z')));
    $wind = erddap_resolve_float($fields['wind_spd_avg'] ?? null, (float) ($previous['wind'] ?? 10.0));
    $wind_dir = erddap_optional_float($fields['wind_dir_avg'] ?? null);
    if ($wind_dir === null && isset($previous['wind_dir'])) {
        $wind_dir = erddap_optional_float($previous['wind_dir']);
    }

    $wave_period = erddap_resolve_float($fields['wave_period_max'] ?? null, max(0.0, (float) ($previous['size'] ?? 250.0) - 100.0));
    $choppiness = erddap_resolve_float($fields['wave_ht_max'] ?? null, (float) ($previous['choppiness'] ?? 1.5));
    $components = erddap_wind_components($wind, $wind_dir, $previous);

    return [
        'station_name' => erddap_string_value($fields['station_name'] ?? null, (string) ($previous['station_name'] ?? "St. John's Buoy")),
        'time' => $observed_at,
        'time_display' => date('Y-m-d H:i:s', strtotime($observed_at)),
        'wind' => $wind,
        'wind_dir' => $wind_dir,
        'wind_x' => $components['wind_x'],
        'wind_y' => $components['wind_y'],
        'size' => 100 + $wave_period,
        'choppiness' => $choppiness,
        'longitude' => erddap_optional_float($fields['longitude'] ?? null) ?? erddap_optional_float($previous['longitude'] ?? null),
        'latitude' => erddap_optional_float($fields['latitude'] ?? null) ?? erddap_optional_float($previous['latitude'] ?? null),
    ];
}

/**
 * @param array<string, mixed> $previous
 * @return array{wind_x: float, wind_y: float}
 */
function erddap_wind_components(float $speed, ?float $direction, array $previous): array
{
    if ($direction !== null && $speed > 0) {
        $radians = deg2rad($direction);

        return [
            'wind_x' => -$speed * sin($radians),
            'wind_y' => -$speed * cos($radians),
        ];
    }

    if (isset($previous['wind_x'], $previous['wind_y']) && $speed > 0) {
        $previous_speed = hypot((float) $previous['wind_x'], (float) $previous['wind_y']);
        if ($previous_speed > 0) {
            $scale = $speed / $previous_speed;

            return [
                'wind_x' => (float) $previous['wind_x'] * $scale,
                'wind_y' => (float) $previous['wind_y'] * $scale,
            ];
        }
    }

    return [
        'wind_x' => $speed,
        'wind_y' => $speed,
    ];
}

function erddap_string_value(mixed $value, string $fallback): string
{
    if ($value === null || $value === '' || erddap_is_missing($value)) {
        return $fallback;
    }

    return (string) $value;
}

function erddap_resolve_float(mixed $value, float $fallback): float
{
    $parsed = erddap_optional_float($value);

    return $parsed ?? $fallback;
}

function erddap_optional_float(mixed $value): ?float
{
    if ($value === null || $value === '') {
        return null;
    }

    if (is_string($value) && erddap_is_missing($value)) {
        return null;
    }

    if (!is_numeric($value)) {
        return null;
    }

    $float = (float) $value;
    if (!is_finite($float)) {
        return null;
    }

    return $float;
}

function erddap_is_missing(mixed $value): bool
{
    if (!is_string($value)) {
        return false;
    }

    $normalized = strtolower(trim($value));

    return in_array($normalized, ['nan', 'null', 'na', 'n/a', '-', '--'], true);
}

function erddap_cache_path(): string
{
    return dirname(__DIR__) . '/cache/erddap-latest.json';
}

function erddap_lock_path(): string
{
    return dirname(__DIR__) . '/cache/erddap.lock';
}

/**
 * @return resource
 */
function erddap_acquire_lock()
{
    $path = erddap_lock_path();
    $directory = dirname($path);
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Unable to create cache directory.');
    }

    $lock = fopen($path, 'c+');
    if ($lock === false) {
        throw new RuntimeException('Unable to open ERDDAP cache lock.');
    }

    if (!flock($lock, LOCK_EX)) {
        fclose($lock);
        throw new RuntimeException('Unable to acquire ERDDAP cache lock.');
    }

    return $lock;
}

/**
 * @param resource $lock
 */
function erddap_release_lock($lock): void
{
    flock($lock, LOCK_UN);
    fclose($lock);
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

    return erddap_normalize_station_data($payload['data']);
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
        'time_iso' => $data['time'],
        'wind' => $data['wind'],
        'wind_dir' => $data['wind_dir'] ?? null,
        'wind_x' => $data['wind_x'],
        'wind_y' => $data['wind_y'],
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
            $cached['error'] = $exception->getMessage();

            return $cached;
        }

        $fallback = erddap_default_station_data();
        $fallback['stale'] = true;
        $fallback['error'] = $exception->getMessage();

        return $fallback;
    }
}

/**
 * @return array<string, mixed>
 */
function erddap_default_station_data(): array
{
    $components = erddap_wind_components(10.0, null, []);

    return [
        'station_name' => "St. John's Buoy",
        'time' => gmdate('Y-m-d\TH:i:s\Z'),
        'time_display' => gmdate('Y-m-d H:i:s'),
        'wind' => 10.0,
        'wind_dir' => null,
        'wind_x' => $components['wind_x'],
        'wind_y' => $components['wind_y'],
        'size' => 250.0,
        'choppiness' => 1.5,
        'longitude' => null,
        'latitude' => null,
    ];
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

    return erddap_normalize_station_data($payload['data']);
}
