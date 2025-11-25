<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $endpoint = '/api/assets/inventory-stocktakings/{inventory_stocktaking}/rows/associate';
    $endpoint = strtr($endpoint, [
        '{inventory_stocktaking}' => 'REPLACE_INVENTORY_STOCKTAKING',
    ]);

    // Associate assets inventory stocktaking row with assets inventory stocktaking
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
