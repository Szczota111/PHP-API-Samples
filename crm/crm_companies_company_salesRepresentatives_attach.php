<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $company = $api->first('/api/crm/companies');
    if (!$company || !isset($company['id'])) {
        throw new RuntimeException('Unable to resolve a company for attaching sales representatives.');
    }

    $contacts = $api->getAll('/api/crm/contacts?limit=5');
    $contactIds = array_column($contacts ?? [], 'id');
    if (empty($contactIds)) {
        throw new RuntimeException('No contacts available to attach as sales representatives.');
    }

    $endpoint = '/api/crm/companies/{company}/salesRepresentatives/attach';
    $endpoint = strtr($endpoint, [
        '{company}' => $company['id'],
    ]);

    // Attach contacts as sales representatives
    $payload = [
        'resources' => array_slice($contactIds, 0, 2),
    ];

    $response = $api->post($endpoint, $payload);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
