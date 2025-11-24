<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/api.php';
$api=new Api('https://demo.contractors.es','admin','admin','en');
try {
    $country=$api->record("/api/countries/177?include=capital");
    print_r($country);
} catch (Exception $e) {
    echo 'error: ' . $e->getMessage();
}
