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

    $endpoint = '/api/crm/tasks/{task}';
    $endpoint = strtr($endpoint, [
        '{task}' => $task['id'],
    ]);

    $response = $api->get($endpoint);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
