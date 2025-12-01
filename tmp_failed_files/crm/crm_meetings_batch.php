<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $endpoint = '/api/crm/meetings/batch';
    // Create a batch of meetings
    $uniq = date('YmdHis');
    $baseTime = time();
    $payload = [
        'resources' => [
            [
                'title' => 'API Batch Meeting A ' . $uniq,
                'start' => date('Y-m-d\TH:i:s', $baseTime),
                'end' => date('Y-m-d\TH:i:s', $baseTime + 3600),
                'priority' => 1,
            ],
            [
                'title' => 'API Batch Meeting B ' . $uniq,
                'start' => date('Y-m-d\TH:i:s', $baseTime + 7200),
                'end' => date('Y-m-d\TH:i:s', $baseTime + 10800),
                'priority' => 2,
            ],
        ],
    ];

    $response = $api->post($endpoint, $payload);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
