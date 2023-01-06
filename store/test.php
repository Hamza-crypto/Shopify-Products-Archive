<?php

require_once __DIR__ . '/includes/init.php';
global $log;

$shop = $_ENV['SHOPIFY_SHOP_SLUG'];
$token = $_ENV['SHOPIFY_ACCESS_TOKEN'];


$a = 'abc';
$b = null;

if(isset($a, $b)){
    echo 'both are set';
}

