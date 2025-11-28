<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $company = $api->first('/api/crm/companies');
    if (!$company || !isset($company['id'])) {
        throw new RuntimeException('Unable to resolve a sample company to attach groups to.');
    }

    $groups = $api->getAll('/api/crm/company-groups?limit=5');
    $groupIds = array_column($groups ?? [], 'id');
    if (empty($groupIds)) {
        throw new RuntimeException('No company groups available for attachment.');
    }

    $endpoint = '/api/crm/companies/{company}/groups/attach';
    $endpoint = strtr($endpoint, [
        '{company}' => $company['id'],
    ]);

    // Attach the first available groups to the retrieved company
    $payload = [
        'resources' => array_slice($groupIds, 0, 2),
    ];

    $response = $api->post($endpoint, $payload);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
