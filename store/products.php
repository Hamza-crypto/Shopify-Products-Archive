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

//check multiple variables inside isset
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

<!doctype html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">

    <title>Hello, world!</title>
</head>
<body>

<div class="container mt-4">

    <div class="alert alert-primary" role="alert">
        <h1>Shopify API</h1>
    </div>
    <div class="alert alert-secondary" role="alert">
        Archive the products by UPC / SKU
    </div>
    <form action="" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <input type="file" class="form-control-file" name="file">
            <br>
            <input type="submit" class="btn btn-primary flex-right" value="Upload CSV" name="submit">
        </div>
    </form>

<!--    <div class="alert alert-success" role="alert">-->
<!--        Delete Products which do not have UPC / SKU-->
<!--    </div>-->
<!--    <form action="" method="post">-->
<!--        <div class="form-group">-->
<!--            <input type="submit" class="btn btn-danger flex-right" value="Delete products" name="no_upc">-->
<!--        </div>-->
<!--    </form>-->
<!---->
<!--    <div class="alert alert-success" role="alert">-->
<!--        Delete Products which do not have images-->
<!--    </div>-->
<!--    <form action="" method="post">-->
<!--        <div class="form-group">-->
<!--            <input type="submit" class="btn btn-warning flex-right" value="Delete products" name="no_img">-->
<!--        </div>-->
<!--    </form>-->
</div>

<!-- Optional JavaScript -->
<!-- jQuery first, then Popper.js, then Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.14.7/dist/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
</body>
</html>


