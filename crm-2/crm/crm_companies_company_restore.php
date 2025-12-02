<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $trashedCompany = $api->first('/api/crm/companies?with_trashed=true&only_trashed=true');

    if (!$trashedCompany || !isset($trashedCompany['id'])) {
        $country = $api->first('/api/countries');
        if (!$country || !isset($country['id'])) {
            throw new RuntimeException('Unable to fetch country for temp company creation.');
        }

        $timestamp = date('YmdHis');
        $newCompany = $api->create('/api/crm/companies', [
            'company_name' => "Temp Restore {$timestamp}",
            'short_name' => "REST{$timestamp}",
            'country_id' => $country['id'],
            'permission' => 1
        ]);

        if (!$newCompany || !isset($newCompany['id'])) {
            throw new RuntimeException('Failed to create temp company for restore demo.');
        }

        $deleteResponse = $api->delete("/api/crm/companies/{$newCompany['id']}");
        if (!in_array($deleteResponse->getStatusCode(), [200, 204])) {
            throw new RuntimeException('Failed to soft-delete temp company before restore.');
        }

        $trashedCompany = ['id' => $newCompany['id']];
    }

    if (!$trashedCompany || !isset($trashedCompany['id'])) {
        throw new RuntimeException('Still no trashed company available to restore.');
    }

    $endpoint = '/api/crm/companies/{company}/restore';
    $endpoint = strtr($endpoint, [
        '{company}' => $trashedCompany['id'],
    ]);

    // Restore company
    $response = $api->post($endpoint, []);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
