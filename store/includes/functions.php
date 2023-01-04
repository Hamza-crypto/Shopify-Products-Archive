<?php

function shopify_call($token, $shop, $api_endpoint, $query = array(), $method = 'GET', $request_headers = array()) {
    $url = sprintf('https://%s.myshopify.com%s', $shop, $api_endpoint);
    if (!is_null($query) && in_array($method, array('GET', 	'DELETE'))) $url = $url . "?" . http_build_query($query);

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_HEADER, TRUE);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($curl, CURLOPT_MAXREDIRS, 3);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);

    $request_headers[] = '';
    $headers[] = 'Content-Type: application/json';
    if (!is_null($token)) $request_headers[] = sprintf('X-Shopify-Access-Token: %s', $token);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $request_headers);

    if ($method != 'GET' && in_array($method, array('POST', 'PUT'))) {
        if (is_array($query)) $query = json_encode($query);
        curl_setopt ($curl, CURLOPT_POSTFIELDS, $query);
    }

    $response = curl_exec($curl);
    $error_number = curl_errno($curl);
    $error_message = curl_error($curl);
    curl_close($curl);

    if ($error_number) {
        return $error_message;
    } else {
        $response = preg_split("/\r\n\r\n|\n\n|\r\r/", $response, 2);
        $headers = array();
        $header_data = explode("\n",$response[0]);
        $headers['status'] = $header_data[0];
        array_shift($header_data);
        foreach($header_data as $part) {
            $h = explode(":", $part, 2);
            $headers[trim($h[0])] = trim($h[1]);
        }

        return array('headers' => $headers, 'data' => $response[1]);
    }
}

function str_btw($string, $start, $end){
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}
