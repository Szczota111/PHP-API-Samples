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

    $fetchList = static function (string $endpoint) use ($api): array {
        $response = $api->get($endpoint);
        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException('Nie udało się pobrać danych: ' . $endpoint);
        }
        $payload = json_decode($response->getBody()->getContents(), true);
        return $payload['data'] ?? [];
    };

    $currentCustomers = $fetchList("/api/crm/tasks/{$taskId}/customers?limit=20");
    $currentIds = array_column($currentCustomers, 'id');

    $contacts = $fetchList('/api/crm/contacts?limit=20');
    $contactIds = array_column($contacts, 'id');
    if (empty($contactIds)) {
        throw new RuntimeException('Brak kontaktów do podpięcia.');
    }

    $candidates = array_values(array_diff($contactIds, $currentIds));
    if (empty($candidates)) {
        $candidates = $contactIds;
    }

    $resources = array_slice($candidates, 0, 2);
    if (empty($resources)) {
        throw new RuntimeException('Nie udało się przygotować listy kontaktów do podpięcia.');
    }

    $endpoint = '/api/crm/tasks/{task}/customers/attach';
    $endpoint = strtr($endpoint, [
        '{task}' => $taskId,
    ]);

    $payload = [
        'resources' => $resources,
    ];

    $response = $api->post($endpoint, $payload);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
