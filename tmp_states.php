<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/api.php';
$api=new Api('https://demo.contractors.es','admin','admin','en');
$states=$api->getAll('/api/countries/177/states?limit=5');
print_r($states[0] ?? []);
