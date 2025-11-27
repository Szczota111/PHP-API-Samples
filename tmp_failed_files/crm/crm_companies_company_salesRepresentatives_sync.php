<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $company = $api->first('/api/crm/companies');
    if (!$company || !isset($company['id'])) {
        throw new RuntimeException('Unable to resolve a company for syncing sales representatives.');
    }

    $contacts = $api->getAll('/api/crm/contacts?limit=5');
    $contactIds = array_column($contacts ?? [], 'id');
    if (count($contactIds) < 2) {
        throw new RuntimeException('Need at least two contacts to sync as sales representatives.');
    }

    $endpoint = '/api/crm/companies/{company}/salesRepresentatives/sync';
    $endpoint = strtr($endpoint, [
        '{company}' => $company['id'],
    ]);

    // Sync company sales representatives
    $payload = [
        'resources' => array_slice($contactIds, 0, 2),
    ];

    $response = $api->patch($endpoint, $payload);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
