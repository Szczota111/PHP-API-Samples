<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $contactsResponse = $api->get('/api/crm/contacts?limit=5');
    $contactsPayload = json_decode($contactsResponse->getBody()->getContents(), true);
    $contacts = $contactsPayload['data'] ?? [];

    if (count($contacts) < 2) {
        throw new RuntimeException('Need at least two contacts to detach sales representatives.');
    }

    $contact = array_shift($contacts);
    if (!isset($contact['id'])) {
        throw new RuntimeException('Resolved contact is missing an ID.');
    }

    $contactId = $contact['id'];
    $candidateRepIds = array_column($contacts, 'id');

    $salesReps = $api->getAll("/api/crm/contacts/{$contactId}/salesRepresentatives?limit=5");
    $existingRepIds = array_column($salesReps ?? [], 'id');

    if (empty($existingRepIds) && !empty($candidateRepIds)) {
        $api->post("/api/crm/contacts/{$contactId}/salesRepresentatives/attach", [
            'resources' => array_slice($candidateRepIds, 0, 2),
        ]);
        $salesReps = $api->getAll("/api/crm/contacts/{$contactId}/salesRepresentatives?limit=5");
        $existingRepIds = array_column($salesReps ?? [], 'id');
    }

    if (empty($existingRepIds)) {
        throw new RuntimeException('No sales representatives available to detach.');
    }

    $endpoint = '/api/crm/contacts/{contact}/salesRepresentatives/detach';
    $endpoint = strtr($endpoint, [
        '{contact}' => $contactId,
    ]);

    // Detach the specified sales representatives from the contact
    $payload = [
        'resources' => array_slice($existingRepIds, 0, 2),
    ];

    $response = $api->delete($endpoint, $payload);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
