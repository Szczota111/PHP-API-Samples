<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $endpoint = '/api/contractors/invoices/{invoice}/scopes-progress/{scopes_progress}/dissociate';
    $endpoint = strtr($endpoint, [
        '{invoice}' => 'REPLACE_INVOICE',
        '{scopes_progress}' => 'REPLACE_SCOPES_PROGRESS',
    ]);

    // Dissociate contr invoice scopes progress from contr invoice
    // Query params: include
    $response = $api->delete($endpoint);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
