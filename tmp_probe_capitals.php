<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/api.php';
$api = new Api('https://demo.contractors.es','admin','admin','en');
try {
    $caps = $api->getAll('/api/capitals?limit=5');
    print_r($caps[0] ?? []);
} catch (Exception $e) {
    echo 'error ' . $e->getMessage();
}
