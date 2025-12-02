<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $meeting = $api->first('/api/crm/meetings');
    if (!$meeting || !isset($meeting['id'])) {
        throw new RuntimeException('Nie znaleziono spotkania do prezentacji klientów.');
    }

    $meetingId = $meeting['id'];

    $ensureCustomers = function () use ($api, $meetingId) {
        $customers = $api->getAll("/api/crm/meetings/{$meetingId}/customers?limit=10");
        if (!empty($customers)) {
            return $customers;
        }

        $contacts = $api->getAll('/api/crm/contacts?limit=5');
        $contactIds = array_column($contacts ?? [], 'id');
        if (empty($contactIds)) {
            throw new RuntimeException('Brak dostępnych kontaktów do podpięcia jako klienci.');
        }

        $api->post("/api/crm/meetings/{$meetingId}/customers/attach", [
            'resources' => array_slice($contactIds, 0, 2),
        ]);

        return $api->getAll("/api/crm/meetings/{$meetingId}/customers?limit=10");
    };

    $ensureCustomers();

    $endpoint = '/api/crm/meetings/{meeting}/customers';
    $endpoint = strtr($endpoint, [
        '{meeting}' => $meetingId,
    ]);

    // Pobierz maksymalnie 10 klientów powiązanych ze spotkaniem
    $response = $api->get($endpoint . '?limit=10');

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
