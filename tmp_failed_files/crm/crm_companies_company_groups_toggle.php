<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $company = $api->first('/api/crm/companies');
    if (!$company || !isset($company['id'])) {
        throw new RuntimeException('Unable to resolve a company for toggling groups.');
    }

    $groups = $api->getAll('/api/crm/company-groups?limit=5');
    $groupIds = array_column($groups ?? [], 'id');
    if (count($groupIds) < 2) {
        throw new RuntimeException('Need at least two company groups to demonstrate toggle.');
    }

    $endpoint = '/api/crm/companies/{company}/groups/toggle';
    $endpoint = strtr($endpoint, [
        '{company}' => $company['id'],
    ]);

    // Toggle two groups on/off by passing pivot payload
    $payload = [
        'resources' => [
            $groupIds[0] => ['note' => 'Toggle ' . date('c')],
            $groupIds[1] => ['note' => 'Toggle ' . date('c')]
        ]
    ];

    $response = $api->patch($endpoint, $payload);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
