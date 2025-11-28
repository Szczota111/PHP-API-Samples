<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $company = $api->first('/api/crm/companies');
    $companyId = $company['id'] ?? null;

    $uniq = date('YmdHis');
    $newContact = $api->create('/api/crm/contacts', [
        'last_name' => 'Restore-' . $uniq,
        'first_name' => 'Sample',
        'email' => 'restore-' . $uniq . '@example.com',
        'company_id' => $companyId,
    ]);

    if (!$newContact || !isset($newContact['id'])) {
        throw new RuntimeException('Failed to create a contact to restore.');
    }

    $contactId = $newContact['id'];

    // Soft delete the contact so it can be restored
    $api->delete("/api/crm/contacts/{$contactId}?force=0");

    $endpoint = '/api/crm/contacts/{contact}/restore';
    $endpoint = strtr($endpoint, [
        '{contact}' => $contactId,
    ]);

    // Restore the recently deleted contact
    $response = $api->post($endpoint, []);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
