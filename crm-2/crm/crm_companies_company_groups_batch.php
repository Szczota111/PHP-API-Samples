<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $company = $api->first('/api/crm/companies');
    if (!$company || !isset($company['id'])) {
        throw new RuntimeException('Unable to locate a company to own the groups.');
    }

    $endpoint = '/api/crm/companies/{company}/groups/batch';
    $endpoint = strtr($endpoint, [
        '{company}' => $company['id'],
    ]);

    $timestamp = date('YmdHis');
    $payload = [
        'resources' => [
            [
                'name' => "VIP Clients {$timestamp}",
                'slug' => "vip-clients-{$timestamp}"
            ],
            [
                'name' => "Partners {$timestamp}",
                'slug' => "partners-{$timestamp}"
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
