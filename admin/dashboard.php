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

// Revert impersonation if requested
if (isset($_GET['action']) && $_GET['action'] === 'revert_impersonation' && isset($_SESSION['impersonator_id'])) {
    $pdo = get_db_connection();
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id' => $_SESSION['impersonator_id']]);
        $origAdmin = $stmt->fetch();
        if ($origAdmin) {
            $_SESSION['user_id'] = $origAdmin['id'];
            $_SESSION['username'] = $origAdmin['username'];
            $_SESSION['user_role'] = $origAdmin['role'];
            unset($_SESSION['impersonator_id']);
        }
    }
    header('Location: dashboard.php');
    exit;
}

// Only admin users can access full dashboard
if (($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ../studio.php');
    exit;
}

$pdo = get_db_connection();
$message = '';
$messageType = '';

// Handle Settings & Actions
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
                        if (strpos($key, 'security_') === 0 || $key === 'demo_mode') {
                            set_setting($key, '0');
                        }
                    }
                }
                $message = 'Settings updated successfully!';
                $messageType = 'success';
            } elseif ($action === 'update_admin_profile') {
                $newUsername = trim($_POST['username'] ?? '');
                $newEmail = trim($_POST['email'] ?? '');
                $newPassword = $_POST['password'] ?? '';

                if (empty($newUsername) || empty($newEmail)) {
                    $message = 'Username and Email cannot be empty.';
                    $messageType = 'error';
                } else {
                    $currentUserId = $_SESSION['user_id'];
                    if (!empty($newPassword)) {
                        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
                        $stmt = $pdo->prepare("UPDATE users SET username = :u, email = :e, password_hash = :p WHERE id = :id");
                        $stmt->execute(['u' => $newUsername, 'e' => $newEmail, 'p' => $hash, 'id' => $currentUserId]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE users SET username = :u, email = :e WHERE id = :id");
                        $stmt->execute(['u' => $newUsername, 'e' => $newEmail, 'id' => $currentUserId]);
                    }
                    $_SESSION['username'] = $newUsername;
                    $message = 'Admin profile updated successfully!';
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
                        $message = 'Failed to create staff account (username or email already exists).';
                        $messageType = 'error';
                    }
                }
            } elseif ($action === 'edit_staff') {
                $staffId = intval($_POST['staff_id'] ?? 0);
                $staffUsername = trim($_POST['username'] ?? '');
                $staffEmail = trim($_POST['email'] ?? '');
                $staffPassword = $_POST['password'] ?? '';
                $isSuspended = intval($_POST['is_suspended'] ?? 0);

                if ($staffId > 0 && !empty($staffUsername) && !empty($staffEmail)) {
                    if (!empty($staffPassword)) {
                        $passHash = password_hash($staffPassword, PASSWORD_BCRYPT);
                        $stmt = $pdo->prepare("UPDATE users SET username = :u, email = :e, password_hash = :p, is_suspended = :s WHERE id = :id AND role = 'staff'");
                        $stmt->execute(['u' => $staffUsername, 'e' => $staffEmail, 'p' => $passHash, 's' => $isSuspended, 'id' => $staffId]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE users SET username = :u, email = :e, is_suspended = :s WHERE id = :id AND role = 'staff'");
                        $stmt->execute(['u' => $staffUsername, 'e' => $staffEmail, 's' => $isSuspended, 'id' => $staffId]);
                    }
                    $message = 'Staff user updated successfully!';
                    $messageType = 'success';
                }
            } elseif ($action === 'delete_staff') {
                $staffId = intval($_POST['staff_id'] ?? 0);
                if ($staffId > 0 && $pdo) {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id AND role = 'staff'");
                    $stmt->execute(['id' => $staffId]);
                    $message = 'Staff user account deleted successfully!';
                    $messageType = 'success';
                }
            } elseif ($action === 'impersonate_staff') {
                $staffId = intval($_POST['staff_id'] ?? 0);
                if ($staffId > 0 && $pdo) {
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id AND role = 'staff'");
                    $stmt->execute(['id' => $staffId]);
                    $staff = $stmt->fetch();
                    if ($staff) {
                        $_SESSION['impersonator_id'] = $_SESSION['user_id'];
                        $_SESSION['user_id'] = $staff['id'];
                        $_SESSION['username'] = $staff['username'];
                        $_SESSION['user_role'] = $staff['role'];

                        header('Location: ../studio.php');
                        exit;
                    }
                }
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
                    } catch (\Exception $e) {}
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
            }
        }
    }
}

