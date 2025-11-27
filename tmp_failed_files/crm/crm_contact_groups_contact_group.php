<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $contactGroup = $api->first('/api/crm/contact-groups');

    if (!$contactGroup || !isset($contactGroup['id'])) {
        $uniq = date('YmdHis');
        $contactGroup = $api->create('/api/crm/contact-groups', [
            'name' => 'Sample Contact Group ' . $uniq,
            'slug' => 'sample-contact-group-' . $uniq,
            'filter' => 0,
        ]);
    }

    if (!$contactGroup || !isset($contactGroup['id'])) {
        throw new RuntimeException('Unable to resolve a contact group to fetch.');
    }

    $endpoint = '/api/crm/contact-groups/{contact_group}';
    $endpoint = strtr($endpoint, [
        '{contact_group}' => $contactGroup['id'],
    ]);

    // Get contact group details
    $response = $api->get($endpoint);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
