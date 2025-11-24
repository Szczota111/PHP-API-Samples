<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    echo "=== Test contractors/projects/XX/stages relation ===\n\n";

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
        echo "No projects to test.\n";
        exit(0);
    }

    $projectId = $project['id'];
    echo "1. Found project ID: {$projectId}\n";
    echo "   Title: " . ($project['title'] ?? 'N/A') . "\n";
    echo "   Status: " . ($project['status'] ?? 'N/A') . "\n\n";

    // 2) Get project stages via relational endpoint
    echo "2. Getting stages via relational endpoint:\n";
    $stages = $api->getAll("/api/contractors/projects/{$projectId}/stages?limit=50");

    echo "   Found " . count($stages) . " stages\n";

    if (!empty($stages)) {
        echo "   Example stages:\n";
        foreach (array_slice($stages, 0, 3) as $stage) {
            echo "   - ID: {$stage['id']}, Title: " . ($stage['title'] ?? 'N/A') . ", Status: " . ($stage['status'] ?? 'N/A') . "\n";
        }
    }
    echo "\n";

    // 3) Compare with direct endpoint
    echo "3. Comparison with direct endpoint:\n";
    $directStages = $api->searchAll("/api/contractors/project-stages", [
        'filters' => [
            [
                'type' => 'and',
                'field' => 'project_id',
                'value' => $projectId
            ]
        ],
        'limit' => 50
    ]);

    echo "   Stages via project-stages: " . count($directStages) . "\n";
    echo "   Stages via relation: " . count($stages) . "\n";

    if (count($stages) === count($directStages)) {
        echo "   ✓ Stage count matches\n";
    } else {
        echo "   ✗ Different stage count!\n";

        // Debug - check differences
        $directIds = array_column($directStages, 'id');
        $relationIds = array_column($stages, 'id');
        $missingInRelation = array_diff($directIds, $relationIds);
        $missingInDirect = array_diff($relationIds, $directIds);

        if (!empty($missingInRelation)) {
            echo "   Missing in relation (ID): " . implode(', ', $missingInRelation) . "\n";
        }
        if (!empty($missingInDirect)) {
            echo "   Additional in relation (ID): " . implode(', ', $missingInDirect) . "\n";
        }
    }
    echo "\n";

    // 4) Test creating stage via relational endpoint
    echo "4. Test creating stage via relational endpoint:\n";

    try {
        $newStageData = [
            'title' => 'Relation API Test Stage ' . date('H:i:s'),
            'status' => 'pending',
            'prepayment_percentage' => 15
        ];

        $created = $api->create("/api/contractors/projects/{$projectId}/stages", $newStageData);

        if ($created) {
            $newStageId = $created['id'];
            echo "   ✓ Created stage via relation ID: {$newStageId}\n";

            // Check if stage is visible via relation
            $updatedStages = $api->getAll("/api/contractors/projects/{$projectId}/stages?limit=50");
            if (count($updatedStages) > count($stages)) {
                echo "   ✓ New stage is visible via relation\n";
            }

            // Cleanup - delete test stage
            try {
                $deleteResp = $api->delete("/api/contractors/project-stages/{$newStageId}");
                if (in_array($deleteResp->getStatusCode(), [200, 204])) {
                    echo "   ✓ Cleanup: deleted test stage\n";
                }
            } catch (Exception $e) {
                echo "   ⚠ Cleanup failed (possibly no permissions): " . substr($e->getMessage(), 0, 50) . "...\n";
            }
        } else {
            echo "   ✗ Failed to create stage (create returned null)\n";
        }
    } catch (Exception $e) {
        echo "   ⚠ Error during creation via relation: " . $e->getMessage() . "...\n";
    }
    echo "\n";

    // 5) Test single stage via relation
    if (!empty($stages)) {
        $firstStage = $stages[0];
        $stageId = $firstStage['id'];

        echo "5. Test single stage via relation:\n";
        try {
            $singleStage = $api->record("/api/contractors/projects/{$projectId}/stages/{$stageId}");
            if ($singleStage) {
                echo "   ✓ Retrieved stage ID: {$stageId} via relation\n";
                echo "   Title: " . ($singleStage['title'] ?? 'N/A') . "\n";
                echo "   Status: " . ($singleStage['status'] ?? 'N/A') . "\n";
            } else {
                echo "   ✗ Failed to retrieve stage via relation\n";
            }
        } catch (Exception $e) {
            echo "   ⚠ Error retrieving single stage: " . substr($e->getMessage(), 0, 50) . "...\n";
        }
    }

    echo "\n✓ Relational API test completed!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    if (method_exists($e, 'getResponseBody')) {
        echo "Response: " . substr($e->getResponseBody(), 0, 200) . "...\n";
    }
    exit(1);
}
