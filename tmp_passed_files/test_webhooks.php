<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");
$hooks = $api->getAll('/api/contractors/projects/webhooks');
print_r($hooks);
if (!$hooks) {
    $ret = $api->post('/api/contractors/projects/webhooks', [
        'url' => 'https://test.com/webhook_endpoint.php',
        'event' => 'saved',
        'columns_changed' => ['status']
    ]);
    print($ret->getStatusCode() . "\n");
}
