<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $trashedTask = $api->first('/api/crm/tasks?only_trashed=true');

    if ($trashedTask && isset($trashedTask['id'])) {
        $taskId = $trashedTask['id'];
    } else {
        $task = $api->create('/api/crm/tasks', [
            'title' => 'API Task ' . date('c'),
            'deadline_date' => date('Y-m-d'),
            'deadline_time' => date('H:i'),
            'priority' => 1,
            'status' => 1,
        ]);

        if (!$task || !isset($task['id'])) {
            throw new RuntimeException('Nie udało się utworzyć zadania do przywrócenia.');
        }

        $taskId = $task['id'];
        $deleteResponse = $api->delete("/api/crm/tasks/{$taskId}");
        if (!in_array($deleteResponse->getStatusCode(), [200, 204], true)) {
            throw new RuntimeException('Nie udało się przenieść zadania do kosza.');
        }
    }

    $endpoint = '/api/crm/tasks/{task}/restore';
    $endpoint = strtr($endpoint, [
        '{task}' => $taskId,
    ]);

    $response = $api->post($endpoint, []);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
