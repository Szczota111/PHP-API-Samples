<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $endpoint = '/api/contractors/project-proposals/{project_proposal}/scopes-of-work/{scopes_of_work}/dissociate';
    $endpoint = strtr($endpoint, [
        '{project_proposal}' => 'REPLACE_PROJECT_PROPOSAL',
        '{scopes_of_work}' => 'REPLACE_SCOPES_OF_WORK',
    ]);

    // Dissociate contr project proposal scope of work from contr project proposal
    // Query params: include
    $response = $api->delete($endpoint);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
