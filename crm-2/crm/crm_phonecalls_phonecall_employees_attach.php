<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $phonecall = $api->first('/api/crm/phonecalls');
    if (!$phonecall || !isset($phonecall['id'])) {
        $payload = [
            'subject' => 'API Phone Call ' . date('c'),
            'date' => date('Y-m-d'),
            'time' => date('H:i'),
            'contact_company_switch' => 3,
            'other_customer_name' => 'Przykładowy klient',
            'priority' => 1,
        ];
        $phonecall = $api->create('/api/crm/phonecalls', $payload);
    }

    if (!$phonecall || !isset($phonecall['id'])) {
        throw new RuntimeException('Nie udało się przygotować połączenia telefonicznego.');
    }

    $phonecallId = $phonecall['id'];

    $fetchList = static function (callable $request): array {
        $response = $request();
        if (!in_array($response->getStatusCode(), [200, 201], true)) {
            throw new RuntimeException('Nie udało się pobrać danych z API.');
        }
        $payload = json_decode($response->getBody()->getContents(), true);
        return $payload['data'] ?? [];
    };

    $currentEmployees = $fetchList(static function () use ($api, $phonecallId) {
        return $api->get("/api/crm/phonecalls/{$phonecallId}/employees?limit=20");
    });
    $currentIds = array_column($currentEmployees, 'id');

    $contacts = $fetchList(static function () use ($api) {
        return $api->get('/api/crm/contacts?limit=20');
    });
    $contactIds = array_column($contacts, 'id');
    if (empty($contactIds)) {
        throw new RuntimeException('Brak kontaktów, które można podpiąć.');
    }

    $candidates = array_values(array_diff($contactIds, $currentIds));
    if (empty($candidates)) {
        $candidates = $contactIds; // podpinamy ponownie istniejących, aby przykład zawsze działał
    }

    $resources = array_slice($candidates, 0, 2);
    if (empty($resources)) {
        throw new RuntimeException('Nie udało się przygotować listy pracowników do podpięcia.');
    }

    $endpoint = '/api/crm/phonecalls/{phonecall}/employees/attach';
    $endpoint = strtr($endpoint, [
        '{phonecall}' => $phonecallId,
    ]);

    $payload = [
        'resources' => $resources,
    ];

    $response = $api->post($endpoint, $payload);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
