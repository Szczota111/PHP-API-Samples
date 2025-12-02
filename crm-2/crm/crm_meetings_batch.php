<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $endpoint = '/api/crm/meetings/batch';
    // Create a batch of meetings using different "start" date formats
    $location = $api->getFirst('/api/crm/meeting-locations');
    $locationId = $location['id'] ?? null;

    $timeRanges = [
        ['start' => '2020-05-20 13:00:00', 'end' => '2020-05-20 14:00:00'],
        ['start' => '2020-05-20 13:00', 'end' => '2020-05-20 14:00'],
        ['start' => '2020-05-20 05:00 AM', 'end' => '2020-05-20 06:00 AM'],
        ['start' => '2020-05-20T11:00+1:00', 'end' => '2020-05-20T12:00+1:00'],
    ];
    $baseTitle = 'Batch Meeting ' . date('YmdHis');

    $resources = [];
    foreach ($timeRanges as $index => $range) {
        $startDate = date_create($range['start']);
        $endDate = date_create($range['end']);

        if (!$startDate || !$endDate) {
            echo 'Skipping invalid range: ' . json_encode($range) . PHP_EOL;
            continue;
        }

        $resources[] = [
            'title' => $baseTitle . ' #' . ($index + 1),
            'start' => $startDate->format('Y-m-d H:i:s'),
            'end' => $endDate->format('Y-m-d H:i:s'),
            'priority' => 1,
            'description' => 'Original start format: ' . $range['start'],
            'permission' => 1,
            'location_id' => $locationId,
        ];
    }

    $payload = [
        'resources' => $resources,
    ];

    $response = $api->post($endpoint, $payload);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
