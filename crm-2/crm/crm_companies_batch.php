<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $endpoint = '/api/crm/companies/batch';
    $country = $api->first('/api/countries');
    if (!$country || !isset($country['id'])) {
        throw new RuntimeException('Unable to fetch country reference for company payload.');
    }

    $timestamp = date('YmdHis');
    $payload = [
        'resources' => [
            [
                'company_name' => "Batch {$timestamp} Builders",
                'short_name' => "BB{$timestamp}",
                'phone' => '+48 123 456 789',
                'email' => "builders{$timestamp}@example.com",
                'address_1' => 'Industrial Avenue 1',
                'city' => $country['name'] ?? 'Warsaw',
                'country_id' => $country['id'],
                'permission' => 1,
                'external_id' => "batch-{$timestamp}-A",
            ],
            [
                'company_name' => "Batch {$timestamp} Engineers",
                'short_name' => "BE{$timestamp}",
                'phone' => '+48 987 654 321',
                'email' => "engineers{$timestamp}@example.com",
                'address_1' => 'Innovation Park 5',
                'city' => $country['name'] ?? 'Warsaw',
                'country_id' => $country['id'],
                'permission' => 1,
                'external_id' => "batch-{$timestamp}-B",
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
