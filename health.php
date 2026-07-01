<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$checks = [
    'status' => 'ok',
    'time' => gmdate('c'),
    'cache_writable' => is_dir(__DIR__ . '/cache') && is_writable(__DIR__ . '/cache'),
];

try {
    require_once __DIR__ . '/lib/erddap.php';
    $data = fetch_latest_station_data();
    $checks['station'] = $data['station_name'];
    $checks['observed_at'] = $data['time'];
} catch (Throwable $exception) {
    http_response_code(503);
    $checks['status'] = 'degraded';
    $checks['error'] = $exception->getMessage();
}

echo json_encode($checks, JSON_UNESCAPED_SLASHES);
