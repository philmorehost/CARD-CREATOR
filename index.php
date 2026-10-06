<?php
require_once __DIR__ . '/core/helpers.php';

if (!is_installed()) {
    header('Location: installer/index.php');
    exit;
}

$siteTitle = get_setting('site_title', 'CARD-CREATOR');
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['user_role'] ?? 'guest';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($siteTitle) ?> - Premium ID & Business Card SaaS</title>
    <link rel="icon" type="image/svg+xml" href="favicon.php">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col selection:bg-blue-500 selection:text-white">

    <!-- NAVIGATION HEADER -->
    <header class="border-b border-slate-800/80 bg-slate-900/60 backdrop-blur-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center font-black text-white text-xl shadow-lg shadow-blue-500/30">
                    <?= htmlspecialchars(strtoupper(substr($siteTitle, 0, 1))) ?>
                </div>
                <div>
                    <h1 class="font-extrabold text-white text-lg tracking-tight"><?= htmlspecialchars($siteTitle) ?></h1>
                    <span class="text-xs text-blue-400 font-semibold tracking-wider uppercase">Card Creator Studio</span>
                </div>
            </div>

            <div class="flex items-center space-x-4">
                <?php if ($isLoggedIn): ?>
                    <a href="studio.php" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-blue-500/25 transition">
                        Launch Studio 🎨
                    </a>
                    <?php if ($userRole === 'admin'): ?>
                        <a href="admin/dashboard.php" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs rounded-xl border border-slate-700 transition">
                            Admin Console ⚙
                        </a>
                    <?php endif; ?>
                    <a href="admin/logout.php" class="text-xs text-slate-400 hover:text-red-400 transition">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs rounded-xl border border-slate-700 transition">
                        Sign In / Staff Portal
                    </a>
                    <a href="studio.php" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-blue-500/25 transition">
                        Open Card Creator
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- HERO SECTION -->
    <section class="relative py-20 px-6 overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-blue-900/20 via-slate-950 to-slate-950 -z-10"></div>

        <div class="max-w-5xl mx-auto text-center space-y-8">
            <div class="inline-flex items-center space-x-2 px-4 py-1.5 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 text-xs font-semibold">
                <span>✨ Vector Rendered HD ID & Business Cards Studio</span>
            </div>

            <h1 class="text-4xl sm:text-6xl font-black text-white tracking-tight leading-tight">
                Design & Generate Premium <br>
                <span class="bg-gradient-to-r from-blue-400 via-indigo-300 to-emerald-400 bg-clip-text text-transparent">ID Cards & Business Cards</span>
            </h1>

            <p class="text-slate-400 max-w-2xl mx-auto text-base sm:text-lg">
                Create high-resolution corporate identity badges and executive business cards in seconds. Live HTML5 dual-canvas rendering, custom dynamic colors, company logo branding, and instant PNG/JPG/PDF exports.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-4">
                <a href="studio.php" class="w-full sm:w-auto px-8 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-extrabold text-sm rounded-2xl shadow-xl shadow-blue-600/30 transition transform hover:-translate-y-0.5">
                    Start Card Design Studio →
                </a>
                <a href="login.php" class="w-full sm:w-auto px-8 py-4 bg-slate-900 hover:bg-slate-800 text-slate-300 font-extrabold text-sm rounded-2xl border border-slate-800 transition">
                    Staff & Admin Sign In
                </a>
            </div>
        </div>
    </section>

    <!-- TEMPLATES PREVIEW SHOWCASE -->
    <section class="py-16 px-6 bg-slate-900/50 border-y border-slate-800/80">
        <div class="max-w-7xl mx-auto space-y-10">
            <div class="text-center space-y-2">
                <h2 class="text-2xl sm:text-3xl font-extrabold text-white">Seed Premium Card Templates</h2>
                <p class="text-slate-400 text-sm">Choose from 8 pre-seeded vector designs for corporate ID badges and executive business cards.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Template 1 -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 hover:border-blue-500/50 transition group space-y-4">
                    <div class="h-44 rounded-xl bg-gradient-to-b from-blue-900/60 to-slate-950 border border-slate-800 flex items-center justify-center p-4 relative overflow-hidden">
                        <div class="w-24 h-36 bg-white/90 rounded-lg shadow-2xl p-2 flex flex-col items-center justify-between text-[7px] text-slate-800 text-center font-bold">
                            <div class="w-full bg-blue-600 text-white py-1 rounded-t">GLOBAL TECH</div>
                            <div class="w-8 h-8 rounded bg-slate-300"></div>
                            <div>ALEXANDER PIERCE<br><span class="text-[6px] text-blue-600">DIRECTOR</span></div>
                        </div>
                    </div>
                    <div>
                        <span class="text-[10px] font-extrabold text-blue-400 uppercase tracking-wider">ID Card · Portrait</span>
                        <h3 class="font-bold text-white text-sm group-hover:text-blue-400 transition">Corporate Executive Badge</h3>
                    </div>
                </div>

                <!-- Template 2 -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 hover:border-blue-500/50 transition group space-y-4">
                    <div class="h-44 rounded-xl bg-gradient-to-b from-indigo-900/60 to-slate-950 border border-slate-800 flex items-center justify-center p-4 relative overflow-hidden">
                        <div class="w-24 h-36 bg-slate-900 rounded-lg shadow-2xl p-2 border border-indigo-500/30 flex flex-col items-center justify-between text-[7px] text-slate-200 text-center font-bold">
                            <div class="w-full bg-indigo-600 text-white py-1 rounded-t">CYBER DYNE</div>
                            <div class="w-8 h-8 rounded-full bg-indigo-400/30 border border-indigo-400"></div>
                            <div>SARAH CONNOR<br><span class="text-[6px] text-indigo-400">SECURITY ADMIN</span></div>
                        </div>
                    </div>
                    <div>
                        <span class="text-[10px] font-extrabold text-indigo-400 uppercase tracking-wider">ID Card · Portrait</span>
                        <h3 class="font-bold text-white text-sm group-hover:text-indigo-400 transition">Modern Tech Dark Badge</h3>
                    </div>
                </div>

                <!-- Template 3 -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 hover:border-blue-500/50 transition group space-y-4">
                    <div class="h-44 rounded-xl bg-gradient-to-b from-slate-900 to-slate-950 border border-slate-800 flex items-center justify-center p-4 relative overflow-hidden">
                        <div class="w-36 h-24 bg-white rounded-lg shadow-2xl p-2 border-l-4 border-amber-500 flex flex-col justify-between text-[7px] text-slate-800 font-bold">
                            <div class="text-left">APEX INC.</div>
                            <div>
                                <p class="text-[8px]">VICTORIA CROSS</p>
                                <p class="text-[6px] text-amber-600">MANAGING DIRECTOR</p>
                            </div>
                        </div>
                    </div>
                    <div>
                        <span class="text-[10px] font-extrabold text-amber-400 uppercase tracking-wider">Business Card · Landscape</span>
                        <h3 class="font-bold text-white text-sm group-hover:text-amber-400 transition">Minimalist Luxury Gold</h3>
                    </div>
                </div>

                <!-- Template 4 -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 hover:border-blue-500/50 transition group space-y-4">
                    <div class="h-44 rounded-xl bg-gradient-to-b from-emerald-900/60 to-slate-950 border border-slate-800 flex items-center justify-center p-4 relative overflow-hidden">
                        <div class="w-36 h-24 bg-slate-950 rounded-lg shadow-2xl p-2 border border-emerald-500/40 flex flex-col justify-between text-[7px] text-slate-200 font-bold">
                            <div class="text-right text-emerald-400">NEXTGEN LABS</div>
                            <div>
                                <p class="text-[8px]">DR. EMETT BROWN</p>
                                <p class="text-[6px] text-slate-400">CHIEF SCIENTIST</p>
                            </div>
                        </div>
                    </div>
                    <div>
                        <span class="text-[10px] font-extrabold text-emerald-400 uppercase tracking-wider">Business Card · Landscape</span>
                        <h3 class="font-bold text-white text-sm group-hover:text-emerald-400 transition">Dark Neon Emerald</h3>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FEATURES LIST -->
    <section class="py-20 px-6">
        <div class="max-w-7xl mx-auto space-y-12">
            <div class="text-center space-y-2">
                <h2 class="text-2xl sm:text-3xl font-extrabold text-white">Engineered for High Performance & Security</h2>
                <p class="text-slate-400 text-sm">Packed with enterprise-grade card design tools, automated compression, and anti-brute-force firewalling.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="p-6 bg-slate-900/80 border border-slate-800 rounded-2xl space-y-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-600/20 text-blue-400 flex items-center justify-center text-xl font-bold">🎛</div>
                    <h3 class="font-bold text-white text-base">Live Dual-Canvas Builder</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">Simultaneously edit dynamic front and back views with instant vector rendering on high-DPI HTML5 canvas workspace.</p>
                </div>

                <div class="p-6 bg-slate-900/80 border border-slate-800 rounded-2xl space-y-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-600/20 text-indigo-400 flex items-center justify-center text-xl font-bold">🎨</div>
                    <h3 class="font-bold text-white text-base">Primary & Secondary Palette</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">Customize primary accent colors, gradient highlights, company logos, and custom typography for brand alignment.</p>
                </div>

                <div class="p-6 bg-slate-900/80 border border-slate-800 rounded-2xl space-y-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-600/20 text-emerald-400 flex items-center justify-center text-xl font-bold">⚡</div>
                    <h3 class="font-bold text-white text-base">One-Click Combined Export</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">Export front and back sides stitched into single high-resolution PNG/JPG images or 2-page print-ready PDF documents.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="mt-auto border-t border-slate-800/80 bg-slate-900/80 py-8 px-6 text-center text-xs text-slate-500">
        <p>© <?= date('Y') ?> <?= htmlspecialchars($siteTitle) ?>. All rights reserved. Built with PHP Vanilla & Canvas Engine.</p>
    </footer>

</body>
</html>
