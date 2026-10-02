<?php
/**
 * IPTV Web Player Pro - Fast Admin Dashboard
 * Paginated, ultra-fast dashboard for managing 20,000+ channels and M3U playlists.
 */

declare(strict_types=1);
session_start();

define('CONFIG_FILE', __DIR__ . '/config.json');

function loadJsonData(string $path, array $fallback = []): array {
    if (!file_exists($path)) return $fallback;
    $raw = @file_get_contents($path);
    if ($raw === false) return $fallback;
    $data = json_decode($raw, true);
    return is_array($data) ? $data : $fallback;
}

$isLoggedIn = !empty($_SESSION['iptv_admin_logged']) && $_SESSION['iptv_admin_logged'] === true;

$config = loadJsonData(CONFIG_FILE, [
    'site_title' => 'VIP IPTV Web Player Pro',
    'announcement' => '',
    'announcement_active' => false,
    'default_channel_id' => '',
    'm3u_url' => '',
    'last_sync' => 'لم تتم المزامنة بعد'
]);

$siteTitle = htmlspecialchars($config['site_title'] ?? 'VIP IPTV Web Player Pro');
$m3uUrl = htmlspecialchars($config['m3u_url'] ?? '');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">
    <title>لوحة التحكم الإدارية — <?= $siteTitle ?></title>

    <!-- Tailwind CSS (CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: {
                            950: '#05070d',
                            900: '#0b0f19',
                            800: '#111827',
                            700: '#1f293d',
                            600: '#374151'
                        },
                        cyan: {
                            400: '#22d3ee',
                            500: '#06b6d4',
                            600: '#0891b2'
                        }
                    }
                }
            }
        };
    </script>

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Cairo Arabic Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Video.js Player for Stream Preview Modal -->
    <link href="https://vjs.zencdn.net/8.10.0/video-js.css" rel="stylesheet" />
    <script src="https://vjs.zencdn.net/8.10.0/video.min.js"></script>

    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #070a12;
            color: #f1f5f9;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, 0.6);
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(34, 211, 238, 0.25);
            border-radius: 9999px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(34, 211, 238, 0.5);
        }

        /* Modern Toggle Switch */
        .switch {
            position: relative;
            display: inline-block;
            width: 42px;
            height: 22px;
        }
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #374151;
            transition: .3s;
            border-radius: 22px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .3s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: #06b6d4;
        }
        input:checked + .slider:before {
            transform: translateX(20px);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col antialiased selection:bg-cyan-500 selection:text-white">

<?php if (!$isLoggedIn): ?>
    <!-- ======================================================== -->
    <!-- شاشة تسجيل الدخول الآمنة (Admin Login Screen) -->
    <!-- ======================================================== -->
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="max-w-md w-full bg-dark-900 border border-white/10 rounded-3xl p-6 sm:p-8 shadow-2xl relative overflow-hidden">
            <div class="absolute -top-24 -right-24 size-48 rounded-full bg-cyan-500/10 blur-3xl pointer-events-none"></div>
            
            <div class="text-center mb-8">
                <div class="size-16 rounded-2xl bg-gradient-to-tr from-cyan-500 to-blue-600 flex items-center justify-center shadow-lg shadow-cyan-500/20 text-white mx-auto mb-4 text-2xl">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h2 class="text-2xl font-black text-white">تسجيل دخول الإدارة</h2>
                <p class="text-xs text-slate-400 mt-1">لوحة التحكم لإدارة باقات وقنوات IPTV</p>
            </div>

            <form id="login-form" onsubmit="handleLogin(event)" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1.5">كلمة المرور</label>
                    <div class="relative">
                        <i class="fa-solid fa-key absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-500 text-xs"></i>
                        <input type="password" 
                               id="password-input" 
                               name="password" 
                               required 
                               placeholder="أدخل كلمة مرور الإدارة..." 
                               class="w-full bg-dark-800 border border-white/10 rounded-xl pr-10 pl-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-cyan-500/70 focus:ring-1 focus:ring-cyan-500/70 transition">
                    </div>
                    <span class="text-[11px] text-slate-500 mt-1 block">كلمة المرور الافتراضية: <code class="text-cyan-400">admin123</code></span>
                </div>

                <div id="login-error" class="hidden p-3 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400 text-xs text-center font-bold"></div>

                <button type="submit" id="login-btn" class="w-full py-3 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-dark-950 font-black text-sm transition-all shadow-lg shadow-cyan-500/20 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    <span>دخول للوحة التحكم</span>
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="index.php" class="text-xs text-slate-400 hover:text-cyan-400 transition flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-arrow-right"></i>
                    <span>العودة لموقع البث المباشر</span>
                </a>
            </div>
        </div>
    </div>

    <script>
        function handleLogin(e) {
            e.preventDefault();
            const btn = document.getElementById('login-btn');
            const errBox = document.getElementById('login-error');
            const pwd = document.getElementById('password-input').value;

            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> جاري التحقق...';
            errBox.classList.add('hidden');

            const fd = new FormData();
            fd.append('action', 'login');
            fd.append('password', pwd);

            fetch('api.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        errBox.textContent = data.message || 'كلمة المرور غير صحيحة.';
                        errBox.classList.remove('hidden');
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fa-solid fa-right-to-bracket"></i> دخول للوحة التحكم';
                    }
                })
                .catch(() => {
                    errBox.textContent = 'حدث خطأ في الاتصال بالخادم.';
                    errBox.classList.remove('hidden');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-right-to-bracket"></i> دخول للوحة التحكم';
                });
        }
    </script>
