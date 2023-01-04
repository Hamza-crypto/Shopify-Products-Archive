<?php

require_once __DIR__ . '/includes/init.php';

$api_key = $_ENV['SHOPIFY_API_KEY'];
$shared_secret = $_ENV['SHOPIFY_API_SECRET'];
$params = $_GET;
$hmac = $_GET['hmac'];

$params = array_diff_key($params, array('hmac' => ''));
ksort($params);

$computed_hmac = hash_hmac('sha256', http_build_query($params), $shared_secret);

if (hash_equals($hmac, $computed_hmac)) {
    $query = array(
        'client_id' => $api_key,
        'client_secret' => $shared_secret,
        'code' => $params['code']
    );

    $access_token_url = sprintf('https://%s/admin/oauth/access_token', $params['shop']);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $access_token_url);
    curl_setopt($ch, CURLOPT_POST, count($query));
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($query));
    $result = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($result, true);
    $access_token = $result['access_token'];

    dump($access_token);
} else {
    dd('This request is not from Shopify!');
}
