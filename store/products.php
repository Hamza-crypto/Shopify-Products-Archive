<?php

require_once __DIR__ . '/includes/init.php';
global $log;

$shop = $_ENV['SHOPIFY_SHOP_SLUG'];
$token = $_ENV['SHOPIFY_ACCESS_TOKEN'];
$key = 'barcode';

$log->info('orders:start');

$page_info = null;
if (isset($_GET['page_info'])) {
    $page_info = $_GET['page_info'];

    $filters = array(
        'limit' => 250,
        'page_info' => $page_info,
        'rel' => 'next'
    );
} else {
    $filters = array(
        'limit' => 250,
        'status' => 'active',
    );
}


$upc = [];
//check multiple variables inside isset
if (isset($_POST['submit'])) {
    $handle = fopen($_FILES['file']['tmp_name'], "r");
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $upc[] = $data[0];
    }
    fclose($handle);
    $upc = array_unique($upc);
    parse_api_response();
} else {
    unset($filters['status']);
    if (isset($_POST['no_upc'])) {
        parse_api_response();
    }
    if (isset($_POST['no_sku'])) {
        $key = 'sku';
        parse_api_response();
    }

    if (isset($_POST['no_img'])) {
        parse_api_response();
    }
}


function parse_api_response()
{
    global $token;
    global $shop;
    global $log;
    global $page_info;
    global $upc;
    global $filters;
    global $key;

    $body = ['product' => ['status' => 'draft']];

    $i = 0;
    $product_count = 0;
    while (true) {
        $products = shopify_call(
            $token,
            $shop,
            '/admin/api/2023-01/products.json',
            $filters
        );

        $headers = $products['headers'];
        $products = json_decode($products['data'], TRUE);

        if (count($products['products']) <= 0) {
            $log->info('zero:orders', $products);
        }


        foreach ($products['products'] as $product) {
            if (isset($_POST['no_img'])) {
                dump('no_img');
                if (count($product['images']) < 1) {
                    shopify_call(
                        $token,
                        $shop,
                        sprintf('/admin/api/2023-01/products/%s.json', '7901973938400'),
                        [],
                        'DELETE'
                    );
                    echo sprintf('Product ID: %s Deleted</br>', $product['id']);
                }

            } else {
                foreach ($product['variants'] as $variant) {

                    if (isset($_POST['no_sku']) || isset($_POST['no_upc'])) {
                        dump('no_sku, no upc');
                        if (!isset($variant[$key])) {
                            // Delete this product
                            echo sprintf('Product ID: %s Deleted</br>', $product['id']);
                            shopify_call(
                                $token,
                                $shop,
                                sprintf('/admin/api/2023-01/products/%s.json', '7901973938400'),
                                [],
                                'DELETE'
                            );
                        }
                    }

                    if (isset($variant[$key]) && in_array($variant[$key], $upc)) {
                        dump('in_array');
                        //Make this product archive
                        shopify_call(
                            $token,
                            $shop,
                            sprintf('/admin/api/2023-01/products/%s.json', $product['id']),
                            $body,
                            'PUT'
                        );
                        echo sprintf('Product ID: %s, UPC: %s</br>', $product['id'], $variant[$key]);
                        $product_count++;
                    }

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

        if ($i == 40) {
            echo "Breaking the loop, You can continue from next page by changing the page_info";
            dump(sprintf('Total pagination: %s', $i));
            dump($matches[0][1]);
            break;
        }


        sleep(2);

    }
    echo "Total products archived: " . $product_count;
}

?>

<!doctype html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css"
          integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">

    <title>Shopify API</title>
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
            <input type="file" class="form-control-file" name="file" required>
            <br>
            <input type="submit" class="btn btn-primary flex-right" value="Upload CSV" name="submit">
        </div>
    </form>

    <div class="alert alert-success" role="alert">
        Delete Products which do not have UPC / SKU
    </div>
    <form action="" method="post">
        <div class="form-group">
            <input type="submit" class="btn btn-danger flex-right" value="Delete products without UPC" name="no_upc">
            <input type="submit" class="btn btn-danger flex-right" value="Delete products without SKU" name="no_sku">
        </div>
    </form>
    <!---->
    <div class="alert alert-success" role="alert">
        Delete Products which do not have images
    </div>
    <form action="" method="post">
        <div class="form-group">
            <input type="submit" class="btn btn-warning flex-right" value="Delete products" name="no_img">
        </div>
    </form>
</div>

<!-- Optional JavaScript -->
<!-- jQuery first, then Popper.js, then Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"
        integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo"
        crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.14.7/dist/umd/popper.min.js"
        integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1"
        crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/js/bootstrap.min.js"
        integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM"
        crossorigin="anonymous"></script>
</body>
</html>