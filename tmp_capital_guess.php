<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/api.php';
$api=new Api('https://demo.contractors.es','admin','admin','en');
try {
    $res=$api->getAll('/api/countries/177/capital');
    print_r($res);
} catch (Exception $e) {
    echo $e->getMessage();
}
