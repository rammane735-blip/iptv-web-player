<?php
$url = $_GET['url'] ?? "http://s7099.x.pmline.cc:3082/plays/T2ZqdVRWZ3VKaXNUdmQwelROVkJPYitXMjZrN21NQ1hkWVZDZVg4NUFKaEZHS3FsSktZZklXMVgrRjZPdDM5dXZXUW9MdkFRYXU5RUdkZFY3ZE5mMHc9PQ==.ts";
$client_ip = $_GET['ip'] ?? '105.155.10.20';

// Test 1: standard curl
function test_curl($u, $headers = []) {
    $ch = curl_init($u);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // check first hop
    curl_setopt($ch, CURLOPT_USERAGENT, 'IBO Player');
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    $data = curl_exec($ch);
    $err = curl_error($ch);
    $errno = curl_errno($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    return [
        'errno' => $errno,
        'error' => $err,
        'http_code' => $info['http_code'],
        'redirect_url' => $info['redirect_url'] ?? '',
        'primary_ip' => $info['primary_ip'],
        'len' => strlen($data ?? '')
    ];
}

$hop1 = test_curl($url);
$loc1 = $hop1['redirect_url'];

$hop2 = $loc1 ? test_curl($loc1) : null;
$loc2 = $hop2 ? $hop2['redirect_url'] : null;

$hop3 = $loc2 ? test_curl($loc2) : null;
$loc3 = $hop3 ? $hop3['redirect_url'] : null;

// Test with X-Forwarded-For
$headers_ip = [
    "X-Forwarded-For: $client_ip",
    "Client-IP: $client_ip",
    "X-Real-IP: $client_ip"
];
$hop3_ip = $loc2 ? test_curl($loc2, $headers_ip) : null;

header('Content-Type: application/json');
echo json_encode([
    'hop1' => $hop1,
    'hop2' => $hop2,
    'hop3' => $hop3,
    'hop3_with_ip' => $hop3_ip
], JSON_PRETTY_PRINT);
