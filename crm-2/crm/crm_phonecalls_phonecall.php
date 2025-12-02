<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $phonecall = $api->first('/api/crm/phonecalls?with_trashed=1');
    if (!$phonecall || !isset($phonecall['id'])) {
        $phonecall = $api->create('/api/crm/phonecalls', [
            'subject' => 'API Phone Call ' . date('c'),
            'date' => date('Y-m-d'),
            'time' => date('H:i'),
            'priority' => 1,
            'contact_company_switch' => 3,
            'other_customer_name' => 'Przykładowy klient',
        ]);
    }

    if (!$phonecall || !isset($phonecall['id'])) {
        throw new RuntimeException('Nie udało się wyszukać ani utworzyć połączenia.');
    }

    $endpoint = '/api/crm/phonecalls/{phonecall}';
    $endpoint = strtr($endpoint, [
        '{phonecall}' => $phonecall['id'],
    ]);

    $response = $api->get($endpoint);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
