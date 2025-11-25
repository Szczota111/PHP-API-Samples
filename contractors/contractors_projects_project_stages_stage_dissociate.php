<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $endpoint = '/api/contractors/projects/{project}/stages/{stage}/dissociate';
    $endpoint = strtr($endpoint, [
        '{project}' => 'REPLACE_PROJECT',
        '{stage}' => 'REPLACE_STAGE',
    ]);

    // Dissociate contr project stage from contr project
    // Query params: include
    $response = $api->delete($endpoint);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
