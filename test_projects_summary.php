<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");
$limit = 12;
$projects = $api->getAll("/api/contractors/projects?limit={$limit}");
if (empty($projects)) {
    echo "No projects retrieved.\n";
    exit(0);
}

echo "=== PROJECT LIST SUMMARY (limit={$limit}) ===\n";

$statusCounts = [];
$totalValue = 0;
$valueCount = 0;

foreach ($projects as $project) {
    $id = $project['id'] ?? '<unknown id>';
    $name = $project['project_name'] ?? $project['name'] ?? '<no name>';
    $status = $project['status'] ?? '<no status>';
    $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;

    $company = $project['company']['company_name'] ?? $project['company_name'] ?? '<no customer>';
    $contractAmount = isset($project['contract_amount']) && $project['contract_amount'] !== '' ? number_format((float)$project['contract_amount'], 2) : 'N/A';
    $currency = $project['contract_amount_currency'] ?? '';
    if ($contractAmount !== 'N/A') {
        $totalValue += (float)$project['contract_amount'];
        $valueCount++;
    }

    $dates = [];
    foreach (['start_date', 'contract_date', 'est_end_date'] as $field) {
        if (!empty($project[$field])) {
            $dates[] = "{$field}: {$project[$field]}";
        }
    }

    $progress = isset($project['job_progress']) && $project['job_progress'] !== ''
        ? $project['job_progress'] . "%" : 'N/A';

    echo "Project {$id}: {$name}\n";
    echo "  Status: {$status} | Customer: {$company}\n";
    echo "  Value: {$contractAmount}" . ($currency ? " {$currency}" : "") . " | Progress: {$progress}\n";
    echo "  Dates: " . (empty($dates) ? 'none provided' : implode(', ', $dates)) . "\n";
    echo "  Bid #: " . ($project['bid_no'] ?? '<unknown>') . "\n";
    echo str_repeat('-', 60) . "\n";
}

echo "Summary:\n";
foreach ($statusCounts as $status => $count) {
    echo "  {$status}: {$count}\n";
}
if ($valueCount > 0) {
    $average = $totalValue / $valueCount;
    echo "  Average contract value: " . number_format($average, 2) . "\n";
}
