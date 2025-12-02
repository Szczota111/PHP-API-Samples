<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $trashed = $api->getAll('/api/crm/phonecalls?only_trashed=1&limit=1');
    $phonecallId = $trashed[0]['id'] ?? null;

    if (!$phonecallId) {
        $phonecall = $api->create('/api/crm/phonecalls', [
            'subject' => 'RESTORE phone call ' . date('c'),
            'date' => date('Y-m-d'),
            'time' => date('H:i'),
            'priority' => 1,
            'contact_company_switch' => 3,
            'other_customer_name' => 'Klient do przywrócenia',
        ]);

        $phonecallId = $phonecall['id'] ?? null;
        if (!$phonecallId) {
            throw new RuntimeException('Nie udało się utworzyć połączenia testowego.');
        }

        $api->delete("/api/crm/phonecalls/{$phonecallId}");
    }

    $endpoint = '/api/crm/phonecalls/{phonecall}/restore';
    $endpoint = strtr($endpoint, [
        '{phonecall}' => $phonecallId,
    ]);

    $response = $api->post($endpoint, []);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
