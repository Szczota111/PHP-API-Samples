<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $webhook = $api->first('/api/crm/phonecalls/webhooks');
    if ($webhook && isset($webhook['id'])) {
        $webhookId = $webhook['id'];
    } else {
        $sampleWebhook = [
            'url' => 'https://example.com/phonecalls/' . uniqid(),
            'event' => 'created',
            'description' => 'Sample phonecall webhook',
            'active' => 1,
        ];

        $created = $api->post('/api/crm/phonecalls/webhooks', $sampleWebhook);
        $createdBody = json_decode($created->getBody()->getContents(), true);
        $webhookId = $createdBody['data']['id'] ?? null;
    }

    if (!$webhookId) {
        throw new RuntimeException('Nie udało się pobrać ani utworzyć webhooka.');
    }

    $endpoint = '/api/crm/phonecalls/webhooks/{webhook}';
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
