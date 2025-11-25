<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $endpoint = '/api/contractors/project-proposals/{project_proposal}/scopes-of-work/search';
    $endpoint = strtr($endpoint, [
        '{project_proposal}' => 'REPLACE_PROJECT_PROPOSAL',
    ]);

    // Search for contr project proposal scope of works
    // Query params: with_trashed, only_trashed, include
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
