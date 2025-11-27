<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $contact = $api->first('/api/crm/contacts');
    if (!$contact || !isset($contact['id'])) {
        throw new RuntimeException('Unable to resolve a contact for detaching groups.');
    }

    $attachedGroups = $api->getAll("/api/crm/contacts/{$contact['id']}/groups?limit=10");
    $groupIds = array_column($attachedGroups ?? [], 'id');

    if (empty($groupIds)) {
        $availableGroups = $api->getAll('/api/crm/contact-groups?limit=5');
        $availableIds = array_column($availableGroups ?? [], 'id');
        if (empty($availableIds)) {
            throw new RuntimeException('No contact groups available to attach for detach demonstration.');
        }

        $api->post("/api/crm/contacts/{$contact['id']}/groups/attach", [
            'resources' => array_slice($availableIds, 0, 2),
        ]);

        $attachedGroups = $api->getAll("/api/crm/contacts/{$contact['id']}/groups?limit=10");
        $groupIds = array_column($attachedGroups ?? [], 'id');
    }

    if (empty($groupIds)) {
        throw new RuntimeException('Unable to find contact groups to detach.');
    }

    $endpoint = '/api/crm/contacts/{contact}/groups/detach';
    $endpoint = strtr($endpoint, [
        '{contact}' => $contact['id'],
    ]);

    // Detach the selected groups
    $payload = [
        'resources' => array_slice($groupIds, 0, 2),
    ];

    $response = $api->delete($endpoint, $payload);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
