<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    // 1) Create PhoneCall
    $payload = [
        'subject' => 'API Phone Call ' . date('c'),
        'date' => date('Y-m-d'),
        'time' => date('H:i'),
        'contact_company_switch' => 3, // 1-contact, 2-company, 3-other
        'other_customer_name' => 'API Test Customer',
        'priority' => 1
    ];
    $resp = $api->post('/api/crm/phonecalls', $payload);
    if (!in_array($resp->getStatusCode(), [200, 201])) $api->throwErr($resp);
    $created = json_decode($resp->getBody()->getContents(), true);
    $phoneCallId = $created['data']['id'];
    echo "Created phonecall ID: {$phoneCallId}\n";

    // 2) Attach an employee
    $contact = $api->getFirst('/api/crm/contacts');
    if (!empty($contact)) {
        $contactId = $contact['id'];
        $resp = $api->post("/api/crm/phonecalls/{$phoneCallId}/employees/attach", [
            'resources' => [$contactId]
        ]);
        if (!in_array($resp->getStatusCode(), [200, 201, 204])) $api->throwErr($resp);
        echo "Attached employee {$contactId}\n";
    } else {
        echo "No contacts available to attach.\n";
    }

    // 3) List employees of the phonecall
    $employees = $api->getAll("/api/crm/phonecalls/{$phoneCallId}/employees?limit=50");
    $employeeIds = array_map(fn($e) => $e['id'], $employees);
    echo "Employees: " . (empty($employeeIds) ? '-' : implode(', ', $employeeIds)) . "\n";

    // 4) Update phonecall status
    $resp = $api->patch("/api/crm/phonecalls/{$phoneCallId}", [
        'status' => 2 // in_progress
    ]);
    if (!in_array($resp->getStatusCode(), [200, 204])) $api->throwErr($resp);
    echo "Updated status to 2.\n";

    // 5) Cleanup - delete (soft delete)
    $resp = $api->delete("/api/crm/phonecalls/{$phoneCallId}");
    if (!in_array($resp->getStatusCode(), [200, 204])) $api->throwErr($resp);
    echo "Deleted phonecall {$phoneCallId}.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
