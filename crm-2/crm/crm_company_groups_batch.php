<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $endpoint = '/api/crm/company-groups/batch';
    $uniq = date('YmdHis');

    // Create a batch with two demo company groups
    $payload = [
        'resources' => [
            [
                'name' => 'Sample Company Group ' . $uniq,
                'slug' => 'sample-company-group-' . $uniq,
            ],
            [
                'name' => 'Sample Company Group ' . $uniq . '-b',
                'slug' => 'sample-company-group-' . $uniq . '-b',
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
