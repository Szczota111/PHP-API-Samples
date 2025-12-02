<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $endpoint = '/api/crm/meeting-locations/batch';
    // Create a batch of meeting locations using different "start" date formats
    // Formats covered: 2020-05-20 13:00:00, 2020-05-20 13:00, 2020-05-20 05:00 AM, 2020-05-20T11:00+1:00
    $baseName = 'Meeting Location ' . date('YmdHis');
    $payload = [
        'resources' => [
            [
                'name' => $baseName . ' A',
                'active' => 1,
                'start' => '2020-05-20 13:00:00',
            ],
            [
                'name' => $baseName . ' B',
                'active' => 1,
                'start' => '2020-05-20 13:00',
            ],
            [
                'name' => $baseName . ' C',
                'active' => 1,
                'start' => '2020-05-20 05:00 AM',
            ],
            [
                'name' => $baseName . ' D',
                'active' => 1,
                'start' => '2020-05-20T11:00+1:00',
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
