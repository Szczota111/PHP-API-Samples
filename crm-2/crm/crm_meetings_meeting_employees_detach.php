<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $meeting = $api->first('/api/crm/meetings');
    if (!$meeting || !isset($meeting['id'])) {
        throw new RuntimeException('Nie znaleziono spotkania do odpinania pracowników.');
    }

    $meetingId = $meeting['id'];

    $employees = $api->getAll("/api/crm/meetings/{$meetingId}/employees?limit=10");
    if (empty($employees)) {
        $contacts = $api->getAll('/api/crm/contacts?limit=5');
        $contactIds = array_column($contacts ?? [], 'id');
        if (empty($contactIds)) {
            throw new RuntimeException('Brak kontaktów do przygotowania operacji detach.');
        }

        $api->post("/api/crm/meetings/{$meetingId}/employees/attach", [
            'resources' => array_slice($contactIds, 0, 2),
        ]);

        $employees = $api->getAll("/api/crm/meetings/{$meetingId}/employees?limit=10");
    }

    $employeeIds = array_column($employees ?? [], 'id');
    if (empty($employeeIds)) {
        throw new RuntimeException('Nie udało się znaleźć pracowników do odpięcia.');
    }

    $endpoint = '/api/crm/meetings/{meeting}/employees/detach';
    $endpoint = strtr($endpoint, [
        '{meeting}' => $meetingId,
    ]);

    $payload = [
        'resources' => array_slice($employeeIds, 0, 2),
    ];

    $response = $api->delete($endpoint, $payload);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
