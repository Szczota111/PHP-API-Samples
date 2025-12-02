<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $webhook = $api->first('/api/crm/contacts/webhooks');
    if (!$webhook || !isset($webhook['id'])) {
        $sampleWebhook = [
            'url' => 'https://webhook.site/' . uniqid('contact-', true),
            'event' => 'created',
            'payload' => [
                'source' => 'php-sample',
                'generated_at' => date(DATE_ATOM),
            ],
            'description' => 'Sample contact webhook ' . date('c'),
            'active' => 1,
        ];

        $webhook = $api->create('/api/crm/contacts/webhooks', $sampleWebhook);
    }

    if (!$webhook || !isset($webhook['id'])) {
        throw new RuntimeException('Unable to resolve or create a contact webhook.');
    }

    $endpoint = '/api/crm/contacts/webhooks/{webhook}';
    $endpoint = strtr($endpoint, [
        '{webhook}' => $webhook['id'],
    ]);

    // Get webhook details
    $response = $api->get($endpoint);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
