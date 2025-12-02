<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $task = $api->first('/api/crm/tasks');
    if (!$task || !isset($task['id'])) {
        $task = $api->create('/api/crm/tasks', [
            'title' => 'API Task ' . date('c'),
            'deadline_date' => date('Y-m-d'),
            'deadline_time' => date('H:i'),
            'priority' => 1,
            'status' => 1,
        ]);
    }

    if (!$task || !isset($task['id'])) {
        throw new RuntimeException('Nie udało się przygotować zadania.');
    }

    $taskId = $task['id'];

    $contacts = $api->get('/api/crm/contacts?limit=5');
    if ($contacts->getStatusCode() !== 200) {
        throw new RuntimeException('Nie udało się pobrać kontaktów.');
    }
    $contactsPayload = json_decode($contacts->getBody()->getContents(), true);
    $contactIds = array_column($contactsPayload['data'] ?? [], 'id');

    if (empty($contactIds)) {
        throw new RuntimeException('Brak dostępnych kontaktów.');
    }

    $endpoint = '/api/crm/tasks/{task}/employees/sync';
    $endpoint = strtr($endpoint, [
        '{task}' => $taskId,
    ]);

    $payload = [
        'resources' => array_slice($contactIds, 0, 3),
    ];

    $response = $api->patch($endpoint, $payload);

    $body = json_decode($response->getBody()->getContents(), true) ?: [
        'message' => 'Brak treści w odpowiedzi API.',
        'status' => $response->getStatusCode(),
    ];
    print_r($body);

} catch (Exception $exception) {
    echo $exception->getMessage();
}