// Fetch current user & data
$currentAdminStmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$currentAdminStmt->execute(['id' => $_SESSION['user_id']]);
$currentAdmin = $currentAdminStmt->fetch();

$loginLogs = $pdo ? $pdo->query("SELECT * FROM login_logs ORDER BY created_at DESC LIMIT 50")->fetchAll() : [];
$cardHistory = $pdo ? $pdo->query("SELECT h.*, u.username FROM card_history h LEFT JOIN users u ON h.user_id = u.id ORDER BY h.id DESC LIMIT 50")->fetchAll() : [];
$staffUsers = $pdo ? $pdo->query("SELECT * FROM users WHERE role = 'staff' ORDER BY id DESC")->fetchAll() : [];
$allUsers = $pdo ? $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll() : [];
$ipBlocks = $pdo ? $pdo->query("SELECT * FROM ip_blocks ORDER BY created_at DESC")->fetchAll() : [];
$ipWhitelists = $pdo ? $pdo->query("SELECT * FROM ip_whitelists ORDER BY created_at DESC")->fetchAll() : [];
$suspendedUsers = $pdo ? $pdo->query("SELECT * FROM users WHERE is_suspended = 1")->fetchAll() : [];

$siteTitle = get_setting('site_title', 'CARD-CREATOR');
$demoMode = get_setting('demo_mode', '0') === '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Console - <?= htmlspecialchars($siteTitle) ?></title>
    <link rel="icon" type="image/svg+xml" href="../favicon.php">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .tab-btn.active {
            background-color: #2563eb;
            color: #ffffff;
            box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3);
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col selection:bg-blue-500 selection:text-white">

<!-- Modern Navigation Header -->
<header class="border-b border-slate-800 bg-slate-900/80 backdrop-blur-md sticky top-0 z-50 px-6 py-4 flex items-center justify-between">
    <div class="flex items-center space-x-3">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center font-black text-white text-xl shadow-lg shadow-blue-500/30">
            <?= htmlspecialchars(strtoupper(substr($siteTitle, 0, 1))) ?>
        </div>
        <div>
            <h1 class="font-extrabold text-white text-base tracking-wide"><?= htmlspecialchars($siteTitle) ?></h1>
            <span class="text-[11px] text-blue-400 font-semibold tracking-wider uppercase">Admin Control Center</span>
        </div>
    </div>

    <div class="flex items-center space-x-4 text-xs font-semibold">
        <?php if ($demoMode): ?>
            <span class="px-3 py-1 bg-amber-500/20 border border-amber-500/40 text-amber-300 rounded-full">
                ⚡ DEMO MODE
            </span>
        <?php endif; ?>

        <a href="../studio.php" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-xl shadow transition">
            Launch Studio 🎨
        </a>

        <a href="logout.php" class="px-3 py-2 bg-red-600/20 hover:bg-red-600/30 text-red-400 border border-red-500/30 rounded-xl transition">
            Logout
        </a>
    </div>
</header>

