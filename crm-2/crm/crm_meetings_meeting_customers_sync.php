<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $meeting = $api->first('/api/crm/meetings');
    if (!$meeting || !isset($meeting['id'])) {
        throw new RuntimeException('Nie znaleziono spotkania do synchronizacji klientów.');
    }

    $meetingId = $meeting['id'];

    $contacts = $api->getAll('/api/crm/contacts?limit=5');
    $contactIds = array_column($contacts ?? [], 'id');
    if (empty($contactIds)) {
        throw new RuntimeException('Brak kontaktów do synchronizacji.');
    }

    $endpoint = '/api/crm/meetings/{meeting}/customers/sync';
    $endpoint = strtr($endpoint, [
        '{meeting}' => $meetingId,
    ]);

    $payload = [
        'resources' => array_slice($contactIds, 0, 3),
    ];

    $response = $api->patch($endpoint, $payload);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
