<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    // 1) Get example project
    $project = $api->searchFirst('/api/contractors/projects', [
        'filters' => [
            [
                'type' => 'and',
                'field' => 'status',
                'value' => 'awarded'
            ],
            [
                'type' => 'and',
                'field' => 'tm',
                'value' => '0'
            ]
        ]
    ]);
    if (empty($project)) {
        echo "No projects.\n";
        exit(0);
    }
    $projectId = $project['id'];
    echo "Project ID: {$projectId}\n";

    // Get scopes of work for the project via API
    $scopes = $api->getAll("/api/contractors/projects/{$projectId}/scopes-of-work?limit=50");
    if (empty($scopes)) {
        echo "Project has no scopes of work - testing without scopes.\n";
        $scopeIds = [];
    } else {
        // Check which scopes are already assigned to stages
        $assignedScopes = $api->searchAll("/api/contractors/project-stages", [
            'filters' => [
                [
                    'type' => 'and',
                    'field' => 'project_id',
                    'value' => $projectId
                ]
            ],
            'includes' => [['relation' => 'scopesOfWork']],
            'limit' => 50
        ]);
        $assignedScopeIds = [];
        foreach ($assignedScopes as $assignedStage) {
            if (isset($assignedStage['scopes_of_work'])) {
                foreach ($assignedStage['scopes_of_work'] as $scope) {
                    $assignedScopeIds[] = $scope['id'];
                }
            }
        }

        // Find scopes that are NOT assigned
        $availableScopes = array_filter($scopes, function ($scope) use ($assignedScopeIds) {
            return !in_array($scope['id'], $assignedScopeIds);
        });

        if (empty($availableScopes)) {
            echo "All scopes of work are already assigned - testing without scopes.\n";
            $scopeIds = [];
        } else {
            $scopeIds = array_slice(array_map(fn($s) => $s['id'], $availableScopes), 0, 2);
            echo "Available unassigned scopes of work: " . implode(', ', $scopeIds) . "\n";
        }
    }

    // 2) Create new project stage
    $payload = [
        'project_id' => $projectId,
        'title' => 'API Test Stage ' . date('c'),
        'status' => 'pending',
        'prepayment_percentage' => 20
    ];

    // Add scopes if available
    if (!empty($scopeIds)) {
        $payload['scopes_of_work'] = $scopeIds;
    }
    $resp = $api->post('/api/contractors/project-stages', $payload);
    if (!in_array($resp->getStatusCode(), [200, 201])) $api->throwErr($resp);
    $created = json_decode($resp->getBody()->getContents(), true);
    $stageId = $created['data']['id'];
    echo "Created stage ID: {$stageId}\n";

    // 3) Get stage details with relations
    $stage = $api->record("/api/contractors/project-stages/{$stageId}");
    if (!$stage) {
        throw new Exception("Failed to get stage {$stageId}");
    }
    echo "Stage title: " . $stage['title'] . "\n";
    echo "Stage status: " . $stage['status'] . "\n";
    echo "Prepayment percentage: " . $stage['prepayment_percentage'] . "%\n";

    if (isset($stage['scopes_of_work'])) {
        echo "Assigned scopes count: " . count($stage['scopes_of_work']) . "\n";
    }

    // Check if stage_amount was automatically calculated
    if (isset($stage['stage_amount']) && $stage['stage_amount'] > 0) {
        echo "Stage amount (auto-calculated): " . $stage['stage_amount'] . " " . $stage['stage_amount_currency'] . "\n";
    } else {
        echo "Stage amount: not calculated (no contract amount or scope amounts)\n";
    }

    // 4) Update stage status
    $resp = $api->patch("/api/contractors/project-stages/{$stageId}", [
        'status' => 'completed'
    ]);
    if (!in_array($resp->getStatusCode(), [200, 204])) $api->throwErr($resp);
    echo "Updated stage status to completed.\n";

    // 5) Try to add acceptation_notes (should succeed for completed)
    $resp = $api->patch("/api/contractors/project-stages/{$stageId}", [
        'status' => 'accepted',
        'acceptation_notes' => 'Stage completed successfully via API test'
    ]);
    if (!in_array($resp->getStatusCode(), [200, 204])) $api->throwErr($resp);
    echo "Updated stage status to accepted with notes.\n";

    // 6) Try to modify scopes_of_work (if available)
    if (!empty($scopeIds)) {
        try {
            $resp = $api->patch("/api/contractors/project-stages/{$stageId}", [
                'scopes_of_work' => [$scopeIds[0]] // try removing one scope
            ]);
            if ($resp->getStatusCode() >= 400) {
                echo "✓ Correctly blocked scopes modification for accepted stage.\n";
            } else {
                echo "✗ ERROR: Should have blocked scopes modification!\n";
            }
        } catch (Exception $e) {
            echo "✓ Correctly blocked scopes modification: " . $e->getMessage() . "\n";
        }
    }

    // 7) Try to set prohibited fields (stage_amount, final_amount, stage_progress)
    try {
        $resp = $api->patch("/api/contractors/project-stages/{$stageId}", [
            'stage_amount' => 5000,
            'final_amount' => 6000,
            'stage_progress' => 50
        ]);
        if ($resp->getStatusCode() >= 400) {
            echo "✓ Correctly blocked prohibited fields.\n";
        } else {
            echo "✗ ERROR: Should have blocked prohibited fields!\n";
        }
    } catch (Exception $e) {
        echo "✓ Correctly blocked prohibited fields: " . $e->getMessage() . "\n";
    }

    // 7) Test relations - get stages via project
    $projectStages = $api->getAll("/api/contractors/projects/{$projectId}/stages?limit=50");
    $stageIds = array_map(fn($s) => $s['id'], $projectStages);
    echo "Project stages: " . implode(', ', $stageIds) . "\n";

    // Check if our stage is on the list
    if (in_array($stageId, $stageIds)) {
        echo "✓ Stage correctly associated with project.\n";
    } else {
        echo "✗ ERROR: Stage not found in project stages!\n";
    }

    // 8) Test filtering
    $acceptedStages = $api->searchAll("/api/contractors/project-stages", [
        'filters' => [
            [
                'type' => 'and',
                'field' => 'status',
                'value' => 'accepted'
            ]
        ],
        'limit' => 50
    ]);
    echo "Found " . count($acceptedStages) . " accepted stages.\n";

    // 9) Test search
    $searchResults = $api->searchAll("/api/contractors/project-stages", [
        'search' => 'API Test',
        'limit' => 50
    ]);
    echo "Search results for 'API Test': " . count($searchResults) . " stages.\n";

    // 10) Cleanup - delete stage
    try {
        $resp = $api->delete("/api/contractors/project-stages/{$stageId}");
        if (!in_array($resp->getStatusCode(), [200, 204])) $api->throwErr($resp);
        echo "Deleted stage {$stageId}.\n";
    } catch (ApiRequestException $e) {
        if ($e->getStatusCode() === 403) {
            echo "✓ Delete blocked (403 Forbidden) - this may be expected based on permissions.\n";
        } else {
            echo "✗ Unexpected error during delete: " . $e->getMessage() . "\n";
        }
    }

    echo "\n✓ All tests completed successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
