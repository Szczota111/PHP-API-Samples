<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $contactsResponse = $api->get('/api/crm/contacts?limit=5');
    $contactsPayload = json_decode($contactsResponse->getBody()->getContents(), true);
    $contacts = $contactsPayload['data'] ?? [];
    if (count($contacts) < 2) {
        throw new RuntimeException('Need at least two contacts to demonstrate attaching sales representatives.');
    }

    $contact = array_shift($contacts);
    if (!isset($contact['id'])) {
        throw new RuntimeException('Resolved contact is missing an ID.');
    }

    $salesRepIds = array_column($contacts, 'id');
    $salesRepIds = array_values(array_diff($salesRepIds, [$contact['id']]));
    if (empty($salesRepIds)) {
        throw new RuntimeException('No additional contacts available to attach as sales representatives.');
    }

    $endpoint = '/api/crm/contacts/{contact}/salesRepresentatives/attach';
    $endpoint = strtr($endpoint, [
        '{contact}' => $contact['id'],
    ]);

    // Attach other contacts as sales representatives for the selected contact
    $payload = [
        'resources' => array_slice($salesRepIds, 0, 2),
    ];

    $response = $api->post($endpoint, $payload);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
