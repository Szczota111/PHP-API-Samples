<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $contactsResponse = $api->get('/api/crm/contacts?limit=5');
    $contactsPayload = json_decode($contactsResponse->getBody()->getContents(), true);
    $contacts = $contactsPayload['data'] ?? [];

    if (count($contacts) < 2) {
        throw new RuntimeException('Need at least two contacts to demonstrate syncing sales representatives.');
    }

    $contact = array_shift($contacts);
    if (!isset($contact['id'])) {
        throw new RuntimeException('Resolved contact is missing an ID.');
    }

    $contactId = $contact['id'];
    $candidateRepIds = array_column($contacts, 'id');
    if (empty($candidateRepIds)) {
        throw new RuntimeException('No additional contacts available for syncing.');
    }

    $endpoint = '/api/crm/contacts/{contact}/salesRepresentatives/sync';
    $endpoint = strtr($endpoint, [
        '{contact}' => $contactId,
    ]);

    // Replace current sales representatives with the provided list
    $payload = [
        'resources' => array_slice($candidateRepIds, 0, 2),
    ];

    $response = $api->patch($endpoint, $payload);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
