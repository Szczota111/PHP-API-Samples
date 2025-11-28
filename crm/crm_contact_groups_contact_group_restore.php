<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $trashedGroup = $api->first('/api/crm/contact-groups?only_trashed=true');

    if (!$trashedGroup || !isset($trashedGroup['id'])) {
        $uniq = date('YmdHis');
        $newGroup = $api->create('/api/crm/contact-groups', [
            'name' => 'Sample Contact Group ' . $uniq,
            'slug' => 'sample-contact-group-' . $uniq,
            'filter' => 0,
        ]);

        if (!$newGroup || !isset($newGroup['id'])) {
            throw new RuntimeException('Failed to create a contact group to restore.');
        }

        $api->delete('/api/crm/contact-groups/' . $newGroup['id']);

        $trashedGroup = ['id' => $newGroup['id']];
    }

    if (!$trashedGroup || !isset($trashedGroup['id'])) {
        throw new RuntimeException('Unable to locate a trashed contact group to restore.');
    }

    $endpoint = '/api/crm/contact-groups/{contact_group}/restore';
    $endpoint = strtr($endpoint, [
        '{contact_group}' => $trashedGroup['id'],
    ]);

    // Restore the soft-deleted contact group
    $response = $api->post($endpoint, []);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
