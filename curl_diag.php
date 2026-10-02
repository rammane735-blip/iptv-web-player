<?php
$url = "http://s7099.x.pmline.cc:3082/plays/T2ZqdVRWZ3VKaXNUdmQwelROVkJPYitXMjZrN21NQ1hkWVZDZVg4NUFKaEZHS3FsSktZZklXMVgrRjZPdDM5dXZXUW9MdkFRYXU5RUdkZFY3ZE5mMHc9PQ==.ts";

$tests = [];

// Test A: With in-memory Cookie Engine
$chA = curl_init($url);
curl_setopt($chA, CURLOPT_RETURNTRANSFER, true);
curl_setopt($chA, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($chA, CURLOPT_MAXREDIRS, 5);
curl_setopt($chA, CURLOPT_USERAGENT, 'IBO Player');
curl_setopt($chA, CURLOPT_COOKIEFILE, ''); // Enables in-memory cookie storage
curl_setopt($chA, CURLOPT_COOKIEJAR, '');
curl_setopt($chA, CURLOPT_TIMEOUT, 6);
$dataA = curl_exec($chA);
$infoA = curl_getinfo($chA);
$errA = curl_error($chA);
curl_close($chA);

$tests['with_cookie_engine'] = [
    'http_code' => $infoA['http_code'],
    'effective_url' => $infoA['url'],
    'error' => $errA,
    'len' => strlen($dataA ?? ''),
    'hex' => bin2hex(substr($dataA ?? '', 0, 16))
];

// Test B: With VLC User Agent
$chB = curl_init($url);
curl_setopt($chB, CURLOPT_RETURNTRANSFER, true);
curl_setopt($chB, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($chB, CURLOPT_MAXREDIRS, 5);
curl_setopt($chB, CURLOPT_USERAGENT, 'VLC/3.0.18 LibVLC/3.0.18');
curl_setopt($chB, CURLOPT_COOKIEFILE, '');
curl_setopt($chB, CURLOPT_COOKIEJAR, '');
curl_setopt($chB, CURLOPT_TIMEOUT, 6);
$dataB = curl_exec($chB);
$infoB = curl_getinfo($chB);
$errB = curl_error($chB);
curl_close($chB);

$tests['vlc_with_cookies'] = [
    'http_code' => $infoB['http_code'],
    'effective_url' => $infoB['url'],
    'error' => $errB,
    'len' => strlen($dataB ?? ''),
    'hex' => bin2hex(substr($dataB ?? '', 0, 16))
];

// Test C: Second URL (FHD)
$urlFhd = "http://s7099.x.pmline.cc:3082/plays/UElia09hbjFWaEtpTitneHJPRXVFZ3JHdERKSWs2ZWdEbjRLcVlBMjNIU0tpREx1QmRUSW5HbE1ta2dsMkVwa2pjNHgvQ2plaUYzS3ZURk51Vkp2bFE9PQ==.ts";
$chC = curl_init($urlFhd);
curl_setopt($chC, CURLOPT_RETURNTRANSFER, true);
curl_setopt($chC, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($chC, CURLOPT_MAXREDIRS, 5);
curl_setopt($chC, CURLOPT_USERAGENT, 'IBO Player');
curl_setopt($chC, CURLOPT_COOKIEFILE, '');
curl_setopt($chC, CURLOPT_COOKIEJAR, '');
curl_setopt($chC, CURLOPT_TIMEOUT, 6);
$dataC = curl_exec($chC);
$infoC = curl_getinfo($chC);
$errC = curl_error($chC);
curl_close($chC);

$tests['fhd_with_cookies'] = [
    'http_code' => $infoC['http_code'],
    'effective_url' => $infoC['url'],
    'error' => $errC,
    'len' => strlen($dataC ?? ''),
    'hex' => bin2hex(substr($dataC ?? '', 0, 16))
];

header('Content-Type: application/json');
echo json_encode($tests, JSON_PRETTY_PRINT);
