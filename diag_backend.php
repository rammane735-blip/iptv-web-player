<?php
$backend_url = "http://0tbzygsb01329613r.s15.ip3-neo3.com:80/live/play/TlhsSGQyOXFTR2xUTVN0WmNrcFBMMEpWY0d4T1JEUkphVlZaVkM5alRXVlJaVVJwTTFoalVHUXpRVDA9/285697";
$ch = curl_init($backend_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'IPTVSmartersPro');
curl_setopt($ch, CURLOPT_TIMEOUT, 6);
$data = curl_exec($ch);
$info = curl_getinfo($ch);
$err = curl_error($ch);
$errno = curl_errno($ch);
curl_close($ch);

header('Content-Type: application/json');
echo json_encode([
    'http_code' => $info['http_code'],
    'data_len' => strlen($data ?? ''),
    'errno' => $errno,
    'error' => $err,
    'hex' => bin2hex(substr($data ?? '', 0, 16))
]);
