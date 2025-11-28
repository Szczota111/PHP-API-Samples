<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $contact = $api->first('/api/crm/contacts');
    if (!$contact || !isset($contact['id'])) {
        throw new RuntimeException('Unable to resolve a contact for group batch creation.');
    }

    $endpoint = '/api/crm/contacts/{contact}/groups/batch';
    $endpoint = strtr($endpoint, [
        '{contact}' => $contact['id'],
    ]);

    $uniq = date('YmdHis');

    // Create and attach multiple groups to the contact in one request
    $payload = [
        'resources' => [
            [
                'name' => 'Contact Batch Group ' . $uniq,
                'slug' => 'contact-batch-group-' . $uniq,
                'filter' => 0,
            ],
            [
                'name' => 'Contact Batch Group ' . $uniq . '-b',
                'slug' => 'contact-batch-group-' . $uniq . '-b',
                'filter' => 0,
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
