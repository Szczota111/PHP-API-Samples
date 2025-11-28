<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    // 1) Pobierz przykładowy projekt
    $project = $api->searchFirst('/api/contractors/projects', ['filters' => [
        [
            'type' => 'and',
            'field' => 'status',
            'operator' => 'like',
            'value' => '%in_progress%'
        ]
    ]]);
    if (empty($project)) {
        echo "Brak projektów.\n";
        exit(0);
    }
    $projectId = $project['id'];
    echo "Project ID: {$projectId}\n";

    // 2) Pobierz aktualnych kosztorysantów
    $managers = $api->getAll("/api/contractors/projects/{$projectId}/estimators?limit=50");
    $managerIds = array_map(function ($m) {
        return $m['id'];
    }, $managers);
    echo "Estimators (przed): " . (empty($managerIds) ? '-' : implode(', ', $managerIds)) . "\n";

    // 3) Wybierz kontakt do ustawienia jako kosztorysant
    $contact = $api->searchFirst('/api/crm/contacts', [
        'filters' => [
            [
                'type' => 'and',
                'field' => 'groups.slug',
                'operator' => '=',
                'value' => 'estimator'
            ]
        ],
    ]);
    if (empty($contact)) {
        echo "Brak kontaktów do przypięcia.\n";
        exit(0);
    }
    $contactId = $contact['id'];
    echo "Ustawiam kontakt {$contactId} jako kosztorysanta...\n";

    // 4) Przypnij kontakt jako kosztorysant (attach), tylko jeśli jeszcze nie jest
    if (!in_array($contactId, $managerIds)) {
        $resp = $api->post("/api/contractors/projects/{$projectId}/estimators/attach", [
            'resources' => [$contactId]
        ]);
        if (!in_array($resp->getStatusCode(), [200, 201, 204])) {
            $api->throwErr($resp);
        }
    } else {
        echo "Kontakt już jest kosztorysantem, pomijam attach.\n";
    }

    // 5) Zweryfikuj zmianę
    $managersAfter = $api->getAll("/api/contractors/projects/{$projectId}/estimators?limit=50");
    $managerIdsAfter = array_map(function ($m) {
        return $m['id'];
    }, $managersAfter);
    echo "Estimators (po):  " . (empty($managerIdsAfter) ? '-' : implode(', ', $managerIdsAfter)) . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