<div class="flex-1 max-w-7xl w-full mx-auto p-4 sm:p-6 space-y-6">

    <?php if ($message): ?>
        <div class="p-4 rounded-2xl border text-xs font-bold flex items-center justify-between <?= $messageType === 'success' ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400' : 'bg-red-500/10 border-red-500/30 text-red-400' ?>">
            <span><?= htmlspecialchars($message) ?></span>
            <button onclick="this.parentElement.remove()" class="text-xs opacity-70 hover:opacity-100">✕</button>
        </div>
    <?php endif; ?>

    <!-- MODERN SPA TAB MENU CONTROL -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-2 flex flex-wrap gap-2 shadow-xl">
        <button onclick="switchTab('overview')" id="tabBtn-overview" class="tab-btn active px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center space-x-2">
            <span>📊 Overview</span>
        </button>
        <button onclick="switchTab('staff')" id="tabBtn-staff" class="tab-btn px-4 py-2.5 rounded-xl text-xs font-bold text-slate-400 hover:text-white transition flex items-center space-x-2">
            <span>👥 Staff Accounts</span>
        </button>
        <button onclick="switchTab('history')" id="tabBtn-history" class="tab-btn px-4 py-2.5 rounded-xl text-xs font-bold text-slate-400 hover:text-white transition flex items-center space-x-2">
            <span>📇 Card History</span>
        </button>
        <button onclick="switchTab('security')" id="tabBtn-security" class="tab-btn px-4 py-2.5 rounded-xl text-xs font-bold text-slate-400 hover:text-white transition flex items-center space-x-2">
            <span>🛡 Anti-Brute-Force & IPs</span>
        </button>
        <button onclick="switchTab('profile')" id="tabBtn-profile" class="tab-btn px-4 py-2.5 rounded-xl text-xs font-bold text-slate-400 hover:text-white transition flex items-center space-x-2">
            <span>⚙ Admin Profile</span>
        </button>
        <button onclick="switchTab('logs')" id="tabBtn-logs" class="tab-btn px-4 py-2.5 rounded-xl text-xs font-bold text-slate-400 hover:text-white transition flex items-center space-x-2">
            <span>📜 Activity Logs</span>
        </button>
    </div>

    <!-- TAB 1: OVERVIEW STATS -->
    <div id="tab-overview" class="tab-content space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl">
                <p class="text-[10px] text-slate-400 font-extrabold uppercase tracking-wider">Total Staff Users</p>
                <p class="text-3xl font-black text-white mt-1"><?= count($staffUsers) ?></p>
            </div>
            <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl">
                <p class="text-[10px] text-slate-400 font-extrabold uppercase tracking-wider">Generated Cards</p>
                <p class="text-3xl font-black text-blue-400 mt-1"><?= count($cardHistory) ?></p>
            </div>
            <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl">
                <p class="text-[10px] text-slate-400 font-extrabold uppercase tracking-wider">Whitelisted IPs</p>
                <p class="text-3xl font-black text-emerald-400 mt-1"><?= count($ipWhitelists) ?></p>
            </div>
            <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl">
                <p class="text-[10px] text-slate-400 font-extrabold uppercase tracking-wider">Blocked IPs</p>
                <p class="text-3xl font-black text-rose-400 mt-1"><?= count($ipBlocks) ?></p>
            </div>
        </div>
    </div>

    <!-- TAB 2: STAFF MANAGEMENT (Edit, Delete, Login As) -->
    <div id="tab-staff" class="tab-content hidden space-y-6">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-6">
            <div class="flex justify-between items-center border-b border-slate-800 pb-4">
                <div>
                    <h2 class="text-base font-bold text-white">Staff Account Management</h2>
                    <p class="text-xs text-slate-400">Create, update, delete or impersonate staff accounts.</p>
                </div>
            </div>

            <!-- Create Staff Form -->
            <form method="POST" class="grid grid-cols-1 sm:grid-cols-4 gap-3 bg-slate-950 p-4 rounded-xl border border-slate-800">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <input type="hidden" name="action" value="create_staff">
                <input type="text" name="username" placeholder="Staff Username" required class="px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white">
                <input type="email" name="email" placeholder="Staff Email" required class="px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white">
                <input type="password" name="password" placeholder="Password" required class="px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white">
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 font-bold text-xs text-white rounded-xl shadow">
                    + Create Staff User
                </button>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-950 text-slate-400 uppercase">
                        <tr>
                            <th class="p-3 rounded-l-xl">ID</th>
                            <th class="p-3">Username</th>
                            <th class="p-3">Email</th>
                            <th class="p-3">Status</th>
                            <th class="p-3">Created</th>
                            <th class="p-3 text-right rounded-r-xl">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        <?php if (empty($staffUsers)): ?>
                            <tr><td colspan="6" class="p-4 text-center italic text-slate-500">No staff accounts created yet.</td></tr>
                        <?php else: foreach ($staffUsers as $u): ?>
                            <tr class="hover:bg-slate-800/40">
                                <td class="p-3 font-mono text-slate-500">#<?= $u['id'] ?></td>
                                <td class="p-3 font-bold text-white"><?= htmlspecialchars($u['username']) ?></td>
                                <td class="p-3 text-slate-300"><?= htmlspecialchars($u['email']) ?></td>
                                <td class="p-3">
                                    <?= $u['is_suspended'] == 1 ? '<span class="px-2 py-0.5 text-[10px] bg-red-500/20 text-red-400 rounded font-bold">Suspended</span>' : '<span class="px-2 py-0.5 text-[10px] bg-emerald-500/20 text-emerald-400 rounded font-bold">Active</span>' ?>
                                </td>
                                <td class="p-3 font-mono text-slate-500"><?= $u['created_at'] ?></td>
                                <td class="p-3 text-right space-x-2">
                                    <button onclick='openEditStaffModal(<?= json_encode($u) ?>)' class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-blue-400 font-semibold rounded-lg">Edit</button>

                                    <form method="POST" class="inline">
                                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                        <input type="hidden" name="action" value="impersonate_staff">
                                        <input type="hidden" name="staff_id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="px-2.5 py-1 bg-indigo-600/30 hover:bg-indigo-600/50 text-indigo-300 font-semibold rounded-lg">Login As</button>
                                    </form>

                                    <form method="POST" class="inline" onsubmit="return confirm('Delete this staff account?');">
                                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                        <input type="hidden" name="action" value="delete_staff">
                                        <input type="hidden" name="staff_id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="px-2.5 py-1 bg-red-600/20 hover:bg-red-600/40 text-red-400 font-semibold rounded-lg">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 3: CARD HISTORY & BATCH EXPORT -->
    <div id="tab-history" class="tab-content hidden space-y-6">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-6">
            <div class="flex justify-between items-center border-b border-slate-800 pb-4">
                <div>
                    <h2 class="text-base font-bold text-white">Generated Card History</h2>
                    <p class="text-xs text-slate-400">All saved card records generated across sessions.</p>
                </div>
                <button onclick="exportBatchPDF()" class="px-4 py-2 bg-rose-600 hover:bg-rose-500 font-bold text-xs text-white rounded-xl shadow flex items-center space-x-1.5">
                    <span>📄 Batch Export All Cards to PDF</span>
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4" id="historyGrid">
                <?php if (empty($cardHistory)): ?>
                    <p class="text-xs text-slate-500 italic col-span-3">No cards generated in history yet.</p>
                <?php else: foreach ($cardHistory as $h): ?>
                    <div class="p-4 bg-slate-950 border border-slate-800 rounded-2xl space-y-3 card-history-item"
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

                        <div class="grid grid-cols-2 gap-2 h-28 bg-slate-900 rounded-xl p-2 border border-slate-800 items-center justify-center overflow-hidden">
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
    </div>

    <!-- TAB 4: ANTI-BRUTE-FORCE & IP FIREWALL -->
    <div id="tab-security" class="tab-content hidden space-y-6">
        <form method="POST" class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-6">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            <input type="hidden" name="action" value="save_settings">

            <div class="flex justify-between items-center border-b border-slate-800 pb-4">
                <h2 class="text-base font-bold text-white">Anti-Brute-Force & IP Firewall Configuration</h2>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 font-bold text-xs text-white rounded-xl shadow">
                    Save Configuration
                </button>
            </div>

            <!-- Account Protection -->
            <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-3">
                <p class="text-xs font-bold text-blue-400 uppercase tracking-wider">Username Protection</p>
                <label class="flex items-center space-x-2 text-xs text-slate-300">
                    <input type="checkbox" name="security_user_protection" value="1" <?= get_setting('security_user_protection', '1') === '1' ? 'checked' : '' ?> class="rounded bg-slate-900 border-slate-700 text-blue-600">
                    <span>Enable Username Protection (Suspends user account on maximum login retry threshold)</span>
                </label>
                <div class="grid grid-cols-2 gap-4 pt-2">
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Protection Period (Mins)</label>
                        <input type="number" name="security_brute_period_mins" value="<?= htmlspecialchars(get_setting('security_brute_period_mins', '15')) ?>" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-white text-xs">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Max Failures Before Suspension</label>
                        <input type="number" name="security_max_account_failures" value="<?= htmlspecialchars(get_setting('security_max_account_failures', '5')) ?>" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-white text-xs">
                    </div>
                </div>
            </div>

            <!-- IP Protection -->
            <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-3">
                <p class="text-xs font-bold text-blue-400 uppercase tracking-wider">IP Address Protection</p>
                <label class="flex items-center space-x-2 text-xs text-slate-300">
                    <input type="checkbox" name="security_ip_protection" value="1" <?= get_setting('security_ip_protection', '1') === '1' ? 'checked' : '' ?> class="rounded bg-slate-900 border-slate-700 text-blue-600">
                    <span>Enable IP Address-Based Firewall Protection</span>
                </label>
                <div class="grid grid-cols-2 gap-4 pt-2">
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Max Failures Per IP</label>
                        <input type="number" name="security_max_ip_failures" value="<?= htmlspecialchars(get_setting('security_max_ip_failures', '5')) ?>" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-white text-xs">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Block Duration</label>
                        <select name="security_ip_block_duration" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-white text-xs">
                            <option value="one_day" <?= get_setting('security_ip_block_duration') === 'one_day' ? 'selected' : '' ?>>1 Day</option>
                            <option value="one_week" <?= get_setting('security_ip_block_duration') === 'one_week' ? 'selected' : '' ?>>1 Week</option>
                            <option value="one_month" <?= get_setting('security_ip_block_duration') === 'one_month' ? 'selected' : '' ?>>1 Month</option>
                        </select>
                    </div>
                </div>
            </div>
        </form>

        <!-- WHITELISTED IPS (WITH 👑 KING ICON) & ACTIVE IP BLOCKS -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Whitelisted Recognized IPs -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-4">
                <div class="flex justify-between items-center border-b border-slate-800 pb-3">
                    <h3 class="font-bold text-white text-sm flex items-center space-x-2">
                        <span>👑 Whitelisted Recognized IPs</span>
                    </h3>
                </div>

                <form method="POST" class="flex space-x-2">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <input type="hidden" name="action" value="add_whitelist_ip">
                    <input type="text" name="ip_address" placeholder="Add IP (e.g. 192.168.1.1)" required class="w-full px-3 py-1.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white">
                    <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 font-semibold text-xs text-white rounded-xl whitespace-nowrap">Add IP</button>
                </form>

                <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
                    <?php if (empty($ipWhitelists)): ?>
                        <p class="text-xs text-slate-500 italic">No whitelisted IPs found.</p>
                    <?php else: foreach ($ipWhitelists as $wl): ?>
                        <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs">
                            <div class="flex items-center space-x-2">
                                <span class="text-base" title="Whitelisted Recognized IP">👑</span>
                                <div>
                                    <p class="font-bold text-emerald-400 flex items-center space-x-1">
                                        <span><?= htmlspecialchars($wl['ip_address']) ?></span>
                                        <span class="text-emerald-500 font-black">✓</span>
                                    </p>
                                    <p class="text-[10px] text-slate-400"><?= htmlspecialchars($wl['label'] ?: 'Recognized Trusted IP') ?> (<?= $wl['successful_sessions_count'] ?? 1 ?> sessions)</p>
                                </div>
                            </div>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                <input type="hidden" name="action" value="remove_whitelist_ip">
                                <input type="hidden" name="wl_id" value="<?= $wl['id'] ?>">
                                <button type="submit" class="text-xs text-red-400 hover:text-red-300 font-semibold">Remove</button>
                            </form>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <!-- Active IP Brute-Force Blocks -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-4">
                <h3 class="font-bold text-white text-sm border-b border-slate-800 pb-3">Active IP Brute-Force Blocks</h3>
                <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
                    <?php if (empty($ipBlocks)): ?>
                        <p class="text-xs text-slate-500 italic">No active IP blocks currently.</p>
                    <?php else: foreach ($ipBlocks as $b): ?>
                        <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs">
                            <div>
                                <p class="font-bold text-rose-400"><?= htmlspecialchars($b['ip_address']) ?></p>
                                <p class="text-[10px] text-slate-400">Blocked until: <?= $b['blocked_until'] ?></p>
                            </div>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                <input type="hidden" name="action" value="unblock_ip">
                                <input type="hidden" name="ip_id" value="<?= $b['id'] ?>">
                                <button type="submit" class="text-xs px-2.5 py-1 bg-slate-800 hover:bg-slate-700 rounded-lg text-slate-200 font-semibold">Unblock</button>
                            </form>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 5: ADMIN PROFILE SETTINGS -->
    <div id="tab-profile" class="tab-content hidden space-y-6">
        <form method="POST" class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-4 max-w-xl">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            <input type="hidden" name="action" value="update_admin_profile">

            <h2 class="text-base font-bold text-white border-b border-slate-800 pb-3">Update Admin Profile Details</h2>

            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1">Admin Username</label>
                <input type="text" name="username" value="<?= htmlspecialchars($currentAdmin['username'] ?? '') ?>" required class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1">Admin Email Address</label>
                <input type="email" name="email" value="<?= htmlspecialchars($currentAdmin['email'] ?? '') ?>" required class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1">New Password (leave blank to keep current)</label>
                <input type="password" name="password" placeholder="••••••••" class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs">
            </div>

            <button type="submit" class="py-3 px-6 bg-blue-600 hover:bg-blue-500 font-bold text-xs text-white rounded-xl shadow">
                Save Profile Changes
            </button>
        </form>
    </div>

    <!-- TAB 6: ACTIVITY LOGS -->
    <div id="tab-logs" class="tab-content hidden space-y-6">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-4">
            <h2 class="text-base font-bold text-white">System Security Activity Logs</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-950 text-slate-400 uppercase">
                        <tr>
                            <th class="p-3 rounded-l-xl">Timestamp</th>
                            <th class="p-3">Username</th>
                            <th class="p-3">IP Address</th>
                            <th class="p-3">Status</th>
                            <th class="p-3 rounded-r-xl">User Agent</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        <?php foreach ($loginLogs as $log): ?>
                            <tr class="hover:bg-slate-800/40">
                                <td class="p-3 font-mono text-slate-500"><?= $log['created_at'] ?></td>
                                <td class="p-3 font-bold text-white"><?= htmlspecialchars($log['username'] ?: 'N/A') ?></td>
                                <td class="p-3 font-mono"><?= htmlspecialchars($log['ip_address']) ?></td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold <?= $log['status'] === 'success' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400' ?>"><?= htmlspecialchars($log['status']) ?></span>
                                </td>
                                <td class="p-3 text-slate-400 truncate max-w-xs"><?= htmlspecialchars($log['user_agent']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- EDIT STAFF MODAL -->
<div id="editStaffModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4">
    <form method="POST" class="bg-slate-900 border border-slate-800 rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl">
        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
        <input type="hidden" name="action" value="edit_staff">
        <input type="hidden" name="staff_id" id="editStaffId">

        <h3 class="text-base font-bold text-white border-b border-slate-800 pb-2">Edit Staff User</h3>

        <div>
            <label class="block text-xs font-semibold text-slate-400 mb-1">Username</label>
            <input type="text" name="username" id="editStaffUsername" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-xs">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-400 mb-1">Email</label>
            <input type="email" name="email" id="editStaffEmail" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-xs">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-400 mb-1">New Password (optional)</label>
            <input type="password" name="password" placeholder="Leave blank to keep current" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-xs">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-400 mb-1">Account Status</label>
            <select name="is_suspended" id="editStaffSuspended" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-xs">
                <option value="0">Active</option>
                <option value="1">Suspended</option>
            </select>
        </div>

        <div class="flex justify-end space-x-3 pt-3 border-t border-slate-800">
            <button type="button" onclick="closeEditStaffModal()" class="px-4 py-2 bg-slate-800 text-slate-300 text-xs font-semibold rounded-xl">Cancel</button>
            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold rounded-xl shadow">Save Changes</button>
        </div>
    </form>
</div>

<script>
function switchTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active', 'text-white'));

    document.getElementById('tab-' + tabName).classList.remove('hidden');
    const activeBtn = document.getElementById('tabBtn-' + tabName);
    activeBtn.classList.add('active', 'text-white');
}

function openEditStaffModal(staff) {
    document.getElementById('editStaffId').value = staff.id;
    document.getElementById('editStaffUsername').value = staff.username;
    document.getElementById('editStaffEmail').value = staff.email;
    document.getElementById('editStaffSuspended').value = staff.is_suspended || 0;
    document.getElementById('editStaffModal').classList.remove('hidden');
}

function closeEditStaffModal() {
    document.getElementById('editStaffModal').classList.add('hidden');
}

function exportBatchPDF() {
    const items = document.querySelectorAll('.card-history-item');
    if (items.length === 0) {
        alert('No generated cards in history to export.');
        return;
    }

    const { jsPDF } = window.jspdf;
    const pdf = new jsPDF({ orientation: 'portrait', unit: 'px', format: [600, 960] });

    let pageAdded = false;

    items.forEach((item) => {
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
