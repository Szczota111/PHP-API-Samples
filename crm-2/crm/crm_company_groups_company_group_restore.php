<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $trashedGroup = $api->first('/api/crm/company-groups?only_trashed=true');

    if (!$trashedGroup || !isset($trashedGroup['id'])) {
        $uniq = date('YmdHis');
        $newGroup = $api->create('/api/crm/company-groups', [
            'name' => 'Sample Group ' . $uniq,
            'slug' => 'sample-group-' . $uniq,
        ]);

        if (!$newGroup || !isset($newGroup['id'])) {
            throw new RuntimeException('Failed to create a company group to restore.');
        }

        $api->delete('/api/crm/company-groups/' . $newGroup['id']);

        $trashedGroup = ['id' => $newGroup['id']];
    }

    if (!$trashedGroup || !isset($trashedGroup['id'])) {
        throw new RuntimeException('Unable to locate a trashed company group to restore.');
    }

    $endpoint = '/api/crm/company-groups/{company_group}/restore';
    $endpoint = strtr($endpoint, [
        '{company_group}' => $trashedGroup['id'],
    ]);

    // Restore the soft-deleted company group
    $response = $api->post($endpoint, []);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
