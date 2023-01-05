<?php

require_once __DIR__ . '/includes/init.php';
global $log;
global $con;

$shop = $_ENV['SHOPIFY_SHOP_SLUG'];
$token = $_ENV['SHOPIFY_ACCESS_TOKEN'];
$query = array('Content-type' => 'application/json');

$log->info('orders:start');

$query = mysqli_query($con, sprintf('SELECT * FROM orders where shop="%s"', $shop));
while ($row = mysqli_fetch_assoc($query)) {
    mysqli_query($con, sprintf('DELETE FROM line_items WHERE order_id=%s', $row['id']));
    mysqli_query($con, sprintf('DELETE FROM orders WHERE id=%s', $row['id']));
}

$page_info = '';
$filters = array(
    'fields' => 'name,current_total_price,source_name,created_at,line_items',
    'limit' => 250,
    'status' => 'closed',
    'financial_status' => 'paid'
);

$i = 0;
while (true) {
    $orders = shopify_call(
        $token,
        $shop,
        '/admin/api/2022-04/orders.json',
        $filters
    );

    $headers = $orders['headers'];
    $orders = json_decode($orders['data'], TRUE);
    $log->info('orders:fetched', [count($orders['orders'])]);

    if (count($orders['orders']) <= 0) {
        $log->info('zero:orders', $orders);
    }

    foreach ($orders['orders'] as $order) {
        $query = mysqli_query(
            $con,
            sprintf(
                'INSERT INTO orders(shop, name, current_total_price, created_at) VALUES("%s", "%s", "%s", "%s")',
                mysqli_escape_string($con, $shop),
                mysqli_escape_string($con, $order['name']),
                $order['current_total_price'],
                $order['created_at']
            )
        );

        if (!$query) {
            $log->error(mysqli_error($con));
        }

        $order_id = mysqli_insert_id($con);
        foreach ($order['line_items'] as $line_item) {
            $query = mysqli_query(
                $con,
                sprintf(
                    'INSERT INTO line_items(order_id, name, price, source_name, created_at) VALUES(%d, "%s", %0.2f, "%s", "%s")',
                    $order_id,
                    mysqli_escape_string($con, $line_item['name']),
                    $line_item['price'],
                    $order['source_name'] ?? '',
                    $order['created_at']
                )
            );

            if (!$query) {
                $log->error(mysqli_error($con));
            }
        }
    }

    if (isset($headers['link'])) {
        if ($i == 0) {
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
            break;
        }

        $filters = array(
            'fields' => 'name,current_total_price,source_name,created_at,line_items',
            'limit' => 250,
            'page_info' => $matches[0][1],
            'rel' => 'next'
        );
    } else {
        break;
    }

    $i++;
    sleep(2);
}

$log->info('orders:end');