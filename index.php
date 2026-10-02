<?php
/**
 * IPTV Web Player Pro - Public Streaming Portal
 * High-performance, ultra-responsive dark web player for large M3U playlists.
 */

declare(strict_types=1);

define('CONFIG_FILE', __DIR__ . '/config.json');
define('CHANNELS_FILE', __DIR__ . '/channels.json');

function loadJson(string $path, array $fallback = []): array {
    if (!file_exists($path)) return $fallback;
    $content = @file_get_contents($path);
    if ($content === false) return $fallback;
    $data = json_decode($content, true);
    return is_array($data) ? $data : $fallback;
}

$config = loadJson(CONFIG_FILE, [
    'site_title' => 'VIP IPTV Web Player Pro',
    'announcement' => 'مرحباً بكم في منصة البث المباشر. استمتع بمشاهدة القنوات والأحداث الرياضية بجودة فائقة.',
    'announcement_active' => true,
    'default_channel_id' => 'ch_1'
]);

$siteTitle = htmlspecialchars($config['site_title'] ?? 'VIP IPTV Web Player Pro');
$announcement = htmlspecialchars($config['announcement'] ?? '');
$announcementActive = !empty($config['announcement_active']) && !empty($announcement);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">
    <title><?= $siteTitle ?> — البث المباشر</title>

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

    <!-- Video.js Player CSS & JS with HLS Support -->
    <link href="https://vjs.zencdn.net/8.10.0/video-js.css" rel="stylesheet" />
    <script src="https://vjs.zencdn.net/8.10.0/video.min.js"></script>

    <!-- Hls.js fallback -->
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>

    <!-- mpegts.js for MPEG-TS Streams -->
    <script src="https://cdn.jsdelivr.net/npm/mpegts.js@latest/dist/mpegts.js"></script>

    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #0b0f19;
            color: #f1f5f9;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
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

        .video-js {
            width: 100% !important;
            height: 100% !important;
            border-radius: 1rem;
            overflow: hidden;
            background-color: #070a12;
        }
        .video-js .vjs-big-play-button {
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) !important;
            background-color: rgba(6, 182, 212, 0.85) !important;
            border: 2px solid rgba(34, 211, 238, 0.8) !important;
            border-radius: 50% !important;
            width: 72px !important;
            height: 72px !important;
            line-height: 68px !important;
            box-shadow: 0 0 25px rgba(6, 182, 212, 0.5);
            transition: all 0.3s ease;
        }
        .video-js:hover .vjs-big-play-button {
            background-color: #06b6d4 !important;
            transform: translate(-50%, -50%) scale(1.1) !important;
        }
        .vjs-control-bar {
            background: linear-gradient(to top, rgba(7, 10, 18, 0.95), transparent) !important;
            height: 48px !important;
        }

        @keyframes pulse-glow {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(1.1); }
        }
        .pulse-live {
            animation: pulse-glow 2s infinite ease-in-out;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col antialiased selection:bg-cyan-500 selection:text-white">

    <!-- 1. شريط الإعلانات الترويجي (Announcement Bar) -->
    <?php if ($announcementActive): ?>
    <div id="announcement-bar" class="bg-gradient-to-r from-cyan-900/60 via-cyan-800/40 to-slate-900 border-b border-cyan-500/30 text-cyan-200 px-4 py-2 text-xs md:text-sm font-semibold flex items-center justify-between gap-3 shadow-inner">
        <div class="flex items-center gap-2 mx-auto">
            <span class="flex h-2 w-2 relative">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-cyan-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-cyan-500"></span>
            </span>
            <i class="fa-solid fa-bullhorn text-cyan-400"></i>
            <span><?= $announcement ?></span>
        </div>
        <button onclick="document.getElementById('announcement-bar').style.display='none'" class="text-cyan-400/70 hover:text-white transition p-1">
            <i class="fa-solid fa-xmark text-sm"></i>
        </button>
    </div>
    <?php endif; ?>

    <!-- 2. الهيدر العلوي (Top Header) -->
    <header class="bg-dark-800/90 backdrop-blur-md border-b border-white/10 sticky top-0 z-40 px-4 lg:px-8 py-3.5 transition-all">
        <div class="max-w-[1700px] mx-auto flex items-center justify-between gap-4">
            
            <a href="index.php" class="flex items-center gap-3 group">
                <div class="size-10 rounded-2xl bg-gradient-to-tr from-cyan-500 to-blue-600 flex items-center justify-center shadow-lg shadow-cyan-500/20 text-white group-hover:scale-105 transition-transform">
                    <i class="fa-solid fa-tv text-lg"></i>
                </div>
                <div>
                    <h1 class="font-extrabold text-lg md:text-xl text-white tracking-wide flex items-center gap-2">
                        <?= $siteTitle ?>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-cyan-500/20 text-cyan-300 border border-cyan-500/30">VIP</span>
                    </h1>
                    <p class="text-[11px] text-slate-400 hidden sm:block">أكثر من 20,000 قناة وباقة عالمية مباشرة</p>
                </div>
            </a>

            <div class="flex items-center gap-3">
                <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white/5 border border-white/10 text-xs text-slate-300">
                    <i class="fa-solid fa-satellite-dish text-cyan-400"></i>
                    <span>إجمالي القنوات:</span>
                    <span class="font-bold text-white font-mono" id="total-channels-badge">جاري التحميل...</span>
                </div>

                <a href="admin.php" class="flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-white/10 hover:bg-cyan-500/20 hover:border-cyan-500/40 border border-white/10 text-xs font-bold text-slate-200 hover:text-cyan-300 transition-all shadow-sm">
                    <i class="fa-solid fa-lock text-cyan-400"></i>
                    <span>لوحة التحكم</span>
                </a>
            </div>

        </div>
    </header>

    <!-- 3. مساحة العمل الرئيسية (Main Layout) -->
    <main class="flex-1 max-w-[1700px] w-full mx-auto p-3 sm:p-4 lg:p-6 grid grid-cols-1 lg:grid-cols-12 gap-5 lg:gap-6">

        <!-- ========================================== -->
        <!-- قسم مشغل الفيديو (Video Player Area) [8 cols] -->
        <!-- ========================================== -->
        <section class="lg:col-span-8 flex flex-col gap-4">
            
            <div class="relative w-full aspect-video bg-dark-900 rounded-2xl overflow-hidden border border-white/10 shadow-2xl shadow-cyan-950/20 flex items-center justify-center group">
                
                <video id="iptv-player" 
                       class="w-full h-full rounded-2xl bg-black object-contain shadow-2xl"
                       controls 
                       autoplay
                       preload="auto"
                       playsinline
                       webkit-playsinline
                       poster="https://images.unsplash.com/photo-1593784991095-a205069470b6?q=80&w=1200&auto=format&fit=crop">
                </video>

                <div id="player-error-overlay" class="absolute inset-0 bg-dark-900/95 backdrop-blur-md hidden flex-col items-center justify-center p-6 text-center z-20">
                    <div class="size-16 rounded-full bg-cyan-500/10 border border-cyan-500/30 text-cyan-400 flex items-center justify-center mb-3 text-2xl">
                        <i class="fa-solid fa-circle-play"></i>
                    </div>
                    <h3 class="text-base sm:text-lg font-bold text-white mb-1">تعذر تشغيل هذا البث مباشرة</h3>
                    <p class="text-xs text-slate-400 max-w-md mb-4 leading-relaxed" id="error-details">
                        يمكنك إعادة محاولة التشغيل فوراً أو اختيار قناة أخرى من القائمة:
                    </p>
                    <div class="flex flex-wrap items-center justify-center gap-2 max-w-lg">
                        <button onclick="reloadCurrentStream()" class="px-4 py-2.5 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-dark-900 font-bold text-xs transition flex items-center gap-1.5 shadow-md shadow-cyan-500/20">
                            <i class="fa-solid fa-rotate-right"></i>
                            <span>إعادة محاولة التشغيل</span>
                        </button>
                        <button onclick="setStreamMode('proxy_ts')" class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs transition flex items-center gap-1.5">
                            <i class="fa-solid fa-bolt"></i>
                            <span>تحديث مشغل MPEG-TS</span>
                        </button>
                    </div>
                </div>

            </div>

            <!-- شريط خيارات تشغيل البث والبروكسي (IBO Player Proxy & External Controls) -->
            <div class="bg-dark-800/80 rounded-2xl p-3 border border-white/10 flex flex-wrap items-center justify-between gap-3 text-xs">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-slate-400 font-bold text-[11px] flex items-center gap-1.5 ml-1">
                        <i class="fa-solid fa-sliders text-cyan-400"></i>
                        <span>وضع البث:</span>
                    </span>
                    <button type="button" onclick="setStreamMode('proxy_ts')" data-mode="proxy_ts" class="mode-btn px-3 py-1.5 rounded-xl bg-cyan-500 text-dark-900 font-bold text-xs shadow-md shadow-cyan-500/20 transition flex items-center gap-1.5">
                        <i class="fa-solid fa-play"></i>
                        <span>بروكسي IBO Player (MPEG-TS)</span>
                        <span class="text-[9px] bg-dark-900/20 px-1.5 py-0.5 rounded-full font-black">مستحسن</span>
                    </button>
                    <button type="button" onclick="setStreamMode('direct')" data-mode="direct" class="mode-btn px-3 py-1.5 rounded-xl bg-white/5 hover:bg-white/10 text-slate-300 border border-white/10 text-xs font-semibold transition flex items-center gap-1.5">
                        <i class="fa-solid fa-link"></i>
                        <span>مباشر (Direct)</span>
                    </button>
                    <button type="button" onclick="setStreamMode('proxy_hls')" data-mode="proxy_hls" class="mode-btn px-3 py-1.5 rounded-xl bg-white/5 hover:bg-white/10 text-slate-300 border border-white/10 text-xs font-semibold transition flex items-center gap-1.5">
                        <i class="fa-solid fa-bolt"></i>
                        <span>بروكسي HLS</span>
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" onclick="reloadCurrentStream()" class="px-3 py-1.5 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-slate-300 hover:text-white font-bold text-xs transition flex items-center gap-1.5">
                        <i class="fa-solid fa-rotate-right"></i>
                        <span>تحديث البث</span>
                    </button>
                    <a id="download-single-m3u" href="#" download="channel.m3u" class="px-2.5 py-1.5 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-slate-300 hover:text-white text-xs transition flex items-center gap-1" title="تحميل ملف M3U لهذه القناة">
                        <i class="fa-solid fa-download"></i>
                        <span class="hidden sm:inline">تحميل .m3u</span>
                    </a>
                </div>
            </div>

            <!-- بطاقة تفاصيل القناة المشغلة حالياً والتحكم السريع -->
            <div class="bg-dark-800/90 rounded-2xl p-4 sm:p-5 border border-white/10 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-lg">
                <div class="flex items-center gap-4 w-full sm:w-auto">
                    <div class="size-14 sm:size-16 rounded-2xl bg-white/5 border border-white/10 p-1.5 flex items-center justify-center shrink-0 overflow-hidden shadow-inner">
                        <img id="current-channel-logo" 
                             src="https://placehold.co/100x100/1e293b/38bdf8?text=TV" 
                             alt="Channel Logo"
                             class="max-h-full max-w-full object-contain"
                             onerror="this.src='https://placehold.co/100x100/1e293b/38bdf8?text=TV'">
                    </div>
                    <div>
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-red-500/20 border border-red-500/30 text-red-400 text-[10px] font-extrabold uppercase tracking-wider">
                                <span class="size-1.5 rounded-full bg-red-500 pulse-live"></span>
                                مباشر LIVE
                            </span>
                            <span id="current-channel-group" class="text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-white/5 text-cyan-300 border border-white/10">
                                عام
                            </span>
                        </div>
                        <h2 id="current-channel-title" class="font-extrabold text-lg sm:text-xl text-white mt-1">
                            اختر قناة للمشاهدة
                        </h2>
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0 w-full sm:w-auto justify-end border-t sm:border-t-0 pt-3 sm:pt-0 border-white/10">
                    <button onclick="reloadCurrentStream()" title="تحديث البث" class="size-10 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-slate-300 hover:text-white flex items-center justify-center transition">
                        <i class="fa-solid fa-arrows-rotate"></i>
                    </button>
                    <button onclick="copyStreamUrl()" title="نسخ رابط البث" class="size-10 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-slate-300 hover:text-cyan-400 flex items-center justify-center transition">
                        <i class="fa-solid fa-link"></i>
                    </button>
                    <button onclick="togglePlayerFullscreen()" title="ملء الشاشة" class="px-3.5 h-10 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-dark-900 font-black text-xs flex items-center gap-2 shadow-lg shadow-cyan-500/20 transition">
                        <i class="fa-solid fa-expand"></i>
                        <span class="hidden sm:inline">تكبير الشاشة</span>
                    </button>
                </div>
            </div>

            <!-- تنبيه سرعة الإنترنت وملاحظة التوافقية -->
            <div class="bg-cyan-500/5 rounded-2xl p-3.5 border border-cyan-500/20 text-xs text-slate-400 flex items-center gap-3">
                <i class="fa-solid fa-circle-info text-cyan-400 text-sm shrink-0"></i>
                <p class="leading-relaxed">
                    يعمل المشغل بتقنية HLS فائقة الثبات مع وكيل <strong class="text-cyan-300">IBO Player</strong> لتجاوز حظر المتصفحات وتشغيل كافة القنوات فوراً.
                </p>
            </div>

        </section>

        <!-- ========================================== -->
        <!-- قسم قائمة القنوات الذكية (Channels Playlist) [4 cols] -->
        <!-- ========================================== -->
        <aside class="lg:col-span-4 flex flex-col bg-dark-800/90 rounded-2xl border border-white/10 shadow-xl overflow-hidden h-[750px] lg:h-auto lg:max-h-[calc(100vh-120px)]">
            
            <div class="p-4 border-b border-white/10 space-y-3 bg-dark-800">
                <div class="flex items-center justify-between">
                    <h3 class="font-extrabold text-sm text-white flex items-center gap-2">
                        <i class="fa-solid fa-list-ul text-cyan-400"></i>
                        <span>قائمة القنوات</span>
                    </h3>
                    <span id="filtered-count-badge" class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-cyan-500/10 text-cyan-300 font-mono">
                        جاري التحميل...
                    </span>
                </div>

                <!-- صندوق البحث الحي في كافة القنوات -->
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" 
                           id="search-input" 
                           placeholder="ابحث بالاسم في أكثر من 20,000 قناة..." 
                           class="w-full bg-dark-900 border border-white/10 rounded-xl pr-9 pl-8 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-cyan-500/60 focus:ring-1 focus:ring-cyan-500/60 transition">
                    <button id="clear-search-btn" onclick="clearSearch()" class="hidden absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white text-xs">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </button>
                </div>

                <!-- القائمة المنسدلة للباقات -->
                <div class="relative">
                    <select id="group-filter" onchange="onGroupChange()" class="w-full bg-dark-900 border border-white/10 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-cyan-500/60 transition appearance-none cursor-pointer">
                        <option value="">جاري تحميل الباقات والتصنيفات...</option>
                    </select>
                    <i class="fa-solid fa-chevron-down absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 text-[10px] pointer-events-none"></i>
                </div>
            </div>

            <!-- قائمة القنوات القابلة للتمرير -->
            <div id="channels-container" class="flex-1 overflow-y-auto custom-scrollbar p-3 space-y-2">
                <div id="channels-loading" class="h-64 flex flex-col items-center justify-center text-center p-6 text-slate-400">
                    <i class="fa-solid fa-circle-notch fa-spin text-3xl text-cyan-400 mb-3"></i>
                    <p class="text-xs font-bold text-slate-300">جاري تحميل القنوات من السيرفر...</p>
                </div>
            </div>

            <!-- الفوتر المصغر للقائمة -->
            <div class="p-3 border-t border-white/10 bg-dark-900/50 flex items-center justify-between text-[11px] text-slate-400">
                <span id="current-group-label">اختر باقة لعرض قنواتها</span>
                <button onclick="scrollToTopChannels()" class="text-cyan-400 hover:underline">
                    <i class="fa-solid fa-arrow-up ml-1"></i>
                    الأعلى
                </button>
            </div>

        </aside>

    </main>

    <footer class="mt-auto border-t border-white/10 bg-dark-900/80 py-4 px-6 text-center text-xs text-slate-500">
        <p>© <?= date('Y') ?> <?= $siteTitle ?> — نظام البث المباشر الذكي الخفيف. جميع الحقوق محفوظة.</p>
    </footer>

    <!-- ======================================================== -->
    <!-- جافاسكربت تشغيل الفيديو والتحكم بالقنوات (JavaScript Logic) -->
    <!-- ======================================================== -->
    <script>
        let currentChannel = { id: '', name: '', url: '', logo: '', group: '' };
        let player = null;
        let searchTimeout = null;
        let allGroups = [];

        document.addEventListener('DOMContentLoaded', function() {
            initVideoPlayer();

            // Set HTTP link
            const httpLink = document.getElementById('http-switch-link');
            if (httpLink) {
                httpLink.href = window.location.href.replace('https://', 'http://');
            }

            // Load groups and initial channels
            loadGroupsAndInitialChannels();

            // Setup search listener
            const searchInput = document.getElementById('search-input');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const val = this.value.trim();
                    const clearBtn = document.getElementById('clear-search-btn');
                    if (val.length > 0) {
                        clearBtn.classList.remove('hidden');
                    } else {
                        clearBtn.classList.add('hidden');
                    }

                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        searchChannels(val);
                    }, 300);
                });
            }
        });

        function initVideoPlayer() {
            const videoEl = document.getElementById('iptv-player');
            if (!videoEl) return;

            videoEl.addEventListener('playing', function() {
                hidePlayerError();
            });
            videoEl.addEventListener('play', function() {
                hidePlayerError();
            });
        }

        let mpegtsPlayer = null;
        let streamMode = 'proxy_ts';
        try {
            if (localStorage.getItem('iptv_stream_mode') === 'proxy_hls') {
                localStorage.setItem('iptv_stream_mode', 'proxy_ts');
            }
            streamMode = localStorage.getItem('iptv_stream_mode') || 'proxy_ts';
        } catch(e) {}

        function setStreamMode(mode) {
            streamMode = mode;
            try { localStorage.setItem('iptv_stream_mode', mode); } catch(e) {}
            updateStreamModeUI();
            if (currentChannel && currentChannel.url) {
                playStream(currentChannel.url);
            }
        }

        function updateStreamModeUI() {
            document.querySelectorAll('.mode-btn').forEach(btn => {
                const m = btn.getAttribute('data-mode');
                if (m === streamMode) {
                    btn.className = 'mode-btn px-3 py-1.5 rounded-xl bg-cyan-500 text-dark-900 font-bold text-xs shadow-md shadow-cyan-500/20 transition flex items-center gap-1.5';
                } else {
                    btn.className = 'mode-btn px-3 py-1.5 rounded-xl bg-white/5 hover:bg-white/10 text-slate-300 border border-white/10 text-xs font-semibold transition flex items-center gap-1.5';
                }
            });
        }

        function playStream(streamUrl) {
            hidePlayerError();
            if (!streamUrl) return;

            // Destroy previous mpegts player if any
            if (mpegtsPlayer) {
                try {
                    mpegtsPlayer.pause();
                    mpegtsPlayer.unload();
                    mpegtsPlayer.detachMediaElement();
                    mpegtsPlayer.destroy();
                } catch(e) {}
                mpegtsPlayer = null;
            }

            const videoEl = document.getElementById('iptv-player');
            if (!videoEl) return;

            let finalUrl = streamUrl;
            let isHls = streamUrl.toLowerCase().includes('.m3u8');

            if (streamMode === 'proxy_ts') {
                finalUrl = 'api.php?action=proxy&url=' + encodeURIComponent(streamUrl);
            } else if (streamMode === 'direct') {
                finalUrl = streamUrl;
            } else {
                finalUrl = 'api.php?action=proxy&url=' + encodeURIComponent(streamUrl);
            }

            // 1. Play with mpegts.js (Recommended for MPEG-TS streams)
            if (!isHls && window.mpegts && mpegts.isSupported()) {
                try {
                    mpegtsPlayer = mpegts.createPlayer({
                        type: 'mpegts',
                        isLive: true,
                        url: finalUrl
                    }, {
                        enableWorker: false,
                        lazyLoad: false,
                        liveBufferLatencyChasing: true,
                        autoCleanupSourceBuffer: true,
                        enableStashBuffer: false,
                        stashInitialSize: 128
                    });
                    mpegtsPlayer.attachMediaElement(videoEl);
                    mpegtsPlayer.load();
                    const p = mpegtsPlayer.play();
                    if (p !== undefined) {
                        p.catch(function(e) {
                            console.log("Autoplay unmuted blocked by browser, trying muted:", e);
                            videoEl.muted = true;
                            mpegtsPlayer.play().catch(function(err) { console.log(err); });
                        });
                    }
                    mpegtsPlayer.on(mpegts.Events.ERROR, function(type, detail, info) {
                        console.warn("mpegts error:", type, detail, info);
                        if (videoEl.paused || videoEl.readyState < 2) {
                            showPlayerError();
                        }
                    });
                    return;
                } catch(err) {
                    console.warn("mpegts init failed:", err);
                }
            }

            // Fallback to HTML5
            videoEl.src = finalUrl;
            const p2 = videoEl.play();
            if (p2 !== undefined) {
                p2.catch(function(e) {
                    videoEl.muted = true;
                    videoEl.play().catch(function(err) { console.log(err); });
                });
            }
        }

        // Fetch groups list
        function loadGroupsAndInitialChannels() {
            fetch('api.php?action=get_groups&_t=' + Date.now())
                .then(r => r.json())
                .then(res => {
                    if (res.success && res.groups && res.groups.length > 0) {
                        allGroups = res.groups;
                        document.getElementById('total-channels-badge').textContent = res.total_channels.toLocaleString() + ' قناة';
                        
                        populateGroupsDropdown(res.groups);

                        // Pick first Arabic / Sports / beIN group if present, or first group
                        let targetGroup = res.groups.find(g => g.name.includes('BEIN') || g.name.includes('ARAB') || g.name.includes('LALIGA'));
                        if (!targetGroup) targetGroup = res.groups[0];

                        document.getElementById('group-filter').value = targetGroup.name;
                        loadChannelsByGroup(targetGroup.name);
                    }
                })
                .catch(err => {
                    console.error("Error loading groups:", err);
                });
        }

        function populateGroupsDropdown(groups) {
            const select = document.getElementById('group-filter');
            select.innerHTML = '';

            groups.forEach(g => {
                const opt = document.createElement('option');
                opt.value = g.name;
                opt.textContent = `${g.name} (${g.visible} قناة)`;
                select.appendChild(opt);
            });
        }

        function onGroupChange() {
            const selGroup = document.getElementById('group-filter').value;
            document.getElementById('search-input').value = '';
            document.getElementById('clear-search-btn').classList.add('hidden');
            loadChannelsByGroup(selGroup);
        }

        function loadChannelsByGroup(groupName) {
            const container = document.getElementById('channels-container');
            container.innerHTML = `
                <div class="h-64 flex flex-col items-center justify-center text-center p-6 text-slate-400">
                    <i class="fa-solid fa-circle-notch fa-spin text-3xl text-cyan-400 mb-3"></i>
                    <p class="text-xs font-bold text-slate-300">جاري تحميل قنوات ${groupName}...</p>
                </div>
            `;
            document.getElementById('current-group-label').textContent = groupName;

            fetch('api.php?action=get_channels&limit=250&group=' + encodeURIComponent(groupName) + '&_t=' + Date.now())
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        renderChannelsList(res.channels);
                        document.getElementById('filtered-count-badge').textContent = res.total + ' قناة';

                        // If no channel is currently playing, play the first one
                        if (!currentChannel.id && res.channels.length > 0) {
                            selectChannelDirect(res.channels[0]);
                        }
                    }
                })
                .catch(err => {
                    container.innerHTML = `<div class="p-6 text-center text-red-400 text-xs font-bold">فشل تحميل القنوات.</div>`;
                });
        }

        function searchChannels(query) {
            if (!query) {
                onGroupChange();
                return;
            }

            const container = document.getElementById('channels-container');
            container.innerHTML = `
                <div class="h-64 flex flex-col items-center justify-center text-center p-6 text-slate-400">
                    <i class="fa-solid fa-circle-notch fa-spin text-3xl text-cyan-400 mb-3"></i>
                    <p class="text-xs font-bold text-slate-300">جاري البحث في كافة القنوات...</p>
                </div>
            `;

            fetch('api.php?action=get_channels&limit=150&q=' + encodeURIComponent(query) + '&_t=' + Date.now())
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        renderChannelsList(res.channels);
                        document.getElementById('filtered-count-badge').textContent = res.total + ' نتيجة';
                        document.getElementById('current-group-label').textContent = `نتائج البحث: "${query}"`;
                    }
                })
                .catch(err => {
                    container.innerHTML = `<div class="p-6 text-center text-red-400 text-xs font-bold">فشل البحث.</div>`;
                });
        }

        function renderChannelsList(channels) {
            const container = document.getElementById('channels-container');
            container.innerHTML = '';

            if (!channels || channels.length === 0) {
                container.innerHTML = `
                    <div class="h-48 flex flex-col items-center justify-center text-center p-6 text-slate-400">
                        <i class="fa-solid fa-ban text-3xl text-slate-600 mb-2"></i>
                        <p class="text-xs font-bold text-slate-300">لم يتم العثور على أي قنوات</p>
                    </div>
                `;
                return;
            }

            channels.forEach(ch => {
                const isCurrent = (currentChannel.id === ch.id);
                const logo = ch.logo || 'https://placehold.co/100x100/1e293b/38bdf8?text=TV';

                const card = document.createElement('article');
                card.id = `channel-card-${ch.id}`;
                card.className = `channel-item group cursor-pointer p-2.5 rounded-xl border transition-all duration-200 flex items-center justify-between gap-3 ${isCurrent ? 'bg-cyan-500/15 border-cyan-500/50 shadow-md shadow-cyan-500/10' : 'bg-dark-900/50 hover:bg-white/[0.04] border-white/5 hover:border-white/10'}`;
                
                card.innerHTML = `
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="size-11 rounded-lg bg-dark-800 border border-white/10 p-1 flex items-center justify-center shrink-0 overflow-hidden">
                            <img src="${logo}" alt="${ch.name}" loading="lazy" class="max-h-full max-w-full object-contain" onerror="this.src='https://placehold.co/100x100/1e293b/38bdf8?text=TV'">
                        </div>
                        <div class="min-w-0">
                            <h4 class="font-bold text-xs text-white truncate group-hover:text-cyan-300 transition">${ch.name}</h4>
                            <span class="text-[10px] text-slate-400 truncate block mt-0.5">${ch.group || 'عام'}</span>
                        </div>
                    </div>
                    <div class="shrink-0 flex items-center">
                        <span class="active-indicator ${isCurrent ? 'flex' : 'hidden'} items-center gap-1 text-[10px] font-extrabold text-cyan-400 bg-cyan-500/10 px-2 py-0.5 rounded-md border border-cyan-500/20">
                            <i class="fa-solid fa-volume-high text-[10px] pulse-live"></i>
                            <span>شغال</span>
                        </span>
                        <span class="play-hint ${isCurrent ? 'hidden' : 'block'} text-slate-500 group-hover:text-cyan-400 text-xs transition">
                            <i class="fa-regular fa-circle-play"></i>
                        </span>
                    </div>
                `;

                card.onclick = () => selectChannelDirect(ch);
                container.appendChild(card);
            });
        }

        function selectChannelDirect(ch) {
            currentChannel = ch;

            document.getElementById('current-channel-title').textContent = ch.name;
            document.getElementById('current-channel-group').textContent = ch.group || 'عام';
            document.getElementById('current-channel-logo').src = ch.logo || 'https://placehold.co/100x100/1e293b/38bdf8?text=TV';

            document.querySelectorAll('.channel-item').forEach(el => {
                el.classList.remove('bg-cyan-500/15', 'border-cyan-500/50', 'shadow-md', 'shadow-cyan-500/10');
                el.classList.add('bg-dark-900/50', 'border-white/5');
                const ind = el.querySelector('.active-indicator');
                const hint = el.querySelector('.play-hint');
                if (ind) ind.classList.replace('flex', 'hidden');
                if (hint) hint.classList.replace('hidden', 'block');
            });

            const activeCard = document.getElementById(`channel-card-${ch.id}`);
            if (activeCard) {
                activeCard.classList.remove('bg-dark-900/50', 'border-white/5');
                activeCard.classList.add('bg-cyan-500/15', 'border-cyan-500/50', 'shadow-md', 'shadow-cyan-500/10');
                const activeInd = activeCard.querySelector('.active-indicator');
                const activeHint = activeCard.querySelector('.play-hint');
                if (activeInd) activeInd.classList.replace('hidden', 'flex');
                if (activeHint) activeHint.classList.replace('block', 'hidden');
            }

            // Update single M3U download link
            const downloadBtn = document.getElementById('download-single-m3u');
            if (downloadBtn) {
                downloadBtn.href = `api.php?action=channel_m3u&id=${encodeURIComponent(ch.id)}`;
            }

            playStream(ch.url);
        }

        function clearSearch() {
            const input = document.getElementById('search-input');
            input.value = '';
            document.getElementById('clear-search-btn').classList.add('hidden');
            onGroupChange();
            input.focus();
        }

        function reloadCurrentStream() {
            if (currentChannel.url) playStream(currentChannel.url);
        }

        function showPlayerError() {
            document.getElementById('player-error-overlay').classList.replace('hidden', 'flex');
        }

        function hidePlayerError() {
            document.getElementById('player-error-overlay').classList.replace('flex', 'hidden');
        }

        function copyStreamUrl() {
            if (!currentChannel.url) return;
            navigator.clipboard.writeText(currentChannel.url).then(() => alert('تم نسخ رابط البث المباشر!')).catch(() => prompt('رابط البث:', currentChannel.url));
        }

        function togglePlayerFullscreen() {
            if (player) {
                if (player.isFullscreen()) player.exitFullscreen();
                else player.requestFullscreen();
            }
        }

        function scrollToTopChannels() {
            document.getElementById('channels-container').scrollTo({ top: 0, behavior: 'smooth' });
        }
    </script>

    
    </div>
</body>
</html>
