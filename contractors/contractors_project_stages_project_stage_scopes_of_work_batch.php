<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $endpoint = '/api/contractors/project-stages/{project_stage}/scopes-of-work/batch';
    $endpoint = strtr($endpoint, [
        '{project_stage}' => 'REPLACE_PROJECT_STAGE',
    ]);

    // Create a batch of contr project scope of works
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
