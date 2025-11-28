<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $contact = $api->first('/api/crm/contacts');
    if (!$contact || !isset($contact['id'])) {
        throw new RuntimeException('Unable to resolve a contact for attaching groups.');
    }

    $groups = $api->getAll('/api/crm/contact-groups?limit=5');
    $groupIds = array_column($groups ?? [], 'id');
    if (empty($groupIds)) {
        throw new RuntimeException('No contact groups available for attachment.');
    }

    $endpoint = '/api/crm/contacts/{contact}/groups/attach';
    $endpoint = strtr($endpoint, [
        '{contact}' => $contact['id'],
    ]);

    // Attach the first available groups to the contact
    $payload = [
        'resources' => array_slice($groupIds, 0, 2),
    ];

    $response = $api->post($endpoint, $payload);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