<?php else: ?>

    <!-- ======================================================== -->
    <!-- لوحة التحكم الإدارية الكاملة (Authenticated Fast Dashboard) -->
    <!-- ======================================================== -->

    <header class="bg-dark-900 border-b border-white/10 sticky top-0 z-40 px-4 lg:px-8 py-3.5">
        <div class="max-w-[1700px] mx-auto flex items-center justify-between gap-4">
            
            <div class="flex items-center gap-3">
                <div class="size-10 rounded-2xl bg-cyan-500/20 border border-cyan-500/30 flex items-center justify-center text-cyan-400">
                    <i class="fa-solid fa-sliders text-lg"></i>
                </div>
                <div>
                    <h1 class="font-extrabold text-base sm:text-lg text-white flex items-center gap-2">
                        لوحة إدارة القنوات والسيرفرات
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">ONLINE</span>
                    </h1>
                    <p class="text-[11px] text-slate-400">تصفح سريع بترقيم الصفحات ومزامنة كاملة لروابط M3U</p>
                </div>
            </div>

            <div class="flex items-center gap-2.5">
                <a href="index.php" target="_blank" class="px-3.5 py-2 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs font-bold text-slate-300 hover:text-white transition flex items-center gap-1.5">
                    <i class="fa-solid fa-arrow-up-right-from-square text-cyan-400 text-xs"></i>
                    <span class="hidden sm:inline">معاينة الموقع</span>
                </a>
                <button onclick="handleLogout()" class="px-3.5 py-2 rounded-xl bg-red-500/10 hover:bg-red-500/20 border border-red-500/30 text-xs font-bold text-red-400 transition flex items-center gap-1.5">
                    <i class="fa-solid fa-power-off"></i>
                    <span>خروج</span>
                </button>
            </div>

        </div>
    </header>

    <main class="flex-1 max-w-[1700px] w-full mx-auto p-4 sm:p-6 lg:p-8 space-y-6">

        <!-- بطاقات الإحصائيات (KPI Cards) -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-5">
            <div class="bg-dark-900 border border-white/10 rounded-2xl p-4 sm:p-5 flex items-center justify-between">
                <div>
                    <span class="text-xs text-slate-400 font-semibold block">إجمالي القنوات</span>
                    <span class="text-2xl sm:text-3xl font-black text-white font-mono mt-1 block" id="kpi-total">...</span>
                </div>
                <div class="size-12 rounded-xl bg-cyan-500/10 text-cyan-400 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-list-check"></i>
                </div>
            </div>

            <div class="bg-dark-900 border border-white/10 rounded-2xl p-4 sm:p-5 flex items-center justify-between">
                <div>
                    <span class="text-xs text-emerald-400 font-semibold block">القنوات المفعلة</span>
                    <span class="text-2xl sm:text-3xl font-black text-emerald-400 font-mono mt-1 block" id="kpi-visible">...</span>
                </div>
                <div class="size-12 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-eye"></i>
                </div>
            </div>

            <div class="bg-dark-900 border border-white/10 rounded-2xl p-4 sm:p-5 flex items-center justify-between">
                <div>
                    <span class="text-xs text-amber-400 font-semibold block">القنوات المخفية</span>
                    <span class="text-2xl sm:text-3xl font-black text-amber-400 font-mono mt-1 block" id="kpi-hidden">...</span>
                </div>
                <div class="size-12 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-eye-slash"></i>
                </div>
            </div>

            <div class="bg-dark-900 border border-white/10 rounded-2xl p-4 sm:p-5 flex items-center justify-between">
                <div>
                    <span class="text-xs text-purple-400 font-semibold block">الباقات والتصنيفات</span>
                    <span class="text-2xl sm:text-3xl font-black text-purple-400 font-mono mt-1 block" id="kpi-groups">...</span>
                </div>
                <div class="size-12 rounded-xl bg-purple-500/10 text-purple-400 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-layer-group"></i>
                </div>
            </div>
        </div>

        <!-- أزرار التبويبات -->
        <div class="flex items-center gap-2 border-b border-white/10 pb-3 flex-wrap">
            <button onclick="switchTab('channels')" id="tab-btn-channels" class="tab-btn active px-4 py-2 rounded-xl text-xs sm:text-sm font-black transition flex items-center gap-2 bg-cyan-500 text-dark-950 shadow-lg shadow-cyan-500/20">
                <i class="fa-solid fa-tv"></i>
                <span>إدارة القنوات المرقّمة</span>
            </button>
            <button onclick="switchTab('add-channel')" id="tab-btn-add-channel" class="tab-btn px-4 py-2 rounded-xl text-xs sm:text-sm font-black transition flex items-center gap-2 bg-dark-900 text-slate-300 hover:text-white border border-white/10">
                <i class="fa-solid fa-circle-plus text-cyan-400"></i>
                <span>إضافة بث مباشر / قناة</span>
            </button>
            <button onclick="switchTab('m3u')" id="tab-btn-m3u" class="tab-btn px-4 py-2 rounded-xl text-xs sm:text-sm font-black transition flex items-center gap-2 bg-dark-900 text-slate-300 hover:text-white border border-white/10">
                <i class="fa-solid fa-cloud-arrow-down text-blue-400"></i>
                <span>مزامنة وتغيير سيرفر M3U</span>
            </button>
            <button onclick="switchTab('settings')" id="tab-btn-settings" class="tab-btn px-4 py-2 rounded-xl text-xs sm:text-sm font-black transition flex items-center gap-2 bg-dark-900 text-slate-300 hover:text-white border border-white/10">
                <i class="fa-solid fa-gear"></i>
                <span>إعدادات الموقع</span>
            </button>
        </div>

        <!-- ======================================================== -->
        <!-- 1. لوحة إدارة القنوات المرقّمة السريعة (Channels Tab) -->
        <!-- ======================================================== -->
        <div id="tab-content-channels" class="tab-content space-y-4">
            
            <!-- شريط الأدوات والفلترة -->
            <div class="bg-dark-900 border border-white/10 rounded-2xl p-4 flex flex-col md:flex-row items-center justify-between gap-4">
                
                <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                    <!-- البحث بالاسم -->
                    <div class="relative w-full sm:w-64">
                        <i class="fa-solid fa-magnifying-glass absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" 
                               id="admin-search" 
                               placeholder="بحث سريع بالاسم..." 
                               class="w-full bg-dark-800 border border-white/10 rounded-xl pr-9 pl-4 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-cyan-500">
                    </div>

                    <!-- اختيار الباقة -->
                    <select id="admin-group-filter" onchange="onAdminFilterChange()" class="bg-dark-800 border border-white/10 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-cyan-500 max-w-[220px]">
                        <option value="">جميع الباقات (الكل)</option>
                    </select>

                    <!-- فلترة الحالة -->
                    <select id="admin-status-filter" onchange="onAdminFilterChange()" class="bg-dark-800 border border-white/10 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-cyan-500">
                        <option value="">جميع الحالات</option>
                        <option value="visible">المفعلة فقط</option>
                        <option value="hidden">المخفية فقط</option>
                    </select>

                    <!-- عدد القنوات في كل صفحة -->
                    <div class="flex items-center gap-1.5 text-xs text-slate-400">
                        <span>عرض:</span>
                        <select id="admin-limit-select" onchange="onAdminLimitChange()" class="bg-dark-800 border border-white/10 rounded-lg px-2 py-1.5 text-xs text-white font-bold focus:outline-none">
                            <option value="15">15 قناة</option>
                            <option value="20" selected>20 قناة</option>
                            <option value="50">50 قناة</option>
                            <option value="100">100 قناة</option>
                        </select>
                    </div>
                </div>

                <!-- الإجراءات الجماعية وإضافة قناة -->
                <div class="flex flex-wrap items-center gap-2 w-full md:w-auto justify-end">
                    <button onclick="switchTab('add-channel')" class="px-3.5 py-2 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-dark-950 text-xs font-black transition flex items-center gap-1.5 shadow-md shadow-cyan-500/20">
                        <i class="fa-solid fa-plus"></i>
                        <span>إضافة قناة جديدة</span>
                    </button>
                    <button onclick="toggleBulkVisibility('show_all')" class="px-3 py-2 rounded-xl bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 text-xs font-bold transition flex items-center gap-1.5">
                        <i class="fa-solid fa-eye"></i>
                        <span>إظهار الكل</span>
                    </button>
                    <button onclick="toggleBulkVisibility('hide_all')" class="px-3 py-2 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/30 text-amber-400 text-xs font-bold transition flex items-center gap-1.5">
                        <i class="fa-solid fa-eye-slash"></i>
                        <span>إخفاء الكل</span>
                    </button>
                </div>

            </div>

            <!-- جدول القنوات (خفيف وسريع جداً) -->
            <div class="bg-dark-900 border border-white/10 rounded-2xl overflow-hidden shadow-xl">
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-right text-xs">
                        <thead class="bg-dark-800/80 border-b border-white/10 text-slate-400 font-extrabold sticky top-0 z-10 backdrop-blur-md">
                            <tr>
                                <th class="p-3.5 w-14 text-center">#</th>
                                <th class="p-3.5 w-16 text-center">الشعار</th>
                                <th class="p-3.5">اسم القناة</th>
                                <th class="p-3.5">الباقة / التصنيف</th>
                                <th class="p-3.5">رابط البث (Stream URL)</th>
                                <th class="p-3.5 w-28 text-center">الحالة (عرض)</th>
                                <th class="p-3.5 w-28 text-center">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody id="channels-table-body" class="divide-y divide-white/5">
                            <tr>
                                <td colspan="7" class="p-8 text-center text-slate-400">
                                    <i class="fa-solid fa-circle-notch fa-spin text-2xl text-cyan-400 mb-2 block"></i>
                                    جاري تحميل القنوات بسرعة فائقة...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- شريط الترقيم السريع (Pagination Bar) -->
                <div class="p-4 border-t border-white/10 bg-dark-800/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="text-xs text-slate-400" id="pagination-info">
                        جاري الحساب...
                    </div>

                    <div class="flex items-center gap-2">
                        <button id="prev-page-btn" onclick="goToPage(currentPage - 1)" class="px-3.5 py-1.5 rounded-xl bg-dark-800 border border-white/10 text-xs font-bold text-slate-300 hover:text-white hover:border-cyan-500 disabled:opacity-40 disabled:pointer-events-none transition flex items-center gap-1.5">
                            <i class="fa-solid fa-chevron-right text-[10px]"></i>
                            <span>السابق</span>
                        </button>

                        <div class="flex items-center gap-1" id="pagination-buttons">
                            <!-- Dynamic Page Buttons -->
                        </div>

                        <button id="next-page-btn" onclick="goToPage(currentPage + 1)" class="px-3.5 py-1.5 rounded-xl bg-dark-800 border border-white/10 text-xs font-bold text-slate-300 hover:text-white hover:border-cyan-500 disabled:opacity-40 disabled:pointer-events-none transition flex items-center gap-1.5">
                            <span>التالي</span>
                            <i class="fa-solid fa-chevron-left text-[10px]"></i>
                        </button>
                    </div>
                </div>

            </div>

        </div>

        <!-- ======================================================== -->
        <!-- 2. لوحة إضافة قناة أو بث مباشر جديد يدوياً (Add Channel Tab) -->
        <!-- ======================================================== -->
        <div id="tab-content-add-channel" class="tab-content hidden space-y-6">
            
            <div class="bg-dark-900 border border-white/10 rounded-2xl p-6 sm:p-8 max-w-3xl">
                <div class="flex items-center gap-3 mb-6">
                    <div class="size-12 rounded-xl bg-cyan-500/10 text-cyan-400 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-circle-plus"></i>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-lg text-white">إضافة قناة أو رابط بث مباشر (Live Stream)</h3>
                        <p class="text-xs text-slate-400">أضف أي رابط بث مباشر لمشاهدته فوراً داخل مشغل الموقع (يدعم .m3u8 و .ts وروابط Astra)</p>
                    </div>
                </div>

                <form id="add-channel-form" onsubmit="handleAddChannel(event)" class="space-y-5">
                    
                    <!-- اسم القناة -->
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1.5">
                            اسم القناة <span class="text-red-400">*</span>
                        </label>
                        <input type="text" 
                               id="add-ch-name" 
                               required 
                               placeholder="مثال: Magnolia Network, beIN Sports 1 HD, الجزيرة..."
                               class="w-full bg-dark-800 border border-white/10 rounded-xl px-4 py-3 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-cyan-500">
                    </div>

                    <!-- رابط البث المباشر -->
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1.5">
                            رابط البث المباشر (Live Stream URL) <span class="text-red-400">*</span>
                        </label>
                        <div class="relative">
                            <i class="fa-solid fa-play absolute right-3.5 top-1/2 -translate-y-1/2 text-cyan-400 text-xs"></i>
                            <input type="url" 
                                   id="add-ch-url" 
                                   required 
                                   placeholder="http://190.197.41.183/MAGNOLIA_NETWORK/index.m3u8 أو http://server:8000/play/..."
                                   class="w-full bg-dark-800 border border-white/10 rounded-xl pr-10 pl-4 py-3 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-cyan-500 font-mono">
                        </div>
                        <span class="text-[11px] text-slate-400 mt-1 block">
                            يدعم جميع صيغ البث: HLS (.m3u8)، MPEG-TS (.ts)، Astra، Xtream، أو أي سيرفر بث حي.
                        </span>
                    </div>

                    <!-- الباقة والشعار -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1.5">
                                الباقة / التصنيف (Group)
                            </label>
                            <input type="text" 
                                   id="add-ch-group" 
                                   placeholder="اختر أو اكتب باقة جديدة..."
                                   list="existing-groups-datalist"
                                   value="عام (General)"
                                   class="w-full bg-dark-800 border border-white/10 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-cyan-500">
                            <datalist id="existing-groups-datalist"></datalist>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1.5">
                                رابط شعار القناة (Logo URL - اختياري)
                            </label>
                            <input type="url" 
                                   id="add-ch-logo" 
                                   placeholder="https://example.com/logo.png"
                                   class="w-full bg-dark-800 border border-white/10 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-cyan-500">
                        </div>
                    </div>

                    <!-- مكان القناة -->
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-2">ترتيب ومكان القناة في المشغل:</label>
                        <div class="flex items-center gap-6 text-xs text-slate-300">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="add_ch_position" value="top" checked class="text-cyan-500 focus:ring-0">
                                <span class="font-bold text-cyan-400">في أول القائمة (لتشغيلها كقناة أولى رئيسية فوراً)</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="add_ch_position" value="bottom" class="text-cyan-500 focus:ring-0">
                                <span>في آخر القائمة</span>
                            </label>
                        </div>
                    </div>

                    <div id="add-channel-msg" class="hidden p-3.5 rounded-xl text-xs font-bold text-center"></div>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" 
                                id="add-channel-btn"
                                class="px-6 py-3 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-dark-950 font-black text-xs transition flex items-center justify-center gap-2 shadow-lg shadow-cyan-500/20">
                            <i class="fa-solid fa-plus"></i>
                            <span>حفظ وإضافة القناة للمشغل</span>
                        </button>
                        <a href="index.php" target="_blank" class="px-4 py-3 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs font-bold text-slate-300 hover:text-white transition flex items-center gap-2">
                            <i class="fa-solid fa-arrow-up-right-from-square text-cyan-400"></i>
                            <span>معاينة في المشغل</span>
                        </a>
                    </div>

                </form>

            </div>

        </div>

        <!-- ======================================================== -->
        <!-- 3. لوحة مزامنة وتغيير رابط M3U (M3U Source Management Tab) -->
        <!-- ======================================================== -->
        <div id="tab-content-m3u" class="tab-content hidden space-y-6">
            
            <div class="bg-dark-900 border border-white/10 rounded-2xl p-6 sm:p-8 max-w-3xl">
                <div class="flex items-center gap-3 mb-6">
                    <div class="size-12 rounded-xl bg-cyan-500/10 text-cyan-400 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-cloud-arrow-down"></i>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-lg text-white">تغيير ومزامنة سيرفر M3U أو بث مباشر</h3>
                        <p class="text-xs text-slate-400">يمكنك وضع رابط M3U كامل أو رابط بث مباشر مفرد وسيتم التعرف عليه تلقائياً</p>
                    </div>
                </div>

                <!-- نصيحة إرشادية ذكية -->
                <div class="mb-5 p-3.5 bg-cyan-950/30 border border-cyan-500/20 rounded-xl text-xs text-cyan-200 flex items-start gap-2.5">
                    <i class="fa-solid fa-circle-info text-cyan-400 mt-0.5 text-sm"></i>
                    <div class="leading-relaxed">
                        <strong>نصيحة:</strong> يدعم هذا القسم روابط ملفات <strong>M3U الكاملة</strong>، وأيضاً <strong>روابط البث المباشر المفردة</strong> (مثل روابط .m3u8 أو .ts). إذا أردت إضافة قناة واحدة وتحديد اسمها بنفسك، يمكنك استخدام تبويب <strong class="text-white">"إضافة بث مباشر / قناة"</strong> في الأعلى.
                    </div>
                </div>

                <form id="m3u-sync-form" onsubmit="handleM3uSync(event)" class="space-y-5">
                    
                    <!-- إدخال وتعديل رابط M3U -->
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1.5">
                            رابط قائمة M3U أو رابط البث المباشر (Remote URL)
                        </label>
                        <div class="relative">
                            <i class="fa-solid fa-link absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-500 text-xs"></i>
                            <input type="url" 
                                   id="m3u-url-input" 
                                   name="m3u_url"
                                   required
                                   value="<?= $m3uUrl ?>" 
                                   placeholder="http://server.top:8080/get.php?username=...&password=... أو http://.../index.m3u8" 
                                   class="w-full bg-dark-800 border border-white/10 rounded-xl pr-10 pl-4 py-3 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-cyan-500 font-mono">
                        </div>
                        <span class="text-[11px] text-slate-400 mt-1 block">
                            قم بنسخ رابط الـ M3U أو رابط القناة المباشرة هنا ثم اضغط على زر المزامنة بالأسفل.
                        </span>
                    </div>

                    <!-- خيار وضع المزامنة (دمج أم استبدال) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-2">طريقة حفظ القنوات:</label>
                        <div class="flex items-center gap-6 text-xs text-slate-300">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="sync_mode" value="replace" checked class="text-cyan-500 focus:ring-0">
                                <span class="font-bold text-white">استبدال القنوات الحالية (مسح القديم ووضع الجديد)</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="sync_mode" value="append" class="text-cyan-500 focus:ring-0">
                                <span class="text-slate-300">إضافة ودمج مع القنوات الحالية (دون حذف القديم)</span>
                            </label>
                        </div>
                    </div>

                    <div class="relative flex py-2 items-center">
                        <div class="flex-grow border-t border-white/10"></div>
                        <span class="flex-shrink mx-4 text-xs font-bold text-slate-500">أو رفع ملف .m3u محلي من جهازك</span>
                        <div class="flex-grow border-t border-white/10"></div>
                    </div>

                    <!-- رفع ملف محلي -->
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1.5">
                            رفع ملف M3U محلي (.m3u / .m3u8)
                        </label>
                        <input type="file" 
                               id="m3u-file-input" 
                               name="m3u_file"
                               accept=".m3u,.m3u8,text/plain"
                               class="w-full bg-dark-800 border border-white/10 rounded-xl px-4 py-2 text-xs text-slate-300 file:mr-4 file:py-1.5 file:px-3.5 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-cyan-500 file:text-dark-950 hover:file:bg-cyan-400 cursor-pointer">
                    </div>

                    <!-- وقت المزامنة السابقة -->
                    <div class="p-3.5 rounded-xl bg-white/[0.02] border border-white/5 flex items-center justify-between text-xs text-slate-400">
                        <span>آخر مزامنة ناجحة:</span>
                        <span class="font-mono text-cyan-300 font-bold" id="last-sync-time"><?= htmlspecialchars($config['last_sync'] ?? 'لا يوجد') ?></span>
                    </div>

                    <div id="sync-status-msg" class="hidden p-3.5 rounded-xl text-xs font-bold text-center"></div>

                    <button type="submit" 
                            id="sync-btn"
                            class="px-6 py-3 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-dark-950 font-black text-xs transition flex items-center justify-center gap-2 shadow-lg shadow-cyan-500/20">
                        <i class="fa-solid fa-arrows-rotate"></i>
                        <span>مزامنة وحفظ القنوات من هذا الرابط</span>
                    </button>

                </form>
            </div>

        </div>

        <!-- ======================================================== -->
        <!-- 3. لوحة إعدادات الموقع (Site Settings Tab) -->
        <!-- ======================================================== -->
        <div id="tab-content-settings" class="tab-content hidden space-y-6">
            
            <div class="bg-dark-900 border border-white/10 rounded-2xl p-6 sm:p-8 max-w-3xl">
                <div class="flex items-center gap-3 mb-6">
                    <div class="size-12 rounded-xl bg-cyan-500/10 text-cyan-400 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-sliders"></i>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-lg text-white">إعدادات البوابة والحماية</h3>
                        <p class="text-xs text-slate-400">تخصيص عنوان الموقع، شريط الإعلانات، رابط M3U، وكلمة المرور</p>
                    </div>
                </div>

                <form id="settings-form" onsubmit="handleSaveSettings(event)" class="space-y-5">
                    
                    <!-- عنوان الموقع -->
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1.5">عنوان الموقع (Site Title)</label>
                        <input type="text" 
                               name="site_title"
                               value="<?= htmlspecialchars($config['site_title'] ?? '') ?>" 
                               required
                               class="w-full bg-dark-800 border border-white/10 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-cyan-500">
                    </div>

                    <!-- رابط M3U الدائم -->
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1.5">رابط M3U المحفوظ حالياً</label>
                        <input type="url" 
                               name="m3u_url"
                               value="<?= $m3uUrl ?>" 
                               placeholder="http://server.top:8080/..." 
                               class="w-full bg-dark-800 border border-white/10 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-cyan-500 font-mono">
                    </div>

                    <!-- شريط الإعلان الترويجي -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-bold text-slate-300">نص شريط الإعلانات العلوي</label>
                            <label class="flex items-center gap-2 text-xs text-slate-400 cursor-pointer">
                                <input type="checkbox" 
                                       name="announcement_active" 
                                       value="1" 
                                       <?= !empty($config['announcement_active']) ? 'checked' : '' ?>
                                       class="rounded bg-dark-800 border-white/10 text-cyan-500 focus:ring-0">
                                <span>تفعيل الشريط</span>
                            </label>
                        </div>
                        <input type="text" 
                               name="announcement"
                               value="<?= htmlspecialchars($config['announcement'] ?? '') ?>" 
                               placeholder="اكتب رسالة ترحيبية أو إعلان يظهر أعلى المشغل..." 
                               class="w-full bg-dark-800 border border-white/10 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-cyan-500">
                    </div>

                    <!-- تغيير كلمة المرور -->
                    <div class="pt-4 border-t border-white/10">
                        <label class="block text-xs font-bold text-slate-300 mb-1.5">
                            تغيير كلمة مرور الإدارة (اتركها فارغة إذا كنت لا تريد التغيير)
                        </label>
                        <input type="password" 
                               name="new_password" 
                               placeholder="أدخل كلمة مرور جديدة (5 أحرف على الأقل)..." 
                               class="w-full bg-dark-800 border border-white/10 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-cyan-500">
                    </div>

                    <div id="settings-status-msg" class="hidden p-3.5 rounded-xl text-xs font-bold text-center"></div>

                    <button type="submit" 
                            id="save-settings-btn"
                            class="px-6 py-2.5 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-dark-950 font-black text-xs transition flex items-center gap-2 shadow-lg shadow-cyan-500/20">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>حفظ التعديلات</span>
                    </button>

                </form>
            </div>

        </div>

    </main>

    <!-- مودال معاينة البث المباشر -->
    <div id="preview-modal" class="fixed inset-0 bg-dark-950/80 backdrop-blur-md hidden items-center justify-center z-50 p-4">
        <div class="bg-dark-900 border border-white/10 rounded-3xl max-w-2xl w-full p-6 shadow-2xl relative overflow-hidden">
            
            <div class="flex items-center justify-between pb-4 border-b border-white/10 mb-4">
                <div class="flex items-center gap-2.5">
                    <span class="size-3 rounded-full bg-red-500 animate-ping"></span>
                    <h4 id="preview-title" class="font-extrabold text-sm sm:text-base text-white">معاينة القناة</h4>
                </div>
                <button onclick="closePreviewModal()" class="size-8 rounded-xl bg-white/5 hover:bg-white/10 text-slate-400 hover:text-white flex items-center justify-center transition">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="w-full aspect-video bg-black rounded-xl overflow-hidden mb-4">
                <video id="preview-player" class="video-js vjs-default-skin w-full h-full" controls playsinline></video>
            </div>

            <div class="flex items-center justify-between text-xs text-slate-400 font-mono">
                <span id="preview-url-text" class="truncate max-w-md"></span>
                <button onclick="closePreviewModal()" class="px-4 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 text-white font-bold transition">
                    إغلاق
                </button>
            </div>

    </div>

    <!-- مودال تعديل بيانات القناة -->
    <div id="edit-modal" class="fixed inset-0 bg-dark-950/80 backdrop-blur-md hidden items-center justify-center z-50 p-4">
        <div class="bg-dark-900 border border-white/10 rounded-3xl max-w-xl w-full p-6 sm:p-8 shadow-2xl relative overflow-hidden">
            
            <div class="flex items-center justify-between pb-4 border-b border-white/10 mb-5">
                <div class="flex items-center gap-2.5">
                    <div class="size-9 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center text-base">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </div>
                    <h4 class="font-extrabold text-sm sm:text-base text-white">تعديل بيانات القناة</h4>
                </div>
                <button onclick="closeEditModal()" class="size-8 rounded-xl bg-white/5 hover:bg-white/10 text-slate-400 hover:text-white flex items-center justify-center transition">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="edit-channel-form" onsubmit="handleEditChannel(event)" class="space-y-4">
                <input type="hidden" id="edit-ch-id" name="id">

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1.5">اسم القناة</label>
                    <input type="text" id="edit-ch-name" name="name" required class="w-full bg-dark-800 border border-white/10 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-cyan-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1.5">رابط البث المباشر (Stream URL)</label>
                    <input type="url" id="edit-ch-url" name="url" required class="w-full bg-dark-800 border border-white/10 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-cyan-500 font-mono">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1.5">الباقة / التصنيف</label>
                        <input type="text" id="edit-ch-group" name="group" list="existing-groups-datalist" class="w-full bg-dark-800 border border-white/10 rounded-xl px-4 py-2 text-xs text-white focus:outline-none focus:border-cyan-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1.5">شعار القناة (رابط الصورة)</label>
                        <input type="url" id="edit-ch-logo" name="logo" class="w-full bg-dark-800 border border-white/10 rounded-xl px-4 py-2 text-xs text-white focus:outline-none focus:border-cyan-500">
                    </div>
                </div>

                <div id="edit-channel-msg" class="hidden p-3 rounded-xl text-xs font-bold text-center"></div>

                <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-white/10">
                    <button type="button" onclick="closeEditModal()" class="px-4 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-slate-300 text-xs font-bold transition">
                        إلغاء
                    </button>
                    <button type="submit" id="save-edit-btn" class="px-5 py-2 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-dark-950 font-black text-xs transition flex items-center gap-1.5 shadow-md shadow-cyan-500/20">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>حفظ التعديلات</span>
                    </button>
                </div>
            </form>

        </div>
    </div>

    <!-- ======================================================== -->
    <!-- جافاسكربت لوحة التحكم الإدارية (Fast Paginated Logic) -->
    <!-- ======================================================== -->
    <script>
        let currentPage = 1;
        let currentLimit = 20;
        let totalPages = 1;
        let totalItems = 0;
        let previewPlayerInstance = null;
        let searchDebounce = null;

        document.addEventListener('DOMContentLoaded', function() {
            loadKpisAndGroups();
            fetchAdminChannels(1);

            const searchInput = document.getElementById('admin-search');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchDebounce);
                    searchDebounce = setTimeout(() => {
                        currentPage = 1;
                        fetchAdminChannels(1);
                    }, 300);
                });
            }
        });

        // Load KPIs and populate groups dropdown
        function loadKpisAndGroups() {
            fetch('api.php?action=get_groups')
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        document.getElementById('kpi-total').textContent = res.total_channels.toLocaleString();
                        document.getElementById('kpi-visible').textContent = res.total_visible.toLocaleString();
                        document.getElementById('kpi-hidden').textContent = (res.total_channels - res.total_visible).toLocaleString();
                        document.getElementById('kpi-groups').textContent = res.groups.length.toLocaleString();

                        const groupSelect = document.getElementById('admin-group-filter');
                        const dataList = document.getElementById('existing-groups-datalist');
                        groupSelect.innerHTML = '<option value="">جميع الباقات (الكل)</option>';
                        if (dataList) dataList.innerHTML = '';
                        res.groups.forEach(g => {
                            const opt = document.createElement('option');
                            opt.value = g.name;
                            opt.textContent = `${g.name} (${g.total})`;
                            groupSelect.appendChild(opt);

                            if (dataList) {
                                const dlOpt = document.createElement('option');
                                dlOpt.value = g.name;
                                dataList.appendChild(dlOpt);
                            }
                        });
                    }
                })
                .catch(err => console.error("Error loading groups:", err));
        }

        // Fetch Paginated Channels via AJAX
        function fetchAdminChannels(page = 1) {
            currentPage = page;
            const tbody = document.getElementById('channels-table-body');
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="p-8 text-center text-slate-400">
                        <i class="fa-solid fa-circle-notch fa-spin text-2xl text-cyan-400 mb-2 block"></i>
                        جاري تحميل الصفحة ${currentPage}...
                    </td>
                </tr>
            `;

            const group = document.getElementById('admin-group-filter').value;
            const status = document.getElementById('admin-status-filter').value;
            const q = document.getElementById('admin-search').value.trim();

            let url = `api.php?action=get_channels&admin=1&page=${currentPage}&limit=${currentLimit}`;
            if (group) url += `&group=${encodeURIComponent(group)}`;
            if (status) url += `&status=${encodeURIComponent(status)}`;
            if (q) url += `&q=${encodeURIComponent(q)}`;

            fetch(url)
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        totalItems = res.total;
                        totalPages = res.total_pages;
                        renderTableRows(res.channels, (currentPage - 1) * currentLimit);
                        renderPagination();
                    } else {
                        tbody.innerHTML = `<tr><td colspan="7" class="p-6 text-center text-red-400 font-bold">${res.message}</td></tr>`;
                    }
                })
                .catch(() => {
                    tbody.innerHTML = `<tr><td colspan="7" class="p-6 text-center text-red-400 font-bold">فشل الاتصال بالخادم.</td></tr>`;
                });
        }

        function renderTableRows(channels, startIndex) {
            const tbody = document.getElementById('channels-table-body');
            tbody.innerHTML = '';

            if (!channels || channels.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" class="p-8 text-center text-slate-400 font-bold">لا توجد قنوات مطابقة.</td></tr>`;
                return;
            }

            channels.forEach((ch, i) => {
                const rowNum = startIndex + i + 1;
                const isVisible = !!ch.visible;
                const logo = ch.logo || 'https://placehold.co/100x100/1e293b/38bdf8?text=TV';

                const tr = document.createElement('tr');
                tr.id = `row-${ch.id}`;
                tr.className = 'hover:bg-white/[0.02] transition';
                tr.innerHTML = `
                    <td class="p-3.5 text-center text-slate-500 font-mono">${rowNum}</td>
                    <td class="p-3.5 text-center">
                        <div class="size-9 rounded-lg bg-dark-800 border border-white/10 p-1 flex items-center justify-center mx-auto overflow-hidden">
                            <img src="${logo}" alt="logo" class="max-h-full max-w-full object-contain" onerror="this.src='https://placehold.co/100x100/1e293b/38bdf8?text=TV'">
                        </div>
                    </td>
                    <td class="p-3.5 font-bold text-white">${ch.name}</td>
                    <td class="p-3.5">
                        <span class="px-2 py-0.5 rounded-full bg-white/5 border border-white/10 text-[11px] text-cyan-300">
                            ${ch.group || 'عام'}
                        </span>
                    </td>
                    <td class="p-3.5 max-w-xs truncate font-mono text-[11px] text-slate-400" title="${ch.url}">${ch.url}</td>
                    <td class="p-3.5 text-center">
                        <label class="switch">
                            <input type="checkbox" onchange="toggleChannelVisibility('${ch.id}', this.checked)" ${isVisible ? 'checked' : ''}>
                            <span class="slider"></span>
                        </label>
                    </td>
                    <td class="p-3.5 text-center">
                        <div class="flex items-center justify-center gap-1.5">
                            <button onclick="previewStream('${escapeHtml(ch.name)}', '${escapeHtml(ch.url)}')" title="معاينة البث" class="size-8 rounded-lg bg-cyan-500/10 hover:bg-cyan-500/20 text-cyan-400 flex items-center justify-center transition">
                                <i class="fa-solid fa-play text-xs"></i>
                            </button>
                            <button onclick="openEditModal('${ch.id}', '${escapeHtml(ch.name)}', '${escapeHtml(ch.url)}', '${escapeHtml(ch.group || '')}', '${escapeHtml(ch.logo || '')}')" title="تعديل القناة" class="size-8 rounded-lg bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 flex items-center justify-center transition">
                                <i class="fa-solid fa-pen-to-square text-xs"></i>
                            </button>
                            <button onclick="deleteChannel('${ch.id}')" title="حذف القناة" class="size-8 rounded-lg bg-red-500/10 hover:bg-red-500/20 text-red-400 flex items-center justify-center transition">
                                <i class="fa-solid fa-trash-can text-xs"></i>
                            </button>
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        function renderPagination() {
            const start = totalItems === 0 ? 0 : (currentPage - 1) * currentLimit + 1;
            const end = Math.min(currentPage * currentLimit, totalItems);
            document.getElementById('pagination-info').textContent = `عرض ${start.toLocaleString()} - ${end.toLocaleString()} من إجمالي ${totalItems.toLocaleString()} قناة (صفحة ${currentPage} من ${totalPages})`;

            document.getElementById('prev-page-btn').disabled = (currentPage <= 1);
            document.getElementById('next-page-btn').disabled = (currentPage >= totalPages);

            const buttonsContainer = document.getElementById('pagination-buttons');
            buttonsContainer.innerHTML = '';

            let pages = [];
            if (totalPages <= 7) {
                for (let i = 1; i <= totalPages; i++) pages.push(i);
            } else {
                pages.push(1);
                if (currentPage > 3) pages.push('...');
                for (let i = Math.max(2, currentPage - 1); i <= Math.min(totalPages - 1, currentPage + 1); i++) {
                    pages.push(i);
                }
                if (currentPage < totalPages - 2) pages.push('...');
                pages.push(totalPages);
            }

            pages.forEach(p => {
                if (p === '...') {
                    const dots = document.createElement('span');
                    dots.className = 'px-2 text-slate-500 text-xs';
                    dots.textContent = '...';
                    buttonsContainer.appendChild(dots);
                } else {
                    const btn = document.createElement('button');
                    btn.className = `size-8 rounded-xl text-xs font-bold transition flex items-center justify-center ${p === currentPage ? 'bg-cyan-500 text-dark-950 font-black shadow-md shadow-cyan-500/20' : 'bg-dark-800 border border-white/10 text-slate-300 hover:text-white'}`;
                    btn.textContent = p;
                    btn.onclick = () => goToPage(p);
                    buttonsContainer.appendChild(btn);
                }
            });
        }

        function goToPage(p) {
            if (p < 1 || p > totalPages) return;
            fetchAdminChannels(p);
        }

        function onAdminLimitChange() {
            currentLimit = parseInt(document.getElementById('admin-limit-select').value);
            currentPage = 1;
            fetchAdminChannels(1);
        }

        function onAdminFilterChange() {
            currentPage = 1;
            fetchAdminChannels(1);
        }

        function toggleChannelVisibility(channelId, isVisible) {
            const fd = new FormData();
            fd.append('action', 'toggle_visibility');
            fd.append('channel_id', channelId);
            fd.append('visible', isVisible ? '1' : '0');

            fetch('api.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        loadKpisAndGroups();
                    } else {
                        alert(res.message || 'فشل تحديث القناة.');
                    }
                });
        }

        function toggleBulkVisibility(mode) {
            const group = document.getElementById('admin-group-filter').value;
            const targetMsg = group ? `في باقة "${group}"` : 'في كافة القنوات';
            if (!confirm(`هل أنت متأكد من ${mode === 'show_all' ? 'إظهار' : 'إخفاء'} جميع القنوات ${targetMsg}؟`)) return;

            const fd = new FormData();
            fd.append('action', 'bulk_visibility');
            fd.append('mode', mode);
            if (group) fd.append('group', group);

            fetch('api.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    alert(res.message);
                    loadKpisAndGroups();
                    fetchAdminChannels(currentPage);
                });
        }

        function deleteChannel(channelId) {
            if (!confirm('هل تريد حذف هذه القناة نهائياً؟')) return;

            const fd = new FormData();
            fd.append('action', 'delete_channel');
            fd.append('channel_id', channelId);

            fetch('api.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        fetchAdminChannels(currentPage);
                        loadKpisAndGroups();
                    } else {
                        alert(res.message);
                    }
                });
        }

        function handleM3uSync(e) {
            e.preventDefault();
            const btn = document.getElementById('sync-btn');
            const statusMsg = document.getElementById('sync-status-msg');
            const form = document.getElementById('m3u-sync-form');

            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-arrows-rotate fa-spin"></i> جاري استيراد وتحليل القنوات من الرابط...';
            statusMsg.classList.add('hidden');

            const fd = new FormData(form);
            fd.append('action', 'sync_m3u');

            fetch('api.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-arrows-rotate"></i> مزامنة وحفظ القنوات من هذا الرابط';
                    statusMsg.classList.remove('hidden');

                    if (res.success) {
                        statusMsg.className = 'p-3.5 rounded-xl text-xs font-bold text-center bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 block';
                        statusMsg.innerHTML = `<i class="fa-solid fa-circle-check ml-1"></i> ${res.message} (تم استيراد ${res.total_imported.toLocaleString()} قناة في ${res.total_groups} باقة)`;
                        document.getElementById('last-sync-time').textContent = res.last_sync;
                        setTimeout(() => window.location.reload(), 2000);
                    } else {
                        statusMsg.className = 'p-3.5 rounded-xl text-xs font-bold text-center bg-red-500/10 border border-red-500/30 text-red-400 block';
                        statusMsg.innerHTML = `<i class="fa-solid fa-triangle-exclamation ml-1"></i> ${res.message}`;
                    }
                })
                .catch(err => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-arrows-rotate"></i> مزامنة وحفظ القنوات من هذا الرابط';
                    statusMsg.className = 'p-3.5 rounded-xl text-xs font-bold text-center bg-red-500/10 border border-red-500/30 text-red-400 block';
                    statusMsg.textContent = 'حدث خطأ أثناء المزامنة: ' + err;
                });
        }

        function handleSaveSettings(e) {
            e.preventDefault();
            const btn = document.getElementById('save-settings-btn');
            const statusMsg = document.getElementById('settings-status-msg');
            const form = document.getElementById('settings-form');

            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> جاري الحفظ...';

            const fd = new FormData(form);
            fd.append('action', 'save_settings');

            fetch('api.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> حفظ التعديلات';
                    statusMsg.classList.remove('hidden');

                    if (res.success) {
                        statusMsg.className = 'p-3.5 rounded-xl text-xs font-bold text-center bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 block';
                        statusMsg.textContent = res.message;
                        setTimeout(() => statusMsg.classList.add('hidden'), 3000);
                    } else {
                        statusMsg.className = 'p-3.5 rounded-xl text-xs font-bold text-center bg-red-500/10 border border-red-500/30 text-red-400 block';
                        statusMsg.textContent = res.message;
                    }
                });
        }

        function handleAddChannel(e) {
            e.preventDefault();
            const btn = document.getElementById('add-channel-btn');
            const msg = document.getElementById('add-channel-msg');
            const name = document.getElementById('add-ch-name').value.trim();
            const url = document.getElementById('add-ch-url').value.trim();
            const group = document.getElementById('add-ch-group').value.trim();
            const logo = document.getElementById('add-ch-logo').value.trim();
            const position = document.querySelector('input[name="add_ch_position"]:checked')?.value || 'top';

            if (!name || !url) {
                alert('يرجى ملء اسم القناة ورابط البث.');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> جاري إضافة القناة...';
            msg.classList.add('hidden');

            const fd = new FormData();
            fd.append('action', 'add_channel');
            fd.append('name', name);
            fd.append('url', url);
            fd.append('group', group);
            fd.append('logo', logo);
            fd.append('position', position);

            fetch('api.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-plus"></i> حفظ وإضافة القناة للمشغل';
                    msg.classList.remove('hidden');

                    if (res.success) {
                        msg.className = 'p-3.5 rounded-xl text-xs font-bold text-center bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 block';
                        msg.innerHTML = `<i class="fa-solid fa-circle-check ml-1"></i> ${res.message}`;
                        document.getElementById('add-ch-name').value = '';
                        document.getElementById('add-ch-url').value = '';
                        document.getElementById('add-ch-logo').value = '';
                        loadKpisAndGroups();
                        fetchAdminChannels(1);
                    } else {
                        msg.className = 'p-3.5 rounded-xl text-xs font-bold text-center bg-red-500/10 border border-red-500/30 text-red-400 block';
                        msg.innerHTML = `<i class="fa-solid fa-triangle-exclamation ml-1"></i> ${res.message}`;
                    }
                })
                .catch(err => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-plus"></i> حفظ وإضافة القناة للمشغل';
                    msg.className = 'p-3.5 rounded-xl text-xs font-bold text-center bg-red-500/10 border border-red-500/30 text-red-400 block';
                    msg.textContent = 'حدث خطأ: ' + err;
                });
        }

        function openEditModal(id, name, url, group, logo) {
            document.getElementById('edit-ch-id').value = id;
            document.getElementById('edit-ch-name').value = name;
            document.getElementById('edit-ch-url').value = url;
            document.getElementById('edit-ch-group').value = group || 'عام (General)';
            document.getElementById('edit-ch-logo').value = logo || '';
            document.getElementById('edit-channel-msg').classList.add('hidden');
            document.getElementById('edit-modal').classList.replace('hidden', 'flex');
        }

        function closeEditModal() {
            document.getElementById('edit-modal').classList.replace('flex', 'hidden');
        }

        function handleEditChannel(e) {
            e.preventDefault();
            const btn = document.getElementById('save-edit-btn');
            const msg = document.getElementById('edit-channel-msg');

            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> جاري الحفظ...';

            const fd = new FormData(document.getElementById('edit-channel-form'));
            fd.append('action', 'edit_channel');

            fetch('api.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> حفظ التعديلات';
                    msg.classList.remove('hidden');

                    if (res.success) {
                        msg.className = 'p-3 rounded-xl text-xs font-bold text-center bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 block';
                        msg.textContent = res.message;
                        fetchAdminChannels(currentPage);
                        loadKpisAndGroups();
                        setTimeout(() => closeEditModal(), 1200);
                    } else {
                        msg.className = 'p-3 rounded-xl text-xs font-bold text-center bg-red-500/10 border border-red-500/30 text-red-400 block';
                        msg.textContent = res.message;
                    }
                })
                .catch(err => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> حفظ التعديلات';
                    msg.className = 'p-3 rounded-xl text-xs font-bold text-center bg-red-500/10 border border-red-500/30 text-red-400 block';
                    msg.textContent = 'حدث خطأ: ' + err;
                });
        }

        function previewStream(channelName, streamUrl) {
            document.getElementById('preview-title').textContent = 'معاينة البث: ' + channelName;
            document.getElementById('preview-url-text').textContent = streamUrl;
            document.getElementById('preview-modal').classList.replace('hidden', 'flex');

            if (!previewPlayerInstance) {
                previewPlayerInstance = videojs('preview-player', {
                    autoplay: true,
                    controls: true,
                    fluid: true,
                    responsive: true
                });
            }

            previewPlayerInstance.src({
                src: streamUrl,
                type: 'application/x-mpegURL'
            });
            previewPlayerInstance.play().catch(e => console.log(e));
        }

        function closePreviewModal() {
            if (previewPlayerInstance) previewPlayerInstance.pause();
            document.getElementById('preview-modal').classList.replace('flex', 'hidden');
        }

        function switchTab(tabId) {
            document.querySelectorAll('.tab-btn').forEach(b => {
                b.classList.remove('bg-cyan-500', 'text-dark-950', 'shadow-lg', 'shadow-cyan-500/20');
                b.classList.add('bg-dark-900', 'text-slate-300', 'border', 'border-white/10');
            });
            document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));

            const activeBtn = document.getElementById('tab-btn-' + tabId);
            const activeContent = document.getElementById('tab-content-' + tabId);
            if (activeBtn) {
                activeBtn.classList.remove('bg-dark-900', 'text-slate-300', 'border', 'border-white/10');
                activeBtn.classList.add('bg-cyan-500', 'text-dark-950', 'shadow-lg', 'shadow-cyan-500/20');
            }
            if (activeContent) activeContent.classList.remove('hidden');
        }

        function handleLogout() {
            if (!confirm('هل تريد تسجيل الخروج؟')) return;
            const fd = new FormData();
            fd.append('action', 'logout');
            fetch('api.php', { method: 'POST', body: fd }).then(() => window.location.reload());
        }

        function escapeHtml(text) {
            return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        }
    </script>
<?php endif; ?>

</body>
</html>
