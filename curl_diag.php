<?php
$url = "http://s7099.x.pmline.cc:3082/live/532275452803140/23072187/304.ts";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'IBO Player');
curl_setopt($ch, CURLOPT_TIMEOUT, 8);
$data = curl_exec($ch);
$err = curl_error($ch);
$errno = curl_errno($ch);
$info = curl_getinfo($ch);
curl_close($ch);

header('Content-Type: application/json');
echo json_encode([
    'errno' => $errno,
    'error' => $err,
    'http_code' => $info['http_code'],
    'primary_ip' => $info['primary_ip'],
    'primary_port' => $info['primary_port'],
    'effective_url' => $info['url'],
    'data_len' => strlen($data ?? ''),
    'data_hex' => bin2hex(substr($data ?? '', 0, 16))
]);
