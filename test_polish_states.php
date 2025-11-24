<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "pl");
$countries = $api->getAll('/api/countries');

$poland = null;
foreach ($countries as $country) {
    if (isset($country['code']) && $country['code'] === 'PL') {
        $poland = $country;
        break;
    }
}

if ($poland === null) {
    throw new RuntimeException('Nie udało się znaleźć wpisu dla Polski.');
}

$states = $api->getAll('/api/countries/' . $poland['id'] . '/states');
echo "Polskie województwa (z językiem nagłówka ustawionym na polski):\n";
foreach ($states as $state) {
    $name = $state['name'] ?? '<bez nazwy>';
    $id = $state['id'] ?? '<brak id>';
    echo "- {$name} (id: {$id})\n";
}
