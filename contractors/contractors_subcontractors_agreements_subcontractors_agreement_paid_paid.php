<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $endpoint = '/api/contractors/subcontractors-agreements/{subcontractors_agreement}/paid/{paid}';
    $endpoint = strtr($endpoint, [
        '{subcontractors_agreement}' => 'REPLACE_SUBCONTRACTORS_AGREEMENT',
        '{paid}' => 'REPLACE_PAID',
    ]);

    // Get contr subcontractors paid
    // Query params: with_trashed, only_trashed
    $response = $api->get($endpoint);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
