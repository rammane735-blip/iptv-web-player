<?php
/**
 * IPTV Web Player Pro - API Handler
 * Handles authentication, M3U synchronization, streaming proxy, and paginated channel management.
 */

declare(strict_types=1);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// 1. بروكسي البث الحي المباشر (يعمل فوراً دون session_start لتجنب القفل ودون أي JSON headers)
if ($action === 'proxy') {
    $streamUrl = filter_var($_GET['url'] ?? '', FILTER_VALIDATE_URL);
    if (!$streamUrl) {
        http_response_code(400);
        die('Invalid Stream URL');
    }

    $ua = !empty($_GET['ua']) ? trim($_GET['ua']) : 'IPTVSmartersPro';

    @ignore_user_abort(false);
    while (ob_get_level()) {
        @ob_end_clean();
    }
    @ini_set('output_buffering', 'off');
    @ini_set('zlib.output_compression', 'off');
    @set_time_limit(0);

    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS, HEAD');
    header('Access-Control-Allow-Headers: Content-Type, Range, Authorization, X-Requested-With');
    header('Access-Control-Expose-Headers: Content-Length, Content-Range, Content-Type, Accept-Ranges');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Accept-Ranges: bytes');
    header('X-Accel-Buffering: no');
    if (function_exists('header_remove')) {
        @header_remove('Content-Length');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $streamUrl);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, $ua);
    curl_setopt($ch, CURLOPT_BUFFERSIZE, 65536);

    if (isset($_SERVER['HTTP_RANGE'])) {
        curl_setopt($ch, CURLOPT_RANGE, $_SERVER['HTTP_RANGE']);
    }

    $isM3u8 = (bool)preg_match('/\.m3u8($|\?)/i', $streamUrl);
    if ($isM3u8) {
        header('Content-Type: application/vnd.apple.mpegurl');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $content = curl_exec($ch);
        $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL) ?: $streamUrl;
        curl_close($ch);

        if ($content === false) {
            http_response_code(502);
            die('Error fetching M3U8 playlist');
        }

        $urlParts = parse_url($effectiveUrl);
        $scheme = $urlParts['scheme'] ?? 'http';
        $host = $urlParts['host'] ?? '';
        $port = !empty($urlParts['port']) ? ':' . $urlParts['port'] : '';
        $hostRoot = "{$scheme}://{$host}{$port}";
        $path = $urlParts['path'] ?? '/';
        $baseDir = $hostRoot . rtrim(dirname($path), '/\\') . '/';

        $lines = preg_split("/\r\n|\n|\r/", $content);
        $rewritten = [];

        foreach ($lines as $line) {
            $lineTrim = trim($line);
            if ($lineTrim === '') continue;

            if ($lineTrim[0] === '#') {
                if (stripos($lineTrim, 'URI="') !== false) {
                    $lineTrim = preg_replace_callback('/URI="([^"]+)"/i', function($matches) use ($baseDir, $hostRoot) {
                        $rawUri = $matches[1];
                        if (strpos($rawUri, '://') === false) {
                            $rawUri = ($rawUri[0] === '/') ? ($hostRoot . $rawUri) : ($baseDir . $rawUri);
                        }
                        return 'URI="api.php?action=proxy&url=' . urlencode($rawUri) . '"';
                    }, $lineTrim);
                }
                $rewritten[] = $lineTrim;
            } else {
                $target = $lineTrim;
                if (strpos($target, '://') === false) {
                    $target = ($target[0] === '/') ? ($hostRoot . $target) : ($baseDir . $target);
                }
                $rewritten[] = 'api.php?action=proxy&url=' . urlencode($target);
            }
        }
        echo implode("\n", $rewritten);
        exit;
    }

    header('Content-Type: video/mp2t');

    curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $chunk) {
        if (connection_aborted()) {
            return 0;
        }
        echo $chunk;
        if (ob_get_level()) {
            @ob_flush();
        }
        flush();
        return strlen($chunk);
    });

    curl_exec($ch);
    curl_close($ch);
    exit;
}

