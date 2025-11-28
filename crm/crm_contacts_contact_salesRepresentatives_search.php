<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $contactsResponse = $api->get('/api/crm/contacts?limit=5');
    $contactsPayload = json_decode($contactsResponse->getBody()->getContents(), true);
    $contacts = $contactsPayload['data'] ?? [];

    if (count($contacts) < 2) {
        throw new RuntimeException('Need at least two contacts to search attached sales representatives.');
    }

    $contact = array_shift($contacts);
    if (!isset($contact['id'])) {
        throw new RuntimeException('Resolved contact is missing an ID.');
    }

    $contactId = $contact['id'];
    $candidateRepIds = array_column($contacts, 'id');

    $salesReps = $api->getAll("/api/crm/contacts/{$contactId}/salesRepresentatives?limit=5");
    $existingRepIds = array_column($salesReps ?? [], 'id');

    $missingReps = array_values(array_diff($candidateRepIds, $existingRepIds));
    if (empty($salesReps) && !empty($missingReps)) {
        $api->post("/api/crm/contacts/{$contactId}/salesRepresentatives/attach", [
            'resources' => array_slice($missingReps, 0, 2),
        ]);
        $salesReps = $api->getAll("/api/crm/contacts/{$contactId}/salesRepresentatives?limit=5");
        $existingRepIds = array_column($salesReps ?? [], 'id');
    }

    if (empty($existingRepIds)) {
        throw new RuntimeException('Unable to attach any sales representatives to search.');
    }

    $endpoint = '/api/crm/contacts/{contact}/salesRepresentatives/search';
    $endpoint = strtr($endpoint, [
        '{contact}' => $contactId,
    ]);

    // Search for sales representatives attached to the contact
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
