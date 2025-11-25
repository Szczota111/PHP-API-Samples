<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $endpoint = '/api/assets/inventory-stocktakings/{inventory_stocktaking}/rows/{row}/dissociate';
    $endpoint = strtr($endpoint, [
        '{inventory_stocktaking}' => 'REPLACE_INVENTORY_STOCKTAKING',
        '{row}' => 'REPLACE_ROW',
    ]);

    // Dissociate assets inventory stocktaking row from assets inventory stocktaking
    $response = $api->delete($endpoint);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
