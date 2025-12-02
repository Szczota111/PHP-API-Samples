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

    $employees = $fetchList(static function () use ($api, $phonecallId) {
        return $api->get("/api/crm/phonecalls/{$phonecallId}/employees?limit=10");
    });

    if (empty($employees)) {
        $contacts = $fetchList(static function () use ($api) {
            return $api->get('/api/crm/contacts?limit=5');
        });

        $contactIds = array_column($contacts, 'id');
        if (empty($contactIds)) {
            throw new RuntimeException('Brak kontaktów do przygotowania przykładu detach.');
        }

        $api->post("/api/crm/phonecalls/{$phonecallId}/employees/attach", [
            'resources' => array_slice($contactIds, 0, 2),
        ]);

        $employees = $fetchList(static function () use ($api, $phonecallId) {
            return $api->get("/api/crm/phonecalls/{$phonecallId}/employees?limit=10");
        });
    }

    $employeeIds = array_column($employees, 'id');
    if (empty($employeeIds)) {
        throw new RuntimeException('Nie znaleziono pracowników do odpięcia.');
    }

    $endpoint = '/api/crm/phonecalls/{phonecall}/employees/detach';
    $endpoint = strtr($endpoint, [
        '{phonecall}' => $phonecallId,
    ]);

    $payload = [
        'resources' => array_slice($employeeIds, 0, 2),
    ];

    $response = $api->delete($endpoint, $payload);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
