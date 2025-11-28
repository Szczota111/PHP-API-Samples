<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    // 1) Create Meeting
    $payload = [
        'title' => 'API Meeting ' . date('c'),
        'start' => date('Y-m-d\TH:i:s'),
        'end' => date('Y-m-d\TH:i:s', strtotime('+1 hour')),
        'priority' => 1
    ];
    $resp = $api->post('/api/crm/meetings', $payload);
    if (!in_array($resp->getStatusCode(), [200, 201])) $api->throwErr($resp);
    $created = json_decode($resp->getBody()->getContents(), true);
    $meetingId = $created['data']['id'];
    echo "Created meeting ID: {$meetingId}\n";

    // 2) Attach employee
    $contact = $api->getFirst('/api/crm/contacts');
    if (!empty($contact)) {
        $contactId = $contact['id'];
        $resp = $api->post("/api/crm/meetings/{$meetingId}/employees/attach", [
            'resources' => [$contactId]
        ]);
        if (!in_array($resp->getStatusCode(), [200, 201, 204])) $api->throwErr($resp);
        echo "Attached employee {$contactId}\n";
    } else {
        echo "No contacts available to attach.\n";
    }

    // 3) List employees
    $employees = $api->getAll("/api/crm/meetings/{$meetingId}/employees?limit=50");
    $employeeIds = array_map(fn($e) => $e['id'], $employees);
    echo "Employees: " . (empty($employeeIds) ? '-' : implode(', ', $employeeIds)) . "\n";

    // 4) Update meeting title
    $resp = $api->patch("/api/crm/meetings/{$meetingId}", [
        'title' => 'API Meeting Updated ' . date('c')
    ]);
    if (!in_array($resp->getStatusCode(), [200, 204])) $api->throwErr($resp);
    echo "Updated title.\n";

    // 5) Cleanup
    $resp = $api->delete("/api/crm/meetings/{$meetingId}");
    if (!in_array($resp->getStatusCode(), [200, 204])) $api->throwErr($resp);
    echo "Deleted meeting {$meetingId}.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
