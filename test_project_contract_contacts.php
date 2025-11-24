<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    // 1) Znajdź pierwszy projekt w statusie "awarded" i z klientem typu 2 (kontakt indywidualny)
    $project = $api->searchFirst('/api/contractors/projects', [
        'filters' => [
            [
                'type' => 'and',
                'field' => 'status',
                'operator' => '=',
                'value' => 'awarded'
            ],
            [
                'type' => 'and',
                'field' => 'customer_type',
                'operator' => '=',
                'value' => 2
            ]
        ],
    ]);

    if (empty($project)) {
        echo "Brak projektów w statusie 'awarded' z klientem typu residential.\n";
        exit(0);
    }

    $projectId = $project['id'];
    $projectName = $project['project_name'] ?? $projectId;
    $currentContacts = array_map(static function ($contact) {
        return $contact['id'];
    }, $project['contacts'] ?? []);

    echo "Wybrany projekt #{$projectId} ({$projectName})\n";
    echo "Kontakty przed: " . (empty($currentContacts) ? '-' : implode(', ', $currentContacts)) . "\n";

    // 2) Pobierz kandydatów na nowe kontakty (preferujemy grupę 'customer')
    $candidateIds = [];
    $contactCandidates = $api->searchAll('/api/crm/contacts', [
        'filters' => [
            [
                'type' => 'and',
                'field' => 'groups.slug',
                'operator' => '=',
                'value' => 'customer'
            ]
        ],
        'limit' => 50,
    ]);

    foreach ($contactCandidates as $contact) {
        if (!in_array($contact['id'], $currentContacts, true)) {
            $candidateIds[] = $contact['id'];
        }
        if (count($candidateIds) === 2) {
            break;
        }
    }

    // jeśli dalej za mało kandydatów, dobierz dowolne kontakty
    if (count($candidateIds) < 2) {
        $fallbackContacts = $api->searchAll('/api/crm/contacts', [
            'limit' => 50,
        ]);
        foreach ($fallbackContacts as $contact) {
            if (!in_array($contact['id'], $currentContacts, true) && !in_array($contact['id'], $candidateIds, true)) {
                $candidateIds[] = $contact['id'];
            }
            if (count($candidateIds) === 2) {
                break;
            }
        }
    }

    if (count($candidateIds) < 2) {
        echo "Nie udało się znaleźć dwóch nowych kontaktów do przypięcia.\n";
        exit(1);
    }

    echo "Nowe kontakty: " . implode(', ', $candidateIds) . "\n";

    // 3) Odpnij poprzednie kontakty (tylko jeśli istnieją i nie należą do nowych)
    $contactsToDetach = array_diff($currentContacts, $candidateIds);
    if (!empty($contactsToDetach)) {
        echo "Odłączam dotychczasowe kontakty: " . implode(', ', $contactsToDetach) . "\n";
        $resp = $api->delete("/api/contractors/projects/{$projectId}/contacts/detach", [
            'resources' => array_values($contactsToDetach),
        ]);
        if (!in_array($resp->getStatusCode(), [200, 204])) {
            $api->throwErr($resp);
        }
    }

    // 4) Podepnij nowe kontakty poprzez endpoint contacts/attach
    echo "Dołączam nowe kontakty przez /contacts/attach...\n";
    $resp = $api->post("/api/contractors/projects/{$projectId}/contacts/attach", [
        'resources' => $candidateIds,
    ]);
    if (!in_array($resp->getStatusCode(), [200, 201, 204])) {
        $api->throwErr($resp);
    }

    // 5) Zweryfikuj zmianę
    $projectAfter = $api->record("/api/contractors/projects/{$projectId}?include=contacts");
    $updatedContacts = array_map(static function ($contact) {
        return $contact['id'];
    }, $projectAfter['contacts'] ?? []);

    echo "Kontakty po zmianie: " . (empty($updatedContacts) ? '-' : implode(', ', $updatedContacts)) . "\n";

    $diff = array_diff($candidateIds, $updatedContacts);
    if (!empty($diff)) {
        throw new RuntimeException('Nie wszystkie kontakty zostały ustawione poprawnie.');
    }

    echo "Zakończono sukcesem – kontakty zostały podmienione.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    if ($e instanceof ApiRequestException) {
        echo "Response: " . $e->getResponseBody() . "\n";
    }
    exit(1);
}
