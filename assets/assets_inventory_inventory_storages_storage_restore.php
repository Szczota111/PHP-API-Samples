<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $endpoint = '/api/assets/inventory/{inventory}/storages/{storage}/restore';
    $endpoint = strtr($endpoint, [
        '{inventory}' => 'REPLACE_INVENTORY',
        '{storage}' => 'REPLACE_STORAGE',
    ]);

    // Restore assets inventory storage
    $payload = [
        // TODO: Provide request body
    ];

    $response = $api->post($endpoint, $payload);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
