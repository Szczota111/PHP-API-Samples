<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    echo "=== CHANGE ORDER STAGE UPDATE TEST ===\n";

    $preferredStatuses = ['verified', 'draft'];
    $changeOrder = null;

    foreach ($preferredStatuses as $status) {
        $changeOrder = $api->searchFirst('/api/contractors/change-orders', [
            'filters' => [
                [
                    'type' => 'and',
                    'field' => 'status',
                    'operator' => '=',
                    'value' => $status,
                ],
            ],
            'limit' => 1,
        ]);

        if (!empty($changeOrder)) {
            echo "Found {$status} change order #{$changeOrder['id']} ({$changeOrder['title']})\n";
            break;
        }
    }

    if (empty($changeOrder)) {
        $changeOrder = $api->first('/api/contractors/change-orders?limit=1');
        if (empty($changeOrder)) {
            throw new RuntimeException('Brak zmian do przetestowania.');
        }
        echo "Fallback: używam change order #{$changeOrder['id']} (status: {$changeOrder['status']})\n";
    }

    $changeOrderId = $changeOrder['id'];
    $projectId = $changeOrder['project_id'];
    $currentStageId = $changeOrder['project_stage_id'] ?? null;

    echo "Powiązany projekt: {$projectId}. Aktualny stage: " . ($currentStageId ?? 'brak') . "\n";

    $projectStages = $api->getAll("/api/contractors/projects/{$projectId}/stages?limit=50");
    if (empty($projectStages)) {
        throw new RuntimeException('Projekt nie ma żadnych stage\'ów – nie ma czego ustawiać.');
    }

    echo "Projekt posiada " . count($projectStages) . " stage'y:\n";
    foreach ($projectStages as $stage) {
        $label = $stage['name'] ?? ($stage['title'] ?? 'Stage');
        $number = $stage['stage_number'] ?? '?';
        echo " - ID {$stage['id']} (#{$number}) {$label} [{$stage['status']}]\n";
    }

    $candidateStages = array_filter($projectStages, static function ($stage) use ($currentStageId) {
        return $currentStageId === null || $stage['id'] !== $currentStageId;
    });

    if (empty($candidateStages)) {
        $targetStage = $projectStages[0];
        echo "Brak alternatywnych stage'y – ponownie ustawiam pierwszy (ID {$targetStage['id']}).\n";
    } else {
        $candidateStages = array_values($candidateStages);
        $targetStage = $candidateStages[array_rand($candidateStages)];
    }

    $targetStageId = $targetStage['id'];
    $targetStageLabel = $targetStage['name'] ?? ($targetStage['title'] ?? 'Stage');
    echo "Aktualizuję change order {$changeOrderId} -> stage {$targetStageId} ({$targetStageLabel}).\n";

    $resp = $api->patch("/api/contractors/change-orders/{$changeOrderId}", [
        'project_stage_id' => $targetStageId,
    ]);
    if (!in_array($resp->getStatusCode(), [200, 204], true)) {
        $api->throwErr($resp);
    }

    $updated = $api->record("/api/contractors/change-orders/{$changeOrderId}?include=projectStage");
    $updatedStageId = $updated['project_stage_id'] ?? null;

    if ($updatedStageId !== $targetStageId) {
        throw new RuntimeException('Stage nie został zaktualizowany.');
    }

    $updatedStageName = $updated['project_stage']['name'] ?? ($updated['project_stage']['title'] ?? 'Stage');
    echo "✅ Sukces! Zmiana {$changeOrderId} przypisana do stage {$updatedStageId} ({$updatedStageName}).\n";
} catch (Exception $e) {
    echo "❌ Błąd: " . $e->getMessage() . "\n";
    if ($e instanceof ApiRequestException) {
        echo "   Status: " . $e->getStatusCode() . "\n";
        echo "   Response: " . $e->getResponseBody() . "\n";
    }
    exit(1);
}
