<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/api.php';
$api=new Api('https://demo.contractors.es','admin','admin','en');
$countries=$api->getAll('/api/countries?limit=5');
print_r($countries[0] ?? []);