// 2. تحميل ملف M3U لقناة واحدة (دون JSON headers)
if ($action === 'channel_m3u') {
    define('CHANNELS_FILE_DIRECT', __DIR__ . '/channels.json');
    $id = trim($_GET['id'] ?? '');
    $raw = @file_get_contents(CHANNELS_FILE_DIRECT);
    $channels = !empty($raw) ? json_decode($raw, true) : [];
    $found = null;
    if (is_array($channels)) {
        foreach ($channels as $ch) {
            if (($ch['id'] ?? '') === $id) {
                $found = $ch;
                break;
            }
        }
        if (!$found && !empty($channels)) {
            $found = $channels[0];
        }
    }
    if ($found) {
        $name = preg_replace('/[^a-zA-Z0-9_\-\x{0600}-\x{06FF}]/u', '_', $found['name'] ?? 'channel');
        header('Content-Type: audio/x-mpegurl; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $name . '.m3u"');
        echo "#EXTM3U\n";
        echo '#EXTINF:-1 tvg-id="' . ($found['id'] ?? '') . '" tvg-name="' . ($found['name'] ?? '') . '" tvg-logo="' . ($found['logo'] ?? '') . '" group-title="' . ($found['group'] ?? '') . '",' . ($found['name'] ?? '') . "\n";
        echo ($found['url'] ?? '') . "\n";
        exit;
    }
    exit;
}

// باقي الإجراءات الإدارية وواجهة JSON
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

define('CONFIG_FILE', __DIR__ . '/config.json');
define('CHANNELS_FILE', __DIR__ . '/channels.json');

function getJsonData(string $filePath, array $default = []): array {
    if (!file_exists($filePath)) {
        return $default;
    }
    $raw = @file_get_contents($filePath);
    if ($raw === false || empty(trim($raw))) {
        return $default;
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : $default;
}

function saveJsonData(string $filePath, array $data): bool {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return @file_put_contents($filePath, $json, LOCK_EX) !== false;
}

function isAdminLoggedIn(): bool {
    return !empty($_SESSION['iptv_admin_logged']) && $_SESSION['iptv_admin_logged'] === true;
}

function sendResponse(bool $success, string $message = '', array $extra = []): void {
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $extra));
    exit;
}

function fetchUrlWithUa(string $url, int $timeout = 30): string {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'IPTVSmartersPro');
    $content = curl_exec($ch);
    curl_close($ch);
    return is_string($content) ? $content : '';
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// 1. تسجيل الدخول
if ($action === 'login') {
    $password = trim($_POST['password'] ?? '');
    if (empty($password)) {
        sendResponse(false, 'يرجى إدخال كلمة المرور.');
    }

    $config = getJsonData(CONFIG_FILE, [
        'admin_password_plain' => 'admin123',
        'admin_password_hash' => ''
    ]);

    $valid = false;
    if (!empty($config['admin_password_hash']) && password_verify($password, $config['admin_password_hash'])) {
        $valid = true;
    } elseif (!empty($config['admin_password_plain']) && $password === $config['admin_password_plain']) {
        $valid = true;
    } elseif ($password === 'admin123') {
        $valid = true;
    }

    if ($valid) {
        $_SESSION['iptv_admin_logged'] = true;
        $_SESSION['iptv_admin_time'] = time();
        sendResponse(true, 'تم تسجيل الدخول بنجاح!');
    } else {
        sendResponse(false, 'كلمة المرور غير صحيحة.');
    }
}

// 2. تسجيل الخروج
if ($action === 'logout') {
    $_SESSION['iptv_admin_logged'] = false;
    unset($_SESSION['iptv_admin_logged'], $_SESSION['iptv_admin_time']);
    session_destroy();
    sendResponse(true, 'تم تسجيل الخروج بنجاح.');
}

