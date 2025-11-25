<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $endpoint = '/api/assets/inventory-transfers/{inventory_transfer}/rows/batch';
    $endpoint = strtr($endpoint, [
        '{inventory_transfer}' => 'REPLACE_INVENTORY_TRANSFER',
    ]);

    // Create a batch of assets inventory transfer rows
    $payload = [
        // TODO: Provide request body (object)
    ];

    $response = $api->post($endpoint, $payload);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
