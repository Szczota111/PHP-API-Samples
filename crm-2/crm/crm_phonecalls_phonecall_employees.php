<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $phonecall = $api->first('/api/crm/phonecalls');
    if (!$phonecall || !isset($phonecall['id'])) {
        $phonecall = $api->create('/api/crm/phonecalls', [
            'subject' => 'API Phone Call ' . date('c'),
            'date' => date('Y-m-d'),
            'time' => date('H:i'),
            'contact_company_switch' => 3,
            'other_customer_name' => 'Przykładowy klient',
            'priority' => 1,
        ]);
    }

    if (!$phonecall || !isset($phonecall['id'])) {
        throw new RuntimeException('Nie udało się przygotować połączenia.');
    }

    $phonecallId = $phonecall['id'];

    $employees = $api->getAll("/api/crm/phonecalls/{$phonecallId}/employees?limit=10");
    if (empty($employees)) {
        $contacts = $api->getAll('/api/crm/contacts?limit=5');
        $contactIds = array_column($contacts ?? [], 'id');
        if (empty($contactIds)) {
            throw new RuntimeException('Brak kontaktów do przypięcia jako pracownicy.');
        }

        $api->post("/api/crm/phonecalls/{$phonecallId}/employees/attach", [
            'resources' => array_slice($contactIds, 0, 2),
        ]);

        $employees = $api->getAll("/api/crm/phonecalls/{$phonecallId}/employees?limit=10");
    }

    $endpoint = '/api/crm/phonecalls/{phonecall}/employees';
    $endpoint = strtr($endpoint, [
        '{phonecall}' => $phonecallId,
    ]);

    $response = $api->get($endpoint . '?limit=10');

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
