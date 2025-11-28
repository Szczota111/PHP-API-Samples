<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $meetingLocation = $api->first('/api/crm/meeting-locations');
    if (!$meetingLocation || !isset($meetingLocation['id'])) {
        $meetingLocation = $api->create('/api/crm/meeting-locations', [
            'name' => 'Meeting Location ' . date('YmdHis'),
            'active' => 1,
        ]);
    }

    if (!$meetingLocation || !isset($meetingLocation['id'])) {
        throw new RuntimeException('Nie udało się uzyskać ID lokalizacji spotkania.');
    }

    $endpoint = '/api/crm/meeting-locations/{meeting_location}';
    $endpoint = strtr($endpoint, [
        '{meeting_location}' => $meetingLocation['id'],
    ]);

    // Pobierz szczegóły lokalizacji (obsługuje with_trashed, only_trashed)
    $response = $api->get($endpoint);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
