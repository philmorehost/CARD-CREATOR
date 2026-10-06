<?php
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/security.php';

if (!is_installed()) {
    header('Location: ../installer/index.php');
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$pdo = get_db_connection();
$message = '';
$messageType = '';

// Handle Settings Update / Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token (CSRF). Please refresh and try again.';
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

    if (is_demo_mode() && !($action === 'save_settings' && isset($_POST['demo_mode']) && $_POST['demo_mode'] === '0')) {
        $message = 'System is currently running in DEMO MODE. Changes are disabled.';
        $messageType = 'error';
    } else {
        if ($action === 'save_settings') {
            $settingsKeys = [
                'site_title', 'demo_mode', 'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass',
                'security_user_protection', 'security_ip_protection', 'security_brute_period_mins',
                'security_max_account_failures', 'security_max_ip_failures', 'security_ip_block_duration',
                'security_lock_admin_user', 'security_notify_unwhitelisted_login', 'security_notify_bruteforce'
            ];

            foreach ($settingsKeys as $key) {
                if (isset($_POST[$key])) {
                    set_setting($key, trim($_POST[$key]));
                } else {
                    // Checkbox uncheck default to '0'
                    if (strpos($key, 'security_') === 0 || $key === 'demo_mode') {
                        set_setting($key, '0');
                    }
                }
            }
            $message = 'Settings updated successfully!';
            $messageType = 'success';
        } elseif ($action === 'unblock_ip') {
            $ipId = intval($_POST['ip_id'] ?? 0);
            if ($ipId > 0 && $pdo) {
                $stmt = $pdo->prepare("DELETE FROM ip_blocks WHERE id = :id");
                $stmt->execute(['id' => $ipId]);
                $message = 'IP Block removed successfully!';
                $messageType = 'success';
            }
        } elseif ($action === 'add_whitelist_ip') {
            $ip = trim($_POST['ip_address'] ?? '');
            $label = trim($_POST['label'] ?? 'Admin Manual Whitelist');
            if (!empty($ip) && $pdo) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO ip_whitelists (ip_address, label, is_auto) VALUES (:ip, :l, 0)");
                    $stmt->execute(['ip' => $ip, 'l' => $label]);
                } catch (\Exception $e) {
                    // Ignore duplicate key error
                }
                $message = 'IP added to Whitelist!';
                $messageType = 'success';
            }
        } elseif ($action === 'remove_whitelist_ip') {
            $wlId = intval($_POST['wl_id'] ?? 0);
            if ($wlId > 0 && $pdo) {
                $stmt = $pdo->prepare("DELETE FROM ip_whitelists WHERE id = :id");
                $stmt->execute(['id' => $wlId]);
                $message = 'Whitelisted IP removed!';
                $messageType = 'success';
            }
        } elseif ($action === 'unsuspend_user') {
            $userId = intval($_POST['user_id'] ?? 0);
            if ($userId > 0 && $pdo) {
                $stmt = $pdo->prepare("UPDATE users SET is_suspended = 0 WHERE id = :id");
                $stmt->execute(['id' => $userId]);
                $message = 'User account unlocked successfully!';
                $messageType = 'success';
            }
        } elseif ($action === 'create_staff') {
            $staffUsername = trim($_POST['username'] ?? '');
            $staffEmail = trim($_POST['email'] ?? '');
            $staffPassword = $_POST['password'] ?? '';

            if (empty($staffUsername) || empty($staffEmail) || empty($staffPassword)) {
                $message = 'All fields are required for staff user creation.';
                $messageType = 'error';
            } else {
                try {
                    $passHash = password_hash($staffPassword, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (:u, :e, :p, 'staff')");
                    $stmt->execute(['u' => $staffUsername, 'e' => $staffEmail, 'p' => $passHash]);
                    $message = 'Staff account created successfully!';
                    $messageType = 'success';
                } catch (\Exception $e) {
                    $message = 'Failed to create staff account (username/email already exists).';
                    $messageType = 'error';
                }
            }
        }
    }
}
}

