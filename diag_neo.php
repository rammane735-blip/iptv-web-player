<?php
$url = "http://tv.business-cloud-4.ru/live/b48d9f42deab/9f8c5b1d06/285697.ts";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'IPTVSmartersPro');
curl_setopt($ch, CURLOPT_TIMEOUT, 6);
$response = curl_exec($ch);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$header = substr($response, 0, $header_size);
$body = substr($response, $header_size);
$info = curl_getinfo($ch);
curl_close($ch);

header('Content-Type: application/json');
echo json_encode([
    'http_code' => $info['http_code'],
    'header' => $header,
    'body' => substr($body, 0, 500)
], JSON_PRETTY_PRINT);
