<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $endpoint = '/api/countries/{country}/states/search';
    $endpoint = strtr($endpoint, [
        '{country}' => '1',
    ]);

    // Search for states named "zabul"
    $payload = [
        'filters' => [
            [
                'type' => 'and',
                'field' => 'name',
                'operator' => 'like',
                'value' => '%zabul%',
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
