<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $endpoint = '/api/contractors/project-biddings/{project_bidding}/proposals/{proposal}/dissociate';
    $endpoint = strtr($endpoint, [
        '{project_bidding}' => 'REPLACE_PROJECT_BIDDING',
        '{proposal}' => 'REPLACE_PROPOSAL',
    ]);

    // Dissociate contr project proposal from contr project bidding
    $response = $api->delete($endpoint);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
