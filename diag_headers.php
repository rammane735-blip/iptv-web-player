<?php
$url = "http://tv.business-cloud-4.ru/live/b48d9f42deab/9f8c5b1d06/285697.ts";

$test_headers = [
    'plain' => [],
    'with_morocco_ip' => [
        'X-Forwarded-For: 105.155.10.20',
        'X-Real-IP: 105.155.10.20',
        'Client-IP: 105.155.10.20',
        'CF-Connecting-IP: 105.155.10.20'
    ],
    'with_host_header' => [
        'Host: tv.business-cloud-4.ru',
        'X-Forwarded-For: 105.155.10.20'
    ]
];

$results = [];
foreach ($test_headers as $label => $headers) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'IPTVSmartersPro');
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    $data = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    $results[$label] = [
        'http_code' => $info['http_code'],
        'len' => strlen($data ?? ''),
        'hex' => bin2hex(substr($data ?? '', 0, 16))
    ];
}

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT);