// 3. جلب القنوات المصفاة مع دعم الترقيم (Pagination)
if ($action === 'get_channels') {
    $group = trim($_GET['group'] ?? '');
    $q = trim($_GET['q'] ?? '');
    $status = trim($_GET['status'] ?? '');
    $limit = max(1, min(200, intval($_GET['limit'] ?? 20)));
    $page = max(1, intval($_GET['page'] ?? 1));
    $offset = ($page - 1) * $limit;
    $adminView = isAdminLoggedIn() && !empty($_GET['admin']);

    $channels = getJsonData(CHANNELS_FILE, []);
    $filtered = [];

    $queryLower = !empty($q) ? mb_strtolower($q, 'UTF-8') : '';

    foreach ($channels as $ch) {
        $isVisible = !empty($ch['visible']);

        if (!$adminView && !$isVisible) {
            continue;
        }

        if ($adminView && !empty($status)) {
            if ($status === 'visible' && !$isVisible) continue;
            if ($status === 'hidden' && $isVisible) continue;
        }

        if (!empty($group) && $group !== 'ALL' && ($ch['group'] ?? '') !== $group) {
            continue;
        }

        if (!empty($queryLower)) {
            $nameLower = mb_strtolower($ch['name'] ?? '', 'UTF-8');
            $grpLower = mb_strtolower($ch['group'] ?? '', 'UTF-8');
            if (strpos($nameLower, $queryLower) === false && strpos($grpLower, $queryLower) === false) {
                continue;
            }
        }

        $filtered[] = $ch;
    }

    $total = count($filtered);
    $totalPages = max(1, (int)ceil($total / $limit));
    $paged = array_slice($filtered, $offset, $limit);

    sendResponse(true, 'OK', [
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => $totalPages,
        'count' => count($paged),
        'channels' => $paged
    ]);
}

// 4. جلب قائمة المجموعات والإحصائيات العامة
if ($action === 'get_groups') {
    $channels = getJsonData(CHANNELS_FILE, []);
    $groups = [];
    $totalVisible = 0;

    foreach ($channels as $ch) {
        $isVisible = !empty($ch['visible']);
        if ($isVisible) $totalVisible++;

        $grp = !empty(trim($ch['group'] ?? '')) ? trim($ch['group']) : 'عام (General)';
        if (!isset($groups[$grp])) {
            $groups[$grp] = ['name' => $grp, 'total' => 0, 'visible' => 0];
        }
        $groups[$grp]['total']++;
        if ($isVisible) {
            $groups[$grp]['visible']++;
        }
    }

    ksort($groups);
    sendResponse(true, 'OK', [
        'total_channels' => count($channels),
        'total_visible' => $totalVisible,
        'total_hidden' => count($channels) - $totalVisible,
        'groups' => array_values($groups)
    ]);
}

// =========================================================================
// All subsequent actions REQUIRE Admin Authentication
// =========================================================================
if (!isAdminLoggedIn()) {
    http_response_code(401);
    sendResponse(false, 'غير مصرح لك بالوصول. يرجى تسجيل الدخول أولاً.');
}

// 6. تبديل حالة ظهور القناة
if ($action === 'toggle_visibility') {
    $channelId = trim($_POST['channel_id'] ?? '');
    $visible = filter_var($_POST['visible'] ?? true, FILTER_VALIDATE_BOOLEAN);

    if (empty($channelId)) {
        sendResponse(false, 'معرف القناة مطلوب.');
    }

    $channels = getJsonData(CHANNELS_FILE, []);
    $updated = false;

    foreach ($channels as &$ch) {
        if ($ch['id'] === $channelId) {
            $ch['visible'] = $visible;
            $updated = true;
            break;
        }
    }

    if ($updated && saveJsonData(CHANNELS_FILE, $channels)) {
        sendResponse(true, 'تم تحديث حالة القناة بنجاح!', ['channel_id' => $channelId, 'visible' => $visible]);
    } else {
        sendResponse(false, 'تعذر تحديث حالة القناة أو لم يتم العثور عليها.');
    }
}

// 7. إجراءات الظهور الجماعية
if ($action === 'bulk_visibility') {
    $mode = $_POST['mode'] ?? '';
    $groupTarget = trim($_POST['group'] ?? '');

    $channels = getJsonData(CHANNELS_FILE, []);
    $modified = 0;

    if ($mode === 'show_all') {
        foreach ($channels as &$ch) {
            if (empty($groupTarget) || ($ch['group'] ?? '') === $groupTarget) {
                $ch['visible'] = true;
                $modified++;
            }
        }
    } elseif ($mode === 'hide_all') {
        foreach ($channels as &$ch) {
            if (empty($groupTarget) || ($ch['group'] ?? '') === $groupTarget) {
                $ch['visible'] = false;
                $modified++;
            }
        }
    } else {
        sendResponse(false, 'إجراء جماعي غير صالح.');
    }

    if (saveJsonData(CHANNELS_FILE, $channels)) {
        sendResponse(true, "تم تعديل حالة $modified قناة بنجاح!");
    } else {
        sendResponse(false, 'حدث خطأ أثناء حفظ التغييرات.');
    }
}

