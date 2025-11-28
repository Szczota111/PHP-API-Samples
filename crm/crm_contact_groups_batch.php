<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $endpoint = '/api/crm/contact-groups/batch';
    $uniq = date('YmdHis');

    // Create a pair of demo contact groups in one request
    $payload = [
        'resources' => [
            [
                'name' => 'Sample Contact Group ' . $uniq,
                'slug' => 'sample-contact-group-' . $uniq,
                'filter' => 0,
            ],
            [
                'name' => 'Sample Contact Group ' . $uniq . '-b',
                'slug' => 'sample-contact-group-' . $uniq . '-b',
                'filter' => 0,
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