// Fetch stats & data
$loginLogs = $pdo ? $pdo->query("SELECT * FROM login_logs ORDER BY created_at DESC LIMIT 50")->fetchAll() : [];
$cardHistory = $pdo ? $pdo->query("SELECT h.*, u.username FROM card_history h LEFT JOIN users u ON h.user_id = u.id ORDER BY h.id DESC LIMIT 50")->fetchAll() : [];
$staffUsers = $pdo ? $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll() : [];
$ipBlocks = $pdo ? $pdo->query("SELECT * FROM ip_blocks ORDER BY created_at DESC")->fetchAll() : [];
$ipWhitelists = $pdo ? $pdo->query("SELECT * FROM ip_whitelists ORDER BY created_at DESC")->fetchAll() : [];
$suspendedUsers = $pdo ? $pdo->query("SELECT * FROM users WHERE is_suspended = 1")->fetchAll() : [];
$cardTemplates = $pdo ? $pdo->query("SELECT * FROM card_templates ORDER BY id DESC")->fetchAll() : [];

$siteTitle = get_setting('site_title', 'CARD-CREATOR');
$demoMode = get_setting('demo_mode', '0') === '1';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard & Security Console - <?= htmlspecialchars($siteTitle) ?></title>
    <link rel="icon" type="image/svg+xml" href="../favicon.php">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen">

<!-- Top Navigation -->
<nav class="bg-slate-800 border-b border-slate-700 px-6 py-4 flex justify-between items-center sticky top-0 z-50">
    <div class="flex items-center space-x-3">
        <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center font-bold text-white shadow-lg shadow-blue-500/30">
            <?= htmlspecialchars(strtoupper(substr($siteTitle, 0, 1))) ?>
        </div>
        <div>
            <h1 class="font-bold text-white text-lg"><?= htmlspecialchars($siteTitle) ?></h1>
            <span class="text-xs text-slate-400">Security Control Panel & Management System</span>
        </div>
    </div>

    <div class="flex items-center space-x-4 text-sm">
        <?php if ($demoMode): ?>
            <span class="px-3 py-1 bg-amber-500/20 border border-amber-500/40 text-amber-300 font-semibold text-xs rounded-full">
                ⚡ DEMO MODE ACTIVE
            </span>
        <?php endif; ?>
        <a href="../index.php" target="_blank" class="text-slate-300 hover:text-white transition">Live Site ↗</a>
        <a href="logout.php" class="px-3.5 py-1.5 bg-red-600/20 hover:bg-red-600/30 text-red-400 border border-red-500/30 rounded-lg text-xs font-semibold transition">Logout</a>
    </div>
</nav>

