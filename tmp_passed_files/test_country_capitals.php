<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/api.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");
$countries = $api->getAll('/api/countries?limit=300');
if (empty($countries)) {
    echo "No countries retrieved from Contractors API.\n";
    exit(0);
}

$capitalMap = [];
$geoClient = new Client([
    'base_uri' => 'https://restcountries.com',
    'timeout' => 15,
]);

try {
    $response = $geoClient->get('/v3.1/all?fields=cca2,capital');
    $raw = json_decode($response->getBody()->getContents(), true);
    foreach ($raw as $country) {
        $code = strtoupper($country['cca2'] ?? '');
        $capitalMap[$code] = $country['capital'] ?? [];
    }
} catch (RequestException $e) {
    echo "Failed to retrieve capitals from restcountries.com: " . $e->getMessage() . "\n";
    echo "Will print country list without capital information.\n";
}

usort($countries, fn($a, $b) => strcmp($a['name'] ?? '', $b['name'] ?? ''));

$found = 0;
$missing = 0;

echo "=== COUNTRY CAPITALS ===\n";
foreach ($countries as $country) {
    $code = strtoupper($country['code'] ?? '');
    $name = $country['name'] ?? '<no name>';
    $capitalInfo = isset($capitalMap[$code]) && !empty($capitalMap[$code])
        ? implode(', ', $capitalMap[$code])
        : 'capital data unavailable';
    if (strpos($capitalInfo, 'capital data unavailable') === false) {
        $found++;
    } else {
        $missing++;
    }
    echo "{$name} ({$code}): {$capitalInfo}\n";
}

echo "\nSummary: capitals found for {$found} countries, missing for {$missing}.\n";