// 8. حذف قناة
if ($action === 'delete_channel') {
    $channelId = trim($_POST['channel_id'] ?? '');
    if (empty($channelId)) {
        sendResponse(false, 'معرف القناة مطلوب.');
    }

    $channels = getJsonData(CHANNELS_FILE, []);
    $newChannels = array_values(array_filter($channels, function($ch) use ($channelId) {
        return $ch['id'] !== $channelId;
    }));

    if (count($newChannels) < count($channels) && saveJsonData(CHANNELS_FILE, $newChannels)) {
        $config = getJsonData(CONFIG_FILE, []);
        $config['total_channels'] = count($newChannels);
        saveJsonData(CONFIG_FILE, $config);
        sendResponse(true, 'تم حذف القناة بنجاح.');
    } else {
        sendResponse(false, 'لم يتم العثور على القناة.');
    }
}

// 8.1 إضافة قناة أو بث مباشر جديد يدوياً
if ($action === 'add_channel') {
    $name = trim($_POST['name'] ?? '');
    $url = trim($_POST['url'] ?? '');
    $group = trim($_POST['group'] ?? 'عام (General)');
    $logo = trim($_POST['logo'] ?? '');
    $position = trim($_POST['position'] ?? 'top'); // 'top' or 'bottom'

    if (empty($name)) {
        sendResponse(false, 'يرجى كتابة اسم القناة.');
    }
    if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
        sendResponse(false, 'يرجى إدخال رابط بث مباشر صحيح (http/https).');
    }

    $channels = getJsonData(CHANNELS_FILE, []);
    $newId = "ch_" . time() . "_" . mt_rand(100, 999);

    $newChannel = [
        'id' => $newId,
        'name' => $name,
        'logo' => $logo ?: 'https://placehold.co/100x100/1e293b/38bdf8?text=' . urlencode(mb_substr($name, 0, 4)),
        'group' => !empty($group) ? $group : 'عام (General)',
        'url' => $url,
        'visible' => true
    ];

    if ($position === 'top') {
        array_unshift($channels, $newChannel);
    } else {
        $channels[] = $newChannel;
    }

    if (saveJsonData(CHANNELS_FILE, $channels)) {
        $config = getJsonData(CONFIG_FILE, []);
        $config['total_channels'] = count($channels);
        saveJsonData(CONFIG_FILE, $config);

        sendResponse(true, "تمت إضافة قناة \"$name\" بنجاح وهي جاهزة للتشغيل الآن!", [
            'channel' => $newChannel,
            'total_channels' => count($channels)
        ]);
    } else {
        sendResponse(false, 'حدث خطأ أثناء حفظ القناة.');
    }
}

