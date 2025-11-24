<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    // 1) Create Task
    $payload = [
        'title' => 'API Task ' . date('c'),
        'deadline_date' => date('Y-m-d'),
        'deadline_time' => date('H:i'),
        'priority' => 1
    ];
    $resp = $api->post('/api/crm/tasks', $payload);
    if (!in_array($resp->getStatusCode(), [200, 201])) $api->throwErr($resp);
    $created = json_decode($resp->getBody()->getContents(), true);
    $taskId = $created['data']['id'];
    echo "Created task ID: {$taskId}\n";

    // 2) Attach employee
    $contact = $api->getFirst('/api/crm/contacts');
    if (!empty($contact)) {
        $contactId = $contact['id'];
        $resp = $api->post("/api/crm/tasks/{$taskId}/employees/attach", [
            'resources' => [$contactId]
        ]);
        if (!in_array($resp->getStatusCode(), [200, 201, 204])) $api->throwErr($resp);
        echo "Attached employee {$contactId}\n";
    } else {
        echo "No contacts available to attach.\n";
    }

    // 3) List employees
    $employees = $api->getAll("/api/crm/tasks/{$taskId}/employees?limit=50");
    $employeeIds = array_map(fn($e) => $e['id'], $employees);
    echo "Employees: " . (empty($employeeIds) ? '-' : implode(', ', $employeeIds)) . "\n";

    // 4) Update task status
    $resp = $api->patch("/api/crm/tasks/{$taskId}", [
        'status' => 2 // in_progress
    ]);
    if (!in_array($resp->getStatusCode(), [200, 204])) $api->throwErr($resp);
    echo "Updated status to 2.\n";

    // 5) Cleanup
    $resp = $api->delete("/api/crm/tasks/{$taskId}");
    if (!in_array($resp->getStatusCode(), [200, 204])) $api->throwErr($resp);
    echo "Deleted task {$taskId}.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
