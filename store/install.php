<?php

require_once __DIR__ . '/includes/init.php';

$shop = $_ENV['SHOPIFY_SHOP_SLUG'];
$api_key = $_ENV['SHOPIFY_API_KEY'];
$scopes = $_ENV['SHOPIFY_APP_SCOPES'];
$redirect_uri = sprintf('%s/generate_token.php', $_ENV['SHOPIFY_APP_HOST_NAME']);

$install_url = sprintf('https://%s.myshopify.com/admin/oauth/authorize?client_id=%s&scope=%s&redirect_uri=%s', $shop, $api_key, $scopes, urlencode($redirect_uri));

header("Location: " . $install_url);
die();
