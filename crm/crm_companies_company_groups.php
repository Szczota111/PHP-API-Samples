<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $company = $api->first('/api/crm/companies');
    if (!$company || !isset($company['id'])) {
        throw new RuntimeException('Unable to resolve a company for reading groups.');
    }

    $attachedGroups = $api->getAll("/api/crm/companies/{$company['id']}/groups?limit=5");
    if (empty($attachedGroups)) {
        $candidateGroup = $api->first('/api/crm/company-groups');
        if ($candidateGroup && isset($candidateGroup['id'])) {
            $api->post("/api/crm/companies/{$company['id']}/groups/attach", [
                'resources' => [$candidateGroup['id']]
            ]);
        }
    }

    $endpoint = '/api/crm/companies/{company}/groups';
    $endpoint = strtr($endpoint, [
        '{company}' => $company['id'],
    ]);

    // Get a list of company groups (with_trashed, only_trashed optional)
    $response = $api->get($endpoint);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
