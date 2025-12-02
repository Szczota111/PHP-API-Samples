<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $trashed = $api->getAll('/api/crm/meetings?only_trashed=1&limit=1');
    $meetingId = $trashed[0]['id'] ?? null;

    if (!$meetingId) {
        $creationPayload = [
            'title' => 'RESTORE sample ' . date('Y-m-d H:i:s'),
            'start' => date('Y-m-d H:i:s'),
            'end' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            'priority' => 1,
        ];

        $created = $api->post('/api/crm/meetings', $creationPayload);
        $createdBody = json_decode($created->getBody()->getContents(), true);
        $meetingId = $createdBody['data']['id'] ?? null;

        if (!$meetingId) {
            throw new RuntimeException('Nie udało się utworzyć spotkania testowego.');
        }

        $api->delete("/api/crm/meetings/{$meetingId}?force=0");
    }

    $endpoint = '/api/crm/meetings/{meeting}/restore';
    $endpoint = strtr($endpoint, [
        '{meeting}' => $meetingId,
    ]);

    $response = $api->post($endpoint, []);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
