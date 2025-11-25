<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $endpoint = '/api/assets/inventory-transfers/{inventory_transfer}';
    $endpoint = strtr($endpoint, [
        '{inventory_transfer}' => 'REPLACE_INVENTORY_TRANSFER',
    ]);

    // Get assets inventory transfer
    // Query params: with_trashed, only_trashed
    $response = $api->get($endpoint);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
