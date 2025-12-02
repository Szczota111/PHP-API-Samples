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

    $customers = $fetchList("/api/crm/tasks/{$taskId}/customers?limit=5");
    if (empty($customers)) {
        $contacts = $fetchList('/api/crm/contacts?limit=5');
        $contactIds = array_column($contacts, 'id');
        if (empty($contactIds)) {
            throw new RuntimeException('Brak kontaktów do przygotowania detach.');
        }

        $api->post("/api/crm/tasks/{$taskId}/customers/attach", [
            'resources' => array_slice($contactIds, 0, 2),
        ]);

        $customers = $fetchList("/api/crm/tasks/{$taskId}/customers?limit=5");
    }

    $customerIds = array_column($customers, 'id');
    if (empty($customerIds)) {
        throw new RuntimeException('Nie znaleziono klientów do odpięcia.');
    }

    $endpoint = '/api/crm/tasks/{task}/customers/detach';
    $endpoint = strtr($endpoint, [
        '{task}' => $taskId,
    ]);

    $payload = [
        'resources' => array_slice($customerIds, 0, 2),
    ];

    $response = $api->delete($endpoint, $payload);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
