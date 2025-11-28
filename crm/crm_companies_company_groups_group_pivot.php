<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $company = $api->first('/api/crm/companies');
    if (!$company || !isset($company['id'])) {
        throw new RuntimeException('Unable to resolve a company for pivot update.');
    }

    $attachedGroups = $api->getAll("/api/crm/companies/{$company['id']}/groups?limit=5");
    if (empty($attachedGroups)) {
        $availableGroup = $api->first('/api/crm/company-groups');
        if (!$availableGroup || !isset($availableGroup['id'])) {
            throw new RuntimeException('No company groups available to attach.');
        }
        $api->post("/api/crm/companies/{$company['id']}/groups/attach", [
            'resources' => [$availableGroup['id']]
        ]);
        $attachedGroups = $api->getAll("/api/crm/companies/{$company['id']}/groups?limit=5");
    }

    $groupId = $attachedGroups[0]['id'];

    $endpoint = '/api/crm/companies/{company}/groups/{group}/pivot';
    $endpoint = strtr($endpoint, [
        '{company}' => $company['id'],
        '{group}' => $groupId,
    ]);

    // Update pivot metadata for the company-group relation
    $payload = [
        'pivot' => [
            'note' => 'Important partner updated ' . date('c'),
            'priority' => 10,
            'status' => 'active'
        ],
    ];

    $response = $api->patch($endpoint, $payload);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
