<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $contact = $api->first('/api/crm/contacts');
    if (!$contact || !isset($contact['id'])) {
        throw new RuntimeException('Unable to resolve a contact for group search.');
    }

    $attachedGroups = $api->getAll("/api/crm/contacts/{$contact['id']}/groups?limit=5");
    if (empty($attachedGroups)) {
        $availableGroups = $api->getAll('/api/crm/contact-groups?limit=5');
        $groupIds = array_column($availableGroups ?? [], 'id');
        if (empty($groupIds)) {
            throw new RuntimeException('No contact groups available to attach.');
        }

        $api->post("/api/crm/contacts/{$contact['id']}/groups/attach", [
            'resources' => array_slice($groupIds, 0, 2),
        ]);

        $attachedGroups = $api->getAll("/api/crm/contacts/{$contact['id']}/groups?limit=5");
    }

    $endpoint = '/api/crm/contacts/{contact}/groups/search';
    $endpoint = strtr($endpoint, [
        '{contact}' => $contact['id'],
    ]);

    // Basic search payload (no filters to avoid whitelist issues)
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
