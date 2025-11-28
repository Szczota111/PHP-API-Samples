<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $company = $api->first('/api/crm/companies');
    if (!$company || !isset($company['id'])) {
        throw new RuntimeException('Unable to resolve a company for toggling sales representatives.');
    }

    $salesReps = $api->getAll("/api/crm/companies/{$company['id']}/salesRepresentatives?limit=5");
    $salesRepIds = array_column($salesReps ?? [], 'id');

    if (count($salesRepIds) < 2) {
        $contacts = $api->getAll('/api/crm/contacts?limit=5');
        $contactIds = array_column($contacts ?? [], 'id');
        if (count($contactIds) < 2) {
            throw new RuntimeException('Need at least two contacts to demonstrate toggling sales representatives.');
        }

        $attachEndpoint = strtr('/api/crm/companies/{company}/salesRepresentatives/attach', [
            '{company}' => $company['id'],
        ]);

        $api->post($attachEndpoint, [
            'resources' => array_slice($contactIds, 0, 2),
        ]);

        $salesReps = $api->getAll("/api/crm/companies/{$company['id']}/salesRepresentatives?limit=5");
        $salesRepIds = array_column($salesReps ?? [], 'id');
    }

    if (count($salesRepIds) < 2) {
        throw new RuntimeException('Unable to resolve enough sales representatives for toggling.');
    }

    $endpoint = '/api/crm/companies/{company}/salesRepresentatives/toggle';
    $endpoint = strtr($endpoint, [
        '{company}' => $company['id'],
    ]);

    // Toggle two existing sales representatives on/off for the company
    $payload = [
        'resources' => array_slice($salesRepIds, 0, 2),
    ];

    $response = $api->patch($endpoint, $payload);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
