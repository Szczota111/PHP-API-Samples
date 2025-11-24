<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/api.php';
$api=new Api('https://demo.contractors.es','admin','admin','en');
try {
    $countries=$api->getAll('/api/countries?include=capital&limit=5');
    print_r($countries[0] ?? []);
} catch (Exception $e) {
    echo $e->getMessage();
}
