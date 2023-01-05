<?php

require_once __DIR__ . '/includes/init.php';
global $log;
global $con;

$shop = $_ENV['SHOPIFY_SHOP_SLUG'];
$token = $_ENV['SHOPIFY_ACCESS_TOKEN'];
$query = array('Content-type' => 'application/json');

$log->info('orders:start');

$page_info = null;
if (isset($_GET['page_info'])) {
    $page_info = $_GET['page_info'];

    $filters = array(
        'limit' => 250,
        'page_info' => $page_info,
        'rel' => 'next'
    );
}
else{
    $filters = array(
        'limit' => 250,
        'status' => 'active',
    );
}



$body = [
    'product' => ['status' => 'draft']
];


if (isset($_POST['submit'])) {
    $handle = fopen($_FILES['file']['tmp_name'], "r");

    $upc = [];
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $upc[] = $data[0];
    }
    fclose($handle);
    $upc = array_unique($upc);
    $i = 0;
    while (true) {
        $products = shopify_call(
            $token,
            $shop,
            '/admin/api/2023-01/products.json',
            $filters
        );

        $headers = $products['headers'];
        $products = json_decode($products['data'], TRUE);
//        $log->info('orders:fetched', [$products]);
//        $log->info('orders:fetched', [count($products['products'])]);

        if (count($products['products']) <= 0) {
            $log->info('zero:orders', $products);
        }


        $count = 0;
        $key = 'barcode';
        foreach ($products['products'] as $product) {
            foreach ($product['variants'] as $variant) {

                if (isset($variant[$key])) {

                    if (in_array($variant[$key], $upc)) {
                        //Make this product archive
                        $status = shopify_call(
                            $token,
                            $shop,
                            sprintf('/admin/api/2023-01/products/%s.json', $product['id']),
                            $body,
                            'PUT'
                        );
                        echo sprintf('Product ID: %s, UPC: %s</br>', $product['id'], $variant[$key]);
                        $count++;
                    }
                } else {
                    // Delete this product
//                echo

                }

            }
        }
        if (isset($headers['link'])) {
            if (is_null($page_info) && $i == 0) {
                $expression = preg_match_all(
                    '/&page_info=(.*?)>; rel="next"/m',
                    $headers['link'],
                    $matches,
                    PREG_SET_ORDER,
                    0
                );
            } else {
                $expression = preg_match_all(
                    '/rel="previous".*&page_info=(.*?)>; rel="next"/m',
                    $headers['link'],
                    $matches,
                    PREG_SET_ORDER,
                    0
                );
            }

            if (!isset($matches[0])) {
                dump($headers);
                echo "No more products and breaking the loop";
                break;
            }

            $filters = array(
                'limit' => 250,
                'page_info' => $matches[0][1],
                'rel' => 'next'
            );
            $log->info('Pagination', [sprintf('Count: %s, Next Page: %s</br>', $i, $matches[0][1])]);
        } else {
            dump($headers);
            echo "No more products and breaking the loop";
            break;
        }

        $i++;

        if($i == 40) {
            echo "Breaking the loop, You can continue from next page by changing the page_info";
            dump(sprintf('Count: %s', $i));
            dump($matches[0][1]);
            break;
        }


        sleep(2);
    }

    echo "Total products archived: " . $count;
}
$log->info('orders:end');

?>

<form action="" method="post" enctype="multipart/form-data">
    Select CSV file to upload:
    <input type="file" name="file" id="fileToUpload">
    <input type="submit" value="Upload CSV" name="submit">
