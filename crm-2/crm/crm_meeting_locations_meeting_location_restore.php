<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $endpoint = '/api/crm/meeting-locations/{meeting_location}/restore';
    $endpoint = strtr($endpoint, [
        '{meeting_location}' => '4',
    ]);

    // Showcase different "start" date formats when restoring a location
    $startFormats = [
        '2020-05-20 13:00:00',
        '2020-05-20 13:00',
        '2020-05-20 05:00 AM',
        '2020-05-20T11:00+1:00',
    ];

    foreach ($startFormats as $format) {
        $payload = [
            'start' => $format,
        ];

        $response = $api->post($endpoint, $payload);

        $body = json_decode($response->getBody()->getContents(), true);
        echo 'Start format: ' . $format . PHP_EOL;
        echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
        echo str_repeat('-', 40) . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
