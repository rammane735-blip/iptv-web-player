<?php
$url = "http://tv.business-cloud-4.ru/live/b48d9f42deab/9f8c5b1d06/285697.ts";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_PROXY, '80.71.232.83:8082');
curl_setopt($ch, CURLOPT_USERAGENT, 'IPTVSmartersPro');
curl_setopt($ch, CURLOPT_TIMEOUT, 6);

$received = '';
curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $chunk) use (&$received) {
    $received .= $chunk;
    if (strlen($received) >= 1880) {
        return 0; // stop after receiving first few packets
    }
    return strlen($chunk);
});

curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

header('Content-Type: application/json');
echo json_encode([
    'http_code' => $info['http_code'],
    'effective_url' => $info['url'],
    'data_len' => strlen($received),
    'hex' => bin2hex(substr($received, 0, 16))
]);
