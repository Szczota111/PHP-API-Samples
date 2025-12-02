<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $contact = $api->first('/api/crm/contacts');
    if (!$contact || !isset($contact['id'])) {
        throw new RuntimeException('Unable to resolve a contact to fetch details.');
    }

    $endpoint = '/api/crm/contacts/{contact}';
    $endpoint = strtr($endpoint, [
        '{contact}' => $contact['id'],
    ]);

    // Get contact details (supports with_trashed, only_trashed)
    $response = $api->get($endpoint);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
