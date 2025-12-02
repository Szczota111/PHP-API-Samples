<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $meeting = $api->first('/api/crm/meetings');
    if (!$meeting || !isset($meeting['id'])) {
        throw new RuntimeException('Nie znaleziono spotkania do pobrania pracownika.');
    }

    $meetingId = $meeting['id'];

    $employees = $api->getAll("/api/crm/meetings/{$meetingId}/employees?limit=5");
    if (empty($employees)) {
        $contacts = $api->getAll('/api/crm/contacts?limit=5');
        $contactIds = array_column($contacts ?? [], 'id');
        if (empty($contactIds)) {
            throw new RuntimeException('Brak kontaktów do przypięcia jako pracownicy.');
        }

        $api->post("/api/crm/meetings/{$meetingId}/employees/attach", [
            'resources' => array_slice($contactIds, 0, 2),
        ]);

        $employees = $api->getAll("/api/crm/meetings/{$meetingId}/employees?limit=5");
    }

    if (empty($employees)) {
        throw new RuntimeException('Nie udało się uzyskać pracownika powiązanego ze spotkaniem.');
    }

    $employeeId = $employees[0]['id'];

    $endpoint = '/api/crm/meetings/{meeting}/employees/{employee}';
    $endpoint = strtr($endpoint, [
        '{meeting}' => $meetingId,
        '{employee}' => $employeeId,
    ]);

    $response = $api->get($endpoint);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
