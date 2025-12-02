<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $company = $api->first('/api/crm/companies');
    if (!$company || !isset($company['id'])) {
        throw new RuntimeException('Unable to resolve a company for searching sales representatives.');
    }

    $salesReps = $api->getAll("/api/crm/companies/{$company['id']}/salesRepresentatives?limit=5");
    if (empty($salesReps)) {
        $contacts = $api->getAll('/api/crm/contacts?limit=5');
        $contactIds = array_column($contacts ?? [], 'id');
        if (empty($contactIds)) {
            throw new RuntimeException('Unable to locate contacts to attach as sales representatives.');
        }

        $attachEndpoint = strtr('/api/crm/companies/{company}/salesRepresentatives/attach', [
            '{company}' => $company['id'],
        ]);

        $api->post($attachEndpoint, [
            'resources' => array_slice($contactIds, 0, 2),
        ]);
    }

    $endpoint = '/api/crm/companies/{company}/salesRepresentatives/search';
    $endpoint = strtr($endpoint, [
        '{company}' => $company['id'],
    ]);

    // Basic search payload as fields are restricted by API whitelist
    $payload = [
        'limit' => 5,
    ];

    $response = $api->post($endpoint, $payload);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
