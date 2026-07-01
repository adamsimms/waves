<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/erddap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=10');

try {
    $data = fetch_latest_station_data();
    echo station_data_to_json($data);
} catch (Throwable $exception) {
    http_response_code(503);
    echo json_encode([
        'error' => $exception->getMessage(),
    ], JSON_UNESCAPED_SLASHES);
}
