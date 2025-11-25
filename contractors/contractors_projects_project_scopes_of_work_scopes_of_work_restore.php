<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $endpoint = '/api/contractors/projects/{project}/scopes-of-work/{scopes_of_work}/restore';
    $endpoint = strtr($endpoint, [
        '{project}' => 'REPLACE_PROJECT',
        '{scopes_of_work}' => 'REPLACE_SCOPES_OF_WORK',
    ]);

    // Restore contr project scope of work
    // Query params: include
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
