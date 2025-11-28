<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/api.php';

$api = new Api('https://demo.contractors.es', 'admin', 'admin', 'en');

function pickProjectWithStage(Api $api): array
{
    $preferredStatuses = ['contract_signed', 'in_progress', 'awarded'];

    foreach ($preferredStatuses as $status) {
        $project = $api->searchFirst('/api/contractors/projects', [
            'filters' => [
                [
                    'type' => 'and',
                    'field' => 'status',
                    'value' => $status,
                ],
            ],
        ]);

        if ($project) {
            $stage = $api->searchFirst('/api/contractors/project-stages', [
                'filters' => [
                    [
                        'type' => 'and',
                        'field' => 'project_id',
                        'value' => $project['id'],
                    ],
                ],
            ]);

            if ($stage) {
                return [$project, $stage];
            }
        }
    }

    $projects = $api->getAll('/api/contractors/projects?limit=25');
    foreach ($projects as $project) {
        $stage = $api->searchFirst('/api/contractors/project-stages', [
            'filters' => [
                [
                    'type' => 'and',
                    'field' => 'project_id',
                    'value' => $project['id'],
                ],
            ],
        ]);
        if ($stage) {
            return [$project, $stage];
        }
    }

    throw new RuntimeException('Nie znaleziono projektu z etapami do testów.');
}

function pickSubcontractorCompany(Api $api): array
{
    $companies = $api->getAll('/api/crm/companies?limit=50');
    if (empty($companies)) {
        throw new RuntimeException('Brak firm w CRM do wykorzystania jako podwykonawca.');
    }

    return $companies[array_rand($companies)];
}

try {
    echo "=== SUBCONTRACTORS AGREEMENTS API TEST ===\n\n";

    [$project, $stage] = pickProjectWithStage($api);
    $projectId = $project['id'];
    echo "Wybrano projekt #{$projectId} ({$project['project_name']}) ze statusem {$project['status']}.\n";
    echo "Etap testowy: #{$stage['id']} ({$stage['name']} / nr {$stage['stage_number']}).\n";

    $scopeOfWork = $api->first('/api/contractors/scope-of-works');
    if (!$scopeOfWork) {
        throw new RuntimeException('Brak Scope of Work do przypisania do umowy.');
    }
    echo "Scope of Work: #{$scopeOfWork['id']} ({$scopeOfWork['name']}).\n";

    $subcontractor = pickSubcontractorCompany($api);
    echo "Podwykonawca: #{$subcontractor['id']} ({$subcontractor['company_name']}).\n";

    $currency = $project['contract_amount_currency'] ?? 'USD';

    echo "\n-- Tworzenie umowy TM ze stawką godzinową --\n";
    $tmAgreement = $api->create('/api/contractors/subcontractors-agreements', [
        'project_id' => $projectId,
        'company_id' => $subcontractor['id'],
        'subject' => 'API TM Agreement ' . date('Y-m-d H:i:s'),
        'tm' => 1,
        'default_hour_rate' => 85,
        'default_hour_rate_currency' => $currency,
        'salary_custom_percentages' => 0,
        'hourRates' => [
            [
                'scope_of_work_id' => $scopeOfWork['id'],
                'hour_rate' => 92.5,
                'hour_rate_currency' => $currency,
            ],
        ],
    ]);
    $tmAgreementId = $tmAgreement['id'];
    echo "✓ Utworzono umowę TM #{$tmAgreementId}\n";

    echo "\n-- Tworzenie umowy ryczałtowej na etap --\n";
    $fixedAgreement = $api->create('/api/contractors/subcontractors-agreements', [
        'project_id' => $projectId,
        'company_id' => $subcontractor['id'],
        'subject' => 'API Fixed Agreement ' . date('Y-m-d H:i:s'),
        'tm' => 0,
        'contract_amount' => 15000,
        'contract_amount_currency' => $currency,
        'projectStages' => [$stage['id']],
    ]);
    $fixedAgreementId = $fixedAgreement['id'];
    echo "✓ Utworzono umowę ryczałtową #{$fixedAgreementId} przypisaną do etapu {$stage['id']}\n";

    echo "\n-- Dodawanie płatności do umowy TM --\n";
    $payment = $api->create("/api/contractors/subcontractors-agreements/{$tmAgreementId}/paid", [
        'date' => date('Y-m-d'),
        'amount' => 1200,
        'amount_currency' => $currency,
        'description' => 'API test payment',
        'paid_in_full' => false,
    ]);
    echo "✓ Dodano płatność #{$payment['id']} do umowy TM\n";

    echo "\n-- Usuwanie umowy bez płatności --\n";
    $deleteResponse = $api->delete("/api/contractors/subcontractors-agreements/{$fixedAgreementId}");
    if (!in_array($deleteResponse->getStatusCode(), [200, 204])) {
        $api->throwErr($deleteResponse);
    }
    echo "✓ Usunięto umowę ryczałtową #{$fixedAgreementId}\n";

    echo "\n✅ Test zakończony powodzeniem.\n";
    echo "Kroki: projekt -> umowa TM -> umowa ryczałtowa -> płatność -> usunięcie.\n";
} catch (Exception $e) {
    echo "\n❌ Błąd testu: " . $e->getMessage() . "\n";
    if ($e instanceof ApiRequestException) {
        echo "Status: " . $e->getStatusCode() . "\n";
        echo "Response: " . $e->getResponseBody() . "\n";
    }
    exit(1);
}
