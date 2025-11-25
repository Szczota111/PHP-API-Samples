<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $endpoint = '/api/contractors/subcontractors-agreements/{subcontractors_agreement}/paid/associate';
    $endpoint = strtr($endpoint, [
        '{subcontractors_agreement}' => 'REPLACE_SUBCONTRACTORS_AGREEMENT',
    ]);

    // Associate contr subcontractors paid with contr subcontractors agreement
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
