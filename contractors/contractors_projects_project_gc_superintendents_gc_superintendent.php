<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $endpoint = '/api/contractors/projects/{project}/gc-superintendents/{gc_superintendent}';
    $endpoint = strtr($endpoint, [
        '{project}' => 'REPLACE_PROJECT',
        '{gc_superintendent}' => 'REPLACE_GC_SUPERINTENDENT',
    ]);

    // Get contact
    // Query params: with_trashed, only_trashed
    $response = $api->get($endpoint);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
