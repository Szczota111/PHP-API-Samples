<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $contactsResponse = $api->get('/api/crm/contacts?limit=5');
    $contactsPayload = json_decode($contactsResponse->getBody()->getContents(), true);
    $contacts = $contactsPayload['data'] ?? [];

    if (count($contacts) < 2) {
        throw new RuntimeException('Need at least two contacts to fetch a specific sales representative relation.');
    }

    $contact = array_shift($contacts);
    if (!isset($contact['id'])) {
        throw new RuntimeException('Resolved contact is missing an ID.');
    }

    $contactId = $contact['id'];
    $candidateRepIds = array_column($contacts, 'id');
    if (empty($candidateRepIds)) {
        throw new RuntimeException('No extra contacts available to act as sales representatives.');
    }

    $salesReps = $api->getAll("/api/crm/contacts/{$contactId}/salesRepresentatives?limit=5");
    if (empty($salesReps)) {
        $api->post("/api/crm/contacts/{$contactId}/salesRepresentatives/attach", [
            'resources' => array_slice($candidateRepIds, 0, 2),
        ]);
        $salesReps = $api->getAll("/api/crm/contacts/{$contactId}/salesRepresentatives?limit=5");
    }

    if (empty($salesReps)) {
        throw new RuntimeException('Unable to attach any sales representatives for detail lookup.');
    }

    $salesRepId = $salesReps[0]['id'];

    $endpoint = '/api/crm/contacts/{contact}/salesRepresentatives/{salesRepresentative}';
    $endpoint = strtr($endpoint, [
        '{contact}' => $contactId,
        '{salesRepresentative}' => $salesRepId,
    ]);

    // Get specific sales representative relation (supports with_trashed/only_trashed query params)
    $response = $api->get($endpoint);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
