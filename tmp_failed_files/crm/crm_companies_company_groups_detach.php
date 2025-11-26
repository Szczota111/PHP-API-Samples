<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $company = $api->first('/api/crm/companies');
    if (!$company || !isset($company['id'])) {
        throw new RuntimeException('Unable to resolve a company with groups to detach.');
    }

    $attachedGroups = $api->getAll("/api/crm/companies/{$company['id']}/groups?limit=10");
    $groupIds = array_column($attachedGroups ?? [], 'id');
    if (empty($groupIds)) {
        throw new RuntimeException('Selected company has no groups to detach.');
    }

    $endpoint = '/api/crm/companies/{company}/groups/detach';
    $endpoint = strtr($endpoint, [
        '{company}' => $company['id'],
    ]);

    // Detach currently attached groups from the company
    $payload = [
        'resources' => array_slice($groupIds, 0, 3),
    ];

    $response = $api->delete($endpoint, $payload);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