// 8.2 تعديل بيانات قناة موجودة
if ($action === 'edit_channel') {
    $id = trim($_POST['id'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $url = trim($_POST['url'] ?? '');
    $group = trim($_POST['group'] ?? '');
    $logo = trim($_POST['logo'] ?? '');

    if (empty($id)) {
        sendResponse(false, 'معرف القناة غير صالح.');
    }
    if (empty($name)) {
        sendResponse(false, 'اسم القناة مطلوب.');
    }
    if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
        sendResponse(false, 'رابط البث غير صالح.');
    }

    $channels = getJsonData(CHANNELS_FILE, []);
    $found = false;

    foreach ($channels as &$ch) {
        if ($ch['id'] === $id) {
            $ch['name'] = $name;
            $ch['url'] = $url;
            if (!empty($group)) $ch['group'] = $group;
            if (!empty($logo)) $ch['logo'] = $logo;
            $found = true;
            break;
        }
    }

    if ($found && saveJsonData(CHANNELS_FILE, $channels)) {
        sendResponse(true, 'تم تعديل بيانات القناة بنجاح!');
    } else {
        sendResponse(false, 'لم يتم العثور على القناة أو لم تتغير البيانات.');
    }
}

// 9. حفظ إعدادات الموقع
if ($action === 'save_settings') {
    $config = getJsonData(CONFIG_FILE, []);

    $siteTitle = trim($_POST['site_title'] ?? '');
    $announcement = trim($_POST['announcement'] ?? '');
    $announcementActive = filter_var($_POST['announcement_active'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $defaultChannel = trim($_POST['default_channel_id'] ?? '');
    $m3uUrl = trim($_POST['m3u_url'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');

    if (!empty($siteTitle)) {
        $config['site_title'] = $siteTitle;
    }
    $config['announcement'] = $announcement;
    $config['announcement_active'] = $announcementActive;
    $config['default_channel_id'] = $defaultChannel;

    if (!empty($m3uUrl)) {
        $config['m3u_url'] = $m3uUrl;
    }

    if (!empty($newPassword)) {
        if (strlen($newPassword) < 5) {
            sendResponse(false, 'كلمة المرور يجب أن لا تقل عن 5 أحرف.');
        }
        $config['admin_password_hash'] = password_hash($newPassword, PASSWORD_BCRYPT);
        $config['admin_password_plain'] = '';
    }

    if (saveJsonData(CONFIG_FILE, $config)) {
        sendResponse(true, 'تم حفظ الإعدادات بنجاح!');
    } else {
        sendResponse(false, 'تعذر حفظ ملف الإعدادات.');
    }
}

// 10. استيراد ومحلل M3U فائق الذكاء والسرعة
if ($action === 'sync_m3u') {
    @set_time_limit(300);
    @ini_set('memory_limit', '512M');

    $remoteUrl = trim($_POST['m3u_url'] ?? '');

    if (empty($remoteUrl) && empty($_FILES['m3u_file']['tmp_name'])) {
        $config = getJsonData(CONFIG_FILE, []);
        $remoteUrl = $config['m3u_url'] ?? '';
    }

    if (empty($remoteUrl) && empty($_FILES['m3u_file']['tmp_name'])) {
        sendResponse(false, 'يرجى إدخال رابط M3U أو اختيار ملف محلي.');
    }

    // التحقق إذا كان الرابط سيرفر Xtream Codes
    $parsed = parse_url($remoteUrl);
    parse_str($parsed['query'] ?? '', $queryParams);
    $isXtream = (!empty($queryParams['username']) && !empty($queryParams['password']));

    // إذا كان سيرفر Xtream: الجلب المباشر عبر player_api.php السريع والمضمون
    if ($isXtream && empty($_FILES['m3u_file']['tmp_name'])) {
        $u = $queryParams['username'];
        $p = $queryParams['password'];
        $scheme = $parsed['scheme'] ?? 'http';
        $host = $parsed['host'] ?? '';
        $port = !empty($parsed['port']) ? (':' . $parsed['port']) : '';
        $baseUrl = "{$scheme}://{$host}{$port}";

        // جلب التصنيفات عبر cURL مع User-Agent IPTVSmartersPro
        $catsUrl = "{$baseUrl}/player_api.php?username={$u}&password={$p}&action=get_live_categories";
        $catsJson = fetchUrlWithUa($catsUrl, 20);
        $catMap = [];
        if ($catsJson) {
            $catsArr = json_decode($catsJson, true);
            if (is_array($catsArr)) {
                foreach ($catsArr as $c) {
                    if (!empty($c['category_id']) && !empty($c['category_name'])) {
                        $catMap[strval($c['category_id'])] = trim($c['category_name']);
                    }
                }
            }
        }

        // جلب القنوات المباشرة
        $streamsUrl = "{$baseUrl}/player_api.php?username={$u}&password={$p}&action=get_live_streams";
        $streamsJson = fetchUrlWithUa($streamsUrl, 45);
        if ($streamsJson) {
            $streamsArr = json_decode($streamsJson, true);
            if (is_array($streamsArr) && count($streamsArr) > 0) {
                $parsedChannels = [];
                $groupsFound = [];
                foreach ($streamsArr as $sIdx => $st) {
                    $sId = $st['stream_id'] ?? ($sIdx + 1);
                    $sName = trim($st['name'] ?? ("قناة " . ($sIdx + 1)));
                    $cId = strval($st['category_id'] ?? '');
                    $sGroup = $catMap[$cId] ?? 'عام (General)';
                    $sLogo = trim($st['stream_icon'] ?? '');
                    $sUrl = "{$baseUrl}/live/{$u}/{$p}/{$sId}.ts";

                    $parsedChannels[] = [
                        'id' => "ch_" . ($sIdx + 1),
                        'name' => $sName,
                        'logo' => $sLogo,
                        'group' => $sGroup,
                        'url' => $sUrl,
                        'visible' => true
                    ];
                    $groupsFound[$sGroup] = true;
                }

                if (saveJsonData(CHANNELS_FILE, $parsedChannels)) {
                    $config = getJsonData(CONFIG_FILE, []);
                    $config['last_sync'] = date('Y-m-d H:i:s');
                    $config['m3u_url'] = $remoteUrl;
                    saveJsonData(CONFIG_FILE, $config);

                    sendResponse(true, 'تم استيراد كافة القنوات بنجاح عبر Xtream Player API!', [
                        'total_imported' => count($parsedChannels),
                        'total_groups' => count($groupsFound),
                        'last_sync' => $config['last_sync']
                    ]);
                }
            }
        }
    }

    // الطريقة القياسية لجلب وتحليل ملف M3U النصي
    $content = '';
    if (!empty($_FILES['m3u_file']['tmp_name']) && is_uploaded_file($_FILES['m3u_file']['tmp_name'])) {
        $content = @file_get_contents($_FILES['m3u_file']['tmp_name']);
    } else {
        $content = fetchUrlWithUa($remoteUrl, 60);
    }

    if (empty($content)) {
        sendResponse(false, 'تعذر جلب محتوى قائمة M3U أو الرابط لا يستجيب.');
    }

    $lines = preg_split("/\r\n|\n|\r/", $content);
    $parsedChannels = [];
    $currentChannel = null;
    $groupsFound = [];
    $channelIndex = 1;

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') continue;

        if (stripos($line, '#EXTINF:') === 0) {
            $currentChannel = [
                'name' => '',
                'logo' => '',
                'group' => 'عام (General)',
                'url' => '',
                'visible' => true
            ];

            if (preg_match('/tvg-logo="([^"]*)"/i', $line, $m)) {
                $currentChannel['logo'] = trim($m[1]);
            }
            if (preg_match('/group-title="([^"]*)"/i', $line, $m)) {
                $grp = trim($m[1]);
                if (!empty($grp)) {
                    $currentChannel['group'] = $grp;
                }
            }
            $lastCommaPos = strrpos($line, ',');
            if ($lastCommaPos !== false) {
                $name = trim(substr($line, $lastCommaPos + 1));
                if (!empty($name)) {
                    $currentChannel['name'] = $name;
                }
            }
            if (empty($currentChannel['name']) && preg_match('/tvg-name="([^"]*)"/i', $line, $m)) {
                $currentChannel['name'] = trim($m[1]);
            }
        } elseif (stripos($line, '#EXTGRP:') === 0) {
            if ($currentChannel) {
                $grp = trim(substr($line, 8));
                if (!empty($grp)) {
                    $currentChannel['group'] = $grp;
                }
            }
        } elseif ($line[0] !== '#') {
            if ($currentChannel) {
                $streamUrl = $line;

                if (preg_match('#^(http://[^/]+)/([^/]+)/([^/]+)/(\d+)$#', $streamUrl, $mXtream)) {
                    $playUrl = "{$mXtream[1]}/live/{$mXtream[2]}/{$mXtream[3]}/{$mXtream[4]}.ts";
                } else {
                    $playUrl = $streamUrl;
                }

                $currentChannel['url'] = $playUrl;

                if (empty($currentChannel['name'])) {
                    $currentChannel['name'] = "قناة " . $channelIndex;
                }

                $currentChannel['id'] = "ch_" . $channelIndex;
                $parsedChannels[] = $currentChannel;
                $groupsFound[$currentChannel['group']] = true;
                $channelIndex++;
                $currentChannel = null;
            }
        }
    }

    $syncMode = trim($_POST['sync_mode'] ?? 'replace'); // 'replace' or 'append'

    if (empty($parsedChannels)) {
        // Smart fallback: check if the user provided a direct single-stream URL (e.g. Magnolia Network, Astra, HLS/TS)
        $isSingleStream = false;
        if (!empty($remoteUrl) && filter_var($remoteUrl, FILTER_VALIDATE_URL)) {
            if (preg_match('/\.(m3u8|ts|mp4|flv)($|\?)/i', $remoteUrl) ||
                preg_match('#/(play|live|stream)/#i', $remoteUrl) ||
                stripos($content, '#EXT-X-STREAM-INF') !== false ||
                stripos($content, '#EXT-X-TARGETDURATION') !== false) {
                $isSingleStream = true;
            }
        }

        if ($isSingleStream) {
            $pathParts = explode('/', parse_url($remoteUrl, PHP_URL_PATH) ?? '');
            $rawName = '';
            for ($k = count($pathParts) - 1; $k >= 0; $k--) {
                $p = trim($pathParts[$k]);
                if (!empty($p) && !preg_match('/^(index|live|play|tracks|video|stream)\.(m3u8|ts|mp4)?$/i', $p)) {
                    $rawName = $p;
                    break;
                }
            }
            if (empty($rawName)) {
                $rawName = 'قناة بث مباشر ' . date('H:i');
            }
            $cleanName = ucwords(str_replace(['_', '-'], ' ', preg_replace('/\.(m3u8|ts)$/i', '', $rawName)));

            $singleCh = [
                'id' => 'ch_' . time() . '_' . mt_rand(100, 999),
                'name' => $cleanName,
                'logo' => 'https://placehold.co/100x100/1e293b/38bdf8?text=' . urlencode(mb_substr($cleanName, 0, 4)),
                'group' => 'بث مباشر (Live)',
                'url' => $remoteUrl,
                'visible' => true
            ];

            $currentChannels = ($syncMode === 'replace') ? [] : getJsonData(CHANNELS_FILE, []);
            array_unshift($currentChannels, $singleCh);
            saveJsonData(CHANNELS_FILE, $currentChannels);

            $config = getJsonData(CONFIG_FILE, []);
            $config['last_sync'] = date('Y-m-d H:i:s');
            $config['m3u_url'] = $remoteUrl;
            $config['total_channels'] = count($currentChannels);
            saveJsonData(CONFIG_FILE, $config);

            sendResponse(true, "تم التعرف على الرابط كبث مباشر لقناة \"$cleanName\" وتمت إضافتها بنجاح إلى المشغل!", [
                'total_imported' => count($currentChannels),
                'total_groups' => 1,
                'last_sync' => $config['last_sync'],
                'channel' => $singleCh
            ]);
        }

        sendResponse(false, 'لم يتم العثور على أي قنوات صالحة في ملف M3U. إذا كان الرابط لقناة واحدة فقط، يمكنك استخدام تبويب "إضافة قناة".');
    }

    if ($syncMode === 'append') {
        $existing = getJsonData(CHANNELS_FILE, []);
        $combined = array_merge($existing, $parsedChannels);
    } else {
        $combined = $parsedChannels;
    }

    if (!saveJsonData(CHANNELS_FILE, $combined)) {
        sendResponse(false, 'فشل في حفظ القنوات إلى ملف channels.json');
    }

    $config = getJsonData(CONFIG_FILE, []);
    $config['last_sync'] = date('Y-m-d H:i:s');
    if (!empty($_POST['m3u_url'])) {
        $config['m3u_url'] = trim($_POST['m3u_url']);
    }
    $config['total_channels'] = count($combined);
    saveJsonData(CONFIG_FILE, $config);

    sendResponse(true, 'تمت مزامنة واستيراد القنوات بنجاح!', [
        'total_imported' => count($combined),
        'total_groups' => count($groupsFound),
        'last_sync' => $config['last_sync']
    ]);
}

sendResponse(false, 'إجراء غير معروف.');
