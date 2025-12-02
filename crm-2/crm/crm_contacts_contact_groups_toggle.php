<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $contact = $api->first('/api/crm/contacts');
    if (!$contact || !isset($contact['id'])) {
        throw new RuntimeException('Unable to resolve a contact for toggling groups.');
    }

    $groups = $api->getAll('/api/crm/contact-groups?limit=5');
    $groupIds = array_column($groups ?? [], 'id');
    if (empty($groupIds)) {
        throw new RuntimeException('No contact groups available to toggle.');
    }

    $endpoint = '/api/crm/contacts/{contact}/groups/toggle';
    $endpoint = strtr($endpoint, [
        '{contact}' => $contact['id'],
    ]);

    // Toggle contact groups: attaches missing ones and detaches existing ones from the provided list
    $payload = [
        'resources' => array_slice($groupIds, 0, 2),
    ];

    $response = $api->patch($endpoint, $payload);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
