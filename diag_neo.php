<?php
$url = "http://tv.business-cloud-4.ru/live/b48d9f42deab/9f8c5b1d06/285697.ts";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
curl_setopt($ch, CURLOPT_USERAGENT, 'IPTVSmartersPro');
curl_setopt($ch, CURLOPT_TIMEOUT, 8);
$data = curl_exec($ch);
$info = curl_getinfo($ch);
$err = curl_error($ch);
$errno = curl_errno($ch);
curl_close($ch);

header('Content-Type: application/json');
echo json_encode([
    'http_code' => $info['http_code'],
    'effective_url' => $info['url'] ?? '',
    'errno' => $errno,
    'error' => $err,
    'data_len' => strlen($data ?? ''),
    'data_hex' => bin2hex(substr($data ?? '', 0, 16))
], JSON_PRETTY_PRINT);
