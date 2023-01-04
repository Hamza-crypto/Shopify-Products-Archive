<?php

require_once __DIR__ . '/includes/init.php';
global $log;
global $con;

$shop = $_ENV['SHOPIFY_SHOP_SLUG'];
$token = $_ENV['SHOPIFY_ACCESS_TOKEN'];
$query = array('Content-type' => 'application/json');

$log->info('orders:start');

$page_info = '';
$filters = [
    'limit' => 250,
];


if(isset($_POST['submit'])){
    $handle = fopen($_FILES['file']['tmp_name'], "r");
    $headers = fgetcsv($handle, 1000, ",");
    $sku = [];
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE)
    {
        $sku[] = $data[0];
    }
    fclose($handle);
    $sku = array_unique($sku);

    $products = shopify_call(
        $token,
        $shop,
        '/admin/api/2023-01/products.json',
        $filters
    );
    dump($products);
    $products = json_decode($products['data'], TRUE);
    $log->info('orders:fetched', [$products]);
    $log->info('orders:fetched', [count($products['products'])]);

    if (count($products['products']) <= 0) {
        $log->info('zero:orders', $products);
    }

    $count = 1;
    foreach ($products['products'] as $order) {
        dump($count . ' ------  order ID: ' .  $order['id']);
        $count++;
        foreach ($order['variants'] as $variant) {
            if(in_array($variant['sku'], $sku)) {
                echo 'SKU found';
            } else {
                echo 'SKU not found';
            }
            dump( $variant['sku']);
        }
    }

}

$log->info('orders:end');

?>

<form action="" method="post" enctype="multipart/form-data">
    Select CSV file to upload:
    <input type="file" name="file" id="fileToUpload">
    <input type="submit" value="Upload CSV" name="submit">
