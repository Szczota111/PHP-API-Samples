<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $meeting = $api->first('/api/crm/meetings');
    if (!$meeting || !isset($meeting['id'])) {
        throw new RuntimeException('Nie znaleziono spotkania do podpinania klientów.');
    }

    $meetingId = $meeting['id'];

    $currentCustomers = $api->getAll("/api/crm/meetings/{$meetingId}/customers?limit=20");
    $currentIds = array_column($currentCustomers ?? [], 'id');

    $contacts = $api->getAll('/api/crm/contacts?limit=10');
    $contactIds = array_column($contacts ?? [], 'id');
    if (empty($contactIds)) {
        throw new RuntimeException('Brak kontaktów dostępnych do podpięcia.');
    }

    $candidates = array_values(array_diff($contactIds, $currentIds));
    if (empty($candidates)) {
        throw new RuntimeException('Wszyscy dostępni kontakci są już przypięci do tego spotkania.');
    }

    $endpoint = '/api/crm/meetings/{meeting}/customers/attach';
    $endpoint = strtr($endpoint, [
        '{meeting}' => $meetingId,
    ]);

    $payload = [
        'resources' => array_slice($candidates, 0, 2),
    ];

    $response = $api->post($endpoint, $payload);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
