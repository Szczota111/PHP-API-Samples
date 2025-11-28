<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $companyGroup = $api->first('/api/crm/company-groups');

    if (!$companyGroup || !isset($companyGroup['id'])) {
        $uniq = date('YmdHis');
        $companyGroup = $api->create('/api/crm/company-groups', [
            'name' => 'Sample Company Group ' . $uniq,
            'slug' => 'sample-company-group-' . $uniq,
        ]);
    }

    if (!$companyGroup || !isset($companyGroup['id'])) {
        throw new RuntimeException('Unable to resolve a company group to fetch.');
    }

    $endpoint = '/api/crm/company-groups/{company_group}';
    $endpoint = strtr($endpoint, [
        '{company_group}' => $companyGroup['id'],
    ]);

    // Get company group (supports with_trashed / only_trashed query params)
    $response = $api->get($endpoint);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
