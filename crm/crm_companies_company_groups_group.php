<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $company = $api->first('/api/crm/companies');
    if (!$company || !isset($company['id'])) {
        throw new RuntimeException('Unable to resolve a company for group lookup.');
    }

    $attachedGroups = $api->getAll("/api/crm/companies/{$company['id']}/groups?limit=5");
    if (empty($attachedGroups)) {
        $candidateGroup = $api->first('/api/crm/company-groups');
        if (!$candidateGroup || !isset($candidateGroup['id'])) {
            throw new RuntimeException('No company groups exist to attach.');
        }
        $api->post("/api/crm/companies/{$company['id']}/groups/attach", [
            'resources' => [$candidateGroup['id']]
        ]);
        $attachedGroups = $api->getAll("/api/crm/companies/{$company['id']}/groups?limit=5");
    }

    $groupId = $attachedGroups[0]['id'];

    $endpoint = '/api/crm/companies/{company}/groups/{group}';
    $endpoint = strtr($endpoint, [
        '{company}' => $company['id'],
        '{group}' => $groupId,
    ]);

    // Get company group (with_trashed/only_trashed optional)
    $response = $api->get($endpoint);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
