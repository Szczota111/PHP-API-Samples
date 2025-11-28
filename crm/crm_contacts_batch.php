<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $company = $api->first('/api/crm/companies');
    $companyId = $company['id'] ?? null;

    $endpoint = '/api/crm/contacts/batch';
    $uniq = date('YmdHis');

    // Create two contacts in a single request
    $payload = [
        'resources' => [
            [
                'last_name' => 'Batch-' . $uniq,
                'first_name' => 'ContactA',
                'email' => 'batch-a-' . $uniq . '@example.com',
                'company_id' => $companyId,
            ],
            [
                'last_name' => 'Batch-' . $uniq,
                'first_name' => 'ContactB',
                'email' => 'batch-b-' . $uniq . '@example.com',
                'company_id' => $companyId,
            ],
        ],
    ];

    $response = $api->post($endpoint, $payload);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