<div class="p-6 max-w-7xl mx-auto space-y-8">

    <?php if ($message): ?>
        <div class="p-4 rounded-xl border text-sm flex items-center justify-between <?= $messageType === 'success' ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400' : 'bg-red-500/10 border-red-500/30 text-red-400' ?>">
            <span><?= htmlspecialchars($message) ?></span>
            <button onclick="this.parentElement.remove()" class="text-xs opacity-70 hover:opacity-100">✕</button>
        </div>
    <?php endif; ?>

    <!-- Overview Stat Grid -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-slate-800 border border-slate-700 p-5 rounded-2xl">
            <p class="text-xs text-slate-400 font-semibold uppercase">Total Templates</p>
            <p class="text-3xl font-bold text-white mt-1"><?= count($cardTemplates) ?></p>
        </div>
        <div class="bg-slate-800 border border-slate-700 p-5 rounded-2xl">
            <p class="text-xs text-slate-400 font-semibold uppercase">Whitelisted IPs</p>
            <p class="text-3xl font-bold text-emerald-400 mt-1"><?= count($ipWhitelists) ?></p>
        </div>
        <div class="bg-slate-800 border border-slate-700 p-5 rounded-2xl">
            <p class="text-xs text-slate-400 font-semibold uppercase">Active IP Blocks</p>
            <p class="text-3xl font-bold text-red-400 mt-1"><?= count($ipBlocks) ?></p>
        </div>
        <div class="bg-slate-800 border border-slate-700 p-5 rounded-2xl">
            <p class="text-xs text-slate-400 font-semibold uppercase">Suspended Users</p>
            <p class="text-3xl font-bold text-amber-400 mt-1"><?= count($suspendedUsers) ?></p>
        </div>
    </div>

    <!-- MAIN SETTINGS & SECURITY TABS -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Left 2 Cols: Security & SMTP Settings -->
        <div class="lg:col-span-2 space-y-8">
            <form method="POST" class="bg-slate-800 border border-slate-700 rounded-2xl p-6 space-y-6">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <input type="hidden" name="action" value="save_settings">

                <div class="flex justify-between items-center border-b border-slate-700 pb-4">
                    <h2 class="text-lg font-bold text-white">System & Anti-Brute-Force Settings</h2>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 font-semibold text-xs text-white rounded-xl shadow-md transition">
                        Save Configuration
                    </button>
                </div>

                <!-- Basic Website Settings -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1">Website Title</label>
                        <input type="text" name="site_title" value="<?= htmlspecialchars($siteTitle) ?>" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1">Environment Mode</label>
                        <select name="demo_mode" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm">
                            <option value="0" <?= !$demoMode ? 'selected' : '' ?>>Production Mode (Read/Write)</option>
                            <option value="1" <?= $demoMode ? 'selected' : '' ?>>Demo Mode (Read-Only Safety)</option>
                        </select>
                    </div>
                </div>

                <!-- Security: Account / Username Protection -->
                <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-700 space-y-3">
                    <p class="text-xs font-bold text-blue-400 uppercase tracking-wider">Username-Based Protection</p>

                    <label class="flex items-center space-x-2 text-sm text-slate-300">
                        <input type="checkbox" name="security_user_protection" value="1" <?= get_setting('security_user_protection', '1') === '1' ? 'checked' : '' ?> class="rounded bg-slate-900 border-slate-700 text-blue-600">
                        <span>Enable Username-based Protection (Suspends account on max failures)</span>
                    </label>

                    <label class="flex items-center space-x-2 text-sm text-slate-300">
                        <input type="checkbox" name="security_lock_admin_user" value="1" <?= get_setting('security_lock_admin_user', '1') === '1' ? 'checked' : '' ?> class="rounded bg-slate-900 border-slate-700 text-blue-600">
                        <span>Allow username protection to lock 'admin' / 'administrator' accounts</span>
                    </label>

                    <div class="grid grid-cols-2 gap-4 pt-2">
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1">Brute Force Period (Mins)</label>
                            <input type="number" name="security_brute_period_mins" value="<?= htmlspecialchars(get_setting('security_brute_period_mins', '15')) ?>" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1">Max Failures Before Lock</label>
                            <input type="number" name="security_max_account_failures" value="<?= htmlspecialchars(get_setting('security_max_account_failures', '5')) ?>" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm">
                        </div>
                    </div>
                </div>

                <!-- Security: IP Protection -->
                <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-700 space-y-3">
                    <p class="text-xs font-bold text-blue-400 uppercase tracking-wider">IP Address-Based Protection</p>

                    <label class="flex items-center space-x-2 text-sm text-slate-300">
                        <input type="checkbox" name="security_ip_protection" value="1" <?= get_setting('security_ip_protection', '1') === '1' ? 'checked' : '' ?> class="rounded bg-slate-900 border-slate-700 text-blue-600">
                        <span>Enable IP Address-based Protection</span>
                    </label>

                    <div class="grid grid-cols-2 gap-4 pt-2">
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1">Max Failures per IP Address</label>
                            <input type="number" name="security_max_ip_failures" value="<?= htmlspecialchars(get_setting('security_max_ip_failures', '5')) ?>" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1">IP Block Duration</label>
                            <select name="security_ip_block_duration" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm">
                                <option value="one_day" <?= get_setting('security_ip_block_duration') === 'one_day' ? 'selected' : '' ?>>1 Day Block</option>
                                <option value="one_week" <?= get_setting('security_ip_block_duration') === 'one_week' ? 'selected' : '' ?>>1 Week Block</option>
                                <option value="one_month" <?= get_setting('security_ip_block_duration') === 'one_month' ? 'selected' : '' ?>>1 Month Block</option>
                                <option value="one_year" <?= get_setting('security_ip_block_duration') === 'one_year' ? 'selected' : '' ?>>1 Year Block</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Email Notifications Configuration -->
                <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-700 space-y-3">
                    <p class="text-xs font-bold text-blue-400 uppercase tracking-wider">Email Notification Alerts</p>

                    <label class="flex items-center space-x-2 text-sm text-slate-300">
                        <input type="checkbox" name="security_notify_unwhitelisted_login" value="1" <?= get_setting('security_notify_unwhitelisted_login', '1') === '1' ? 'checked' : '' ?> class="rounded bg-slate-900 border-slate-700 text-blue-600">
                        <span>Send email alert on successful login from non-whitelisted IP address</span>
                    </label>

                    <label class="flex items-center space-x-2 text-sm text-slate-300">
                        <input type="checkbox" name="security_notify_bruteforce" value="1" <?= get_setting('security_notify_bruteforce', '1') === '1' ? 'checked' : '' ?> class="rounded bg-slate-900 border-slate-700 text-blue-600">
                        <span>Send email alert when brute-force user or IP trigger is detected</span>
                    </label>
                </div>

                <!-- SMTP Mailer Settings -->
                <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-700 space-y-3">
                    <p class="text-xs font-bold text-blue-400 uppercase tracking-wider">SMTP Server Configuration</p>
                    <div class="grid grid-cols-2 gap-3">
                        <input type="text" name="smtp_host" placeholder="SMTP Host" value="<?= htmlspecialchars(get_setting('smtp_host', '')) ?>" class="px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-white text-xs">
                        <input type="text" name="smtp_port" placeholder="SMTP Port (587)" value="<?= htmlspecialchars(get_setting('smtp_port', '587')) ?>" class="px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-white text-xs">
                        <input type="text" name="smtp_user" placeholder="SMTP User" value="<?= htmlspecialchars(get_setting('smtp_user', '')) ?>" class="px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-white text-xs">
                        <input type="password" name="smtp_pass" placeholder="SMTP Password" value="<?= htmlspecialchars(get_setting('smtp_pass', '')) ?>" class="px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-white text-xs">
                    </div>
                </div>

            </form>
        </div>

        <!-- Right Col: Whitelisted IPs & Active Blocks -->
        <div class="space-y-6">

            <!-- Whitelisted IPs with King Icon -->
            <div class="bg-slate-800 border border-slate-700 rounded-2xl p-5 space-y-4">
                <div class="flex justify-between items-center border-b border-slate-700 pb-3">
                    <h3 class="font-bold text-white text-sm flex items-center space-x-1.5">
                        <span>👑 Whitelisted Recognized IPs</span>
                    </h3>
                </div>

                <form method="POST" class="flex space-x-2">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <input type="hidden" name="action" value="add_whitelist_ip">
                    <input type="text" name="ip_address" placeholder="Add IP (e.g. 192.168.1.1)" required class="w-full px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white">
                    <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 font-semibold text-xs text-white rounded-xl whitespace-nowrap">Add IP</button>
                </form>

                <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
                    <?php if (empty($ipWhitelists)): ?>
                        <p class="text-xs text-slate-500 italic">No whitelisted IPs found.</p>
                    <?php else: foreach ($ipWhitelists as $wl): ?>
                        <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-900/60 border border-slate-700 text-xs">
                            <div class="flex items-center space-x-2">
                                <span class="text-base" title="Recognized Whitelisted IP">👑</span>
                                <div>
                                    <p class="font-bold text-emerald-400 flex items-center space-x-1">
                                        <span><?= htmlspecialchars($wl['ip_address']) ?></span>
                                        <span class="text-emerald-500 text-xs">✓</span>
                                    </p>
                                    <p class="text-[10px] text-slate-400"><?= htmlspecialchars($wl['label']) ?> (<?= $wl['successful_sessions_count'] ?> sessions)</p>
                                </div>
                            </div>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                <input type="hidden" name="action" value="remove_whitelist_ip">
                                <input type="hidden" name="wl_id" value="<?= $wl['id'] ?>">
                                <button type="submit" class="text-xs text-red-400 hover:text-red-300">Remove</button>
                            </form>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <!-- Active IP Blocks -->
            <div class="bg-slate-800 border border-slate-700 rounded-2xl p-5 space-y-4">
                <h3 class="font-bold text-white text-sm">Active IP Brute-force Blocks</h3>
                <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
                    <?php if (empty($ipBlocks)): ?>
                        <p class="text-xs text-slate-500 italic">No active IP blocks currently.</p>
                    <?php else: foreach ($ipBlocks as $b): ?>
                        <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-900/60 border border-slate-700 text-xs">
                            <div>
                                <p class="font-bold text-red-400"><?= htmlspecialchars($b['ip_address']) ?></p>
                                <p class="text-[10px] text-slate-400">Blocked until: <?= $b['blocked_until'] ?></p>
                            </div>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                <input type="hidden" name="action" value="unblock_ip">
                                <input type="hidden" name="ip_id" value="<?= $b['id'] ?>">
                                <button type="submit" class="text-xs px-2.5 py-1 bg-slate-700 hover:bg-slate-600 rounded-lg text-slate-200">Unblock</button>
                            </form>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <!-- Suspended Accounts -->
            <?php if (!empty($suspendedUsers)): ?>
                <div class="bg-slate-800 border border-amber-500/30 rounded-2xl p-5 space-y-3">
                    <h3 class="font-bold text-amber-400 text-sm">Suspended User Accounts</h3>
                    <?php foreach ($suspendedUsers as $su): ?>
                        <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-900/60 border border-slate-700 text-xs">
                            <div>
                                <p class="font-bold text-white"><?= htmlspecialchars($su['username']) ?></p>
                                <p class="text-[10px] text-slate-400"><?= htmlspecialchars($su['email']) ?></p>
                            </div>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                <input type="hidden" name="action" value="unsuspend_user">
                                <input type="hidden" name="user_id" value="<?= $su['id'] ?>">
                                <button type="submit" class="text-xs px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 font-semibold text-white rounded-lg">Unlock User</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- STAFF USER MANAGEMENT SECTION -->
    <div class="bg-slate-800 border border-slate-700 rounded-2xl p-6 space-y-6">
        <div class="flex justify-between items-center border-b border-slate-700 pb-4">
            <div>
                <h3 class="font-bold text-white text-base">Staff User Management</h3>
                <p class="text-xs text-slate-400">Create and manage staff accounts authorized to design cards in the studio.</p>
            </div>
        </div>

        <form method="POST" class="grid grid-cols-1 sm:grid-cols-4 gap-3 bg-slate-900/60 p-4 rounded-xl border border-slate-700">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            <input type="hidden" name="action" value="create_staff">
            <input type="text" name="username" placeholder="Staff Username" required class="px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white">
            <input type="email" name="email" placeholder="Staff Email" required class="px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white">
            <input type="password" name="password" placeholder="Password" required class="px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white">
            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 font-bold text-xs text-white rounded-xl shadow">
                + Create Staff User
            </button>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-900/60 text-slate-400 uppercase">
                    <tr>
                        <th class="p-3 rounded-l-xl">ID</th>
                        <th class="p-3">Username</th>
                        <th class="p-3">Email</th>
                        <th class="p-3">Role</th>
                        <th class="p-3">Status</th>
                        <th class="p-3 rounded-r-xl">Created At</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700/60">
                    <?php foreach ($staffUsers as $u): ?>
                        <tr class="hover:bg-slate-700/30">
                            <td class="p-3 font-mono text-slate-400">#<?= $u['id'] ?></td>
                            <td class="p-3 font-bold text-white"><?= htmlspecialchars($u['username']) ?></td>
                            <td class="p-3 text-slate-300"><?= htmlspecialchars($u['email']) ?></td>
                            <td class="p-3">
                                <span class="px-2 py-0.5 text-[10px] uppercase font-bold rounded <?= $u['role'] === 'admin' ? 'bg-purple-500/20 text-purple-300 border border-purple-500/30' : 'bg-blue-500/20 text-blue-300 border border-blue-500/30' ?>">
                                    <?= htmlspecialchars($u['role']) ?>
                                </span>
                            </td>
                            <td class="p-3">
                                <?= $u['is_suspended'] == 1 ? '<span class="text-red-400 font-bold">Suspended</span>' : '<span class="text-emerald-400 font-bold">Active</span>' ?>
                            </td>
                            <td class="p-3 font-mono text-slate-400"><?= $u['created_at'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- CARD HISTORY & BATCH PDF EXPORT SECTION -->
    <div class="bg-slate-800 border border-slate-700 rounded-2xl p-6 space-y-6">
        <div class="flex justify-between items-center border-b border-slate-700 pb-4">
            <div>
                <h3 class="font-bold text-white text-base">Generated Card History Log</h3>
                <p class="text-xs text-slate-400">System-wide record of cards created by staff and admin users.</p>
            </div>
            <button onclick="exportBatchPDF()" class="px-4 py-2 bg-rose-600 hover:bg-rose-500 font-bold text-xs text-white rounded-xl shadow flex items-center space-x-1.5">
                <span>📄 Batch Export All Cards to PDF</span>
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4" id="historyGrid">
            <?php if (empty($cardHistory)): ?>
                <p class="text-xs text-slate-500 italic col-span-3">No cards generated in history yet.</p>
            <?php else: foreach ($cardHistory as $h): ?>
                <div class="p-4 bg-slate-900/80 border border-slate-700/80 rounded-2xl space-y-3 card-history-item"
                     data-front="<?= htmlspecialchars($h['preview_front'] ?? '') ?>"
                     data-back="<?= htmlspecialchars($h['preview_back'] ?? '') ?>"
                     data-name="<?= htmlspecialchars($h['cardholder_name'] ?? 'Cardholder') ?>">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="font-bold text-white text-sm"><?= htmlspecialchars($h['cardholder_name'] ?: 'Cardholder') ?></p>
                            <p class="text-[10px] text-slate-400"><?= htmlspecialchars($h['template_title']) ?> · by <?= htmlspecialchars($h['username'] ?: 'System') ?></p>
                        </div>
                        <span class="text-[9px] px-2 py-0.5 rounded uppercase font-bold bg-blue-600/20 text-blue-300 border border-blue-500/30">
                            <?= htmlspecialchars($h['card_type']) ?>
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 h-28 bg-slate-950 rounded-xl p-2 border border-slate-800 items-center justify-center overflow-hidden">
                        <?php if (!empty($h['preview_front'])): ?>
                            <img src="<?= htmlspecialchars($h['preview_front']) ?>" class="max-h-24 max-w-full mx-auto object-contain rounded border border-slate-700">
                        <?php endif; ?>
                        <?php if (!empty($h['preview_back'])): ?>
                            <img src="<?= htmlspecialchars($h['preview_back']) ?>" class="max-h-24 max-w-full mx-auto object-contain rounded border border-slate-700">
                        <?php endif; ?>
                    </div>

                    <p class="text-[10px] text-slate-500 text-right font-mono"><?= $h['created_at'] ?></p>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- BOTTOM SECTION: LOGIN HISTORY LOGS -->
    <div class="bg-slate-800 border border-slate-700 rounded-2xl p-6 space-y-4">
        <h3 class="font-bold text-white text-base">Login History & Protection Activity Logs</h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-900/60 text-slate-400 uppercase">
                    <tr>
                        <th class="p-3 rounded-l-xl">Timestamp</th>
                        <th class="p-3">Username</th>
                        <th class="p-3">IP Address</th>
                        <th class="p-3">Status</th>
                        <th class="p-3 rounded-r-xl">User Agent</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700/60">
                    <?php if (empty($loginLogs)): ?>
                        <tr><td colspan="5" class="p-4 text-center italic text-slate-500">No login logs recorded yet.</td></tr>
                    <?php else: foreach ($loginLogs as $log): ?>
                        <tr class="hover:bg-slate-700/30">
                            <td class="p-3 font-mono text-slate-400"><?= $log['created_at'] ?></td>
                            <td class="p-3 font-semibold text-white"><?= htmlspecialchars($log['username'] ?: 'N/A') ?></td>
                            <td class="p-3 font-mono"><?= htmlspecialchars($log['ip_address']) ?></td>
                            <td class="p-3">
                                <?php if ($log['status'] === 'success'): ?>
                                    <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 rounded font-semibold">Success</span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 bg-red-500/20 text-red-400 border border-red-500/30 rounded font-semibold">Failed</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3 text-slate-400 truncate max-w-xs" title="<?= htmlspecialchars($log['user_agent']) ?>"><?= htmlspecialchars($log['user_agent']) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
function exportBatchPDF() {
    const items = document.querySelectorAll('.card-history-item');
    if (items.length === 0) {
        alert('No generated cards in history to export.');
        return;
    }

    const { jsPDF } = window.jspdf;
    const pdf = new jsPDF({ orientation: 'portrait', unit: 'px', format: [600, 960] });

    let pageAdded = false;

    items.forEach((item, index) => {
        const front = item.getAttribute('data-front');
        const back = item.getAttribute('data-back');

        if (front && front.length > 50) {
            if (pageAdded) pdf.addPage([600, 960], 'portrait');
            pdf.addImage(front, 'PNG', 0, 0, 600, 960);
            pageAdded = true;
        }

        if (back && back.length > 50) {
            if (pageAdded) pdf.addPage([600, 960], 'portrait');
            pdf.addImage(back, 'PNG', 0, 0, 600, 960);
            pageAdded = true;
        }
    });

    pdf.save(`System_Card_History_Batch_${Date.now()}.pdf`);
}
</script>

</body>
</html>
