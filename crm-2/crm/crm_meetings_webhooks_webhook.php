<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $webhook = $api->first('/api/crm/meetings/webhooks');

    if (!$webhook || !isset($webhook['id'])) {
        $sampleWebhook = [
            'url' => 'https://example.com/meetings/' . uniqid(),
            'event' => 'created',
            'description' => 'Sample meetings webhook',
            'active' => 1,
        ];

        $created = $api->post('/api/crm/meetings/webhooks', $sampleWebhook);
        $createdBody = json_decode($created->getBody()->getContents(), true);
        $webhookId = $createdBody['data']['id'] ?? null;
    } else {
        $webhookId = $webhook['id'];
    }

    if (!$webhookId) {
        throw new RuntimeException('Nie udało się pozyskać ani utworzyć webhooka.');
    }

    $endpoint = '/api/crm/meetings/webhooks/{webhook}';
    $endpoint = strtr($endpoint, [
        '{webhook}' => $webhookId,
    ]);

    $response = $api->get($endpoint);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
