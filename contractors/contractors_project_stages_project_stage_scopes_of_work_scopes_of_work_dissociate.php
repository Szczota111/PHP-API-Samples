<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $endpoint = '/api/contractors/project-stages/{project_stage}/scopes-of-work/{scopes_of_work}/dissociate';
    $endpoint = strtr($endpoint, [
        '{project_stage}' => 'REPLACE_PROJECT_STAGE',
        '{scopes_of_work}' => 'REPLACE_SCOPES_OF_WORK',
    ]);

    // Dissociate contr project scope of work from contr project stage
    $response = $api->delete($endpoint);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
