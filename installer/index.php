<?php
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/sys_check.php';

if (is_installed()) {
    header('Location: ../index.php');
    exit;
}

$stage = isset($_GET['stage']) ? intval($_GET['stage']) : 1;
if ($stage < 1 || $stage > 4) $stage = 1;

$error = '';
$success = '';

// Handle Form Submissions for stages
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'stage1_activation') {
        $activationKey = trim($_POST['activation_key'] ?? '');
        if (empty($activationKey)) {
            $error = 'Please enter your system key.';
        } else {
            $res = SysVerify::checkActivation($activationKey);
            if ($res['status'] == 1) {
                $_SESSION['installer_key'] = $activationKey;
                header('Location: index.php?stage=2');
                exit;
            } else {
                $error = $res['message'] ?? 'Activation failed. Please verify your key.';
            }
        }
    } elseif ($action === 'stage2_database') {
        $host = trim($_POST['db_host'] ?? '127.0.0.1');
        $port = trim($_POST['db_port'] ?? '3306');
        $dbName = trim($_POST['db_name'] ?? '');
        $user = trim($_POST['db_user'] ?? '');
        $pass = $_POST['db_pass'] ?? '';

        if (empty($dbName) || empty($user)) {
            $error = 'Database Name and User are required.';
        } else {
            try {
                $dbFile = __DIR__ . '/../card_creator.sqlite';
                $pdo = new PDO("sqlite:" . $dbFile);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // Import Schema
                $schemaFile = __DIR__ . '/../schema.sql';
                if (file_exists($schemaFile)) {
                    $sql = file_get_contents($schemaFile);
                    $pdo->exec($sql);
                }

                // Save database config
                $configContent = "<?php\nreturn " . var_export([
                    'driver' => 'sqlite',
                    'db_path' => $dbFile,
                    'host' => $host,
                    'port' => $port,
                    'db_name' => $dbName,
                    'user' => $user,
                    'pass' => $pass
                ], true) . ";\n";

                file_put_contents(__DIR__ . '/../config/database.php', $configContent);

                header('Location: index.php?stage=3');
                exit;
            } catch (\Exception $e) {
                $error = 'Database Setup Failed: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'stage3_admin') {
        $username = trim($_POST['admin_user'] ?? '');
        $email = trim($_POST['admin_email'] ?? '');
        $password = $_POST['admin_pass'] ?? '';
        $siteTitle = trim($_POST['site_title'] ?? 'CARD-CREATOR');

        // SMTP settings
        $smtpHost = trim($_POST['smtp_host'] ?? '');
        $smtpPort = trim($_POST['smtp_port'] ?? '587');
        $smtpUser = trim($_POST['smtp_user'] ?? '');
        $smtpPass = $_POST['smtp_pass'] ?? '';

        if (empty($username) || empty($email) || empty($password)) {
            $error = 'Username, Email and Password are required.';
        } else {
            $pdo = get_db_connection();
            if (!$pdo) {
                $error = 'Could not establish connection using saved database config.';
            } else {
                try {
                    // Create Admin Account
                    $passHash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT OR REPLACE INTO users (username, email, password_hash, role) VALUES (:u, :e, :p, 'admin')");
                    $stmt->execute(['u' => $username, 'e' => $email, 'p' => $passHash]);

                    // Seed Settings
                    $settingsMap = [
                        'site_title' => $siteTitle,
                        'smtp_host' => $smtpHost,
                        'smtp_port' => $smtpPort,
                        'smtp_user' => $smtpUser,
                        'smtp_pass' => $smtpPass,
                        'demo_mode' => '0',
                        'security_user_protection' => '1',
                        'security_ip_protection' => '1',
                        'security_brute_period_mins' => '15',
                        'security_max_account_failures' => '5',
                        'security_max_ip_failures' => '5',
                        'security_ip_block_duration' => 'one_day',
                        'security_notify_unwhitelisted_login' => '1',
                        'security_notify_bruteforce' => '1'
                    ];

                    $stmtSetting = $pdo->prepare("INSERT OR REPLACE INTO settings (setting_key, setting_value) VALUES (:k, :v)");
                    foreach ($settingsMap as $k => $v) {
                        $stmtSetting->execute(['k' => $k, 'v' => $v]);
                    }

                    // Seed Premium Templates
                    seed_default_templates($pdo);

                    // Mark installed
                    file_put_contents(__DIR__ . '/../config/installed.lock', date('Y-m-d H:i:s'));

                    header('Location: index.php?stage=4');
                    exit;
                } catch (\Exception $e) {
                    $error = 'Failed to setup admin account: ' . $e->getMessage();
                }
            }
        }
    }
}

/**
 * Seed premium sample templates from card-sample
 */
function seed_default_templates($pdo) {
    $samples = [
        [
            'title' => 'Corporate Identity ID Card',
            'type' => 'id_card',
            'orientation' => 'portrait',
            'width_px' => 600,
            'height_px' => 960,
            'front_bg' => 'card-sample/corporate-id-card-template-scaled.jpg',
            'back_bg' => 'card-sample/modern-office-id-card-design-template-corporate-identity-card-design-vectoe_599186-631.avif',
            'fields' => json_encode([
                ['id' => 'name', 'label' => 'Full Name', 'type' => 'text', 'default' => 'ALEX JOHNSON', 'x' => 50, 'y' => 450, 'font' => 'Inter', 'size' => 28, 'color' => '#1e293b', 'align' => 'center', 'bold' => true],
                ['id' => 'role', 'label' => 'Job Title / Role', 'type' => 'text', 'default' => 'SENIOR SOFTWARE ENGINEER', 'x' => 50, 'y' => 495, 'font' => 'Inter', 'size' => 16, 'color' => '#3b82f6', 'align' => 'center', 'bold' => true],
                ['id' => 'id_no', 'label' => 'ID Number', 'type' => 'text', 'default' => 'ID NO: EMP-89210', 'x' => 50, 'y' => 540, 'font' => 'Inter', 'size' => 16, 'color' => '#475569', 'align' => 'center', 'bold' => false],
                ['id' => 'dept', 'label' => 'Department', 'type' => 'text', 'default' => 'DEPT: INNOVATION & TECH', 'x' => 50, 'y' => 575, 'font' => 'Inter', 'size' => 16, 'color' => '#475569', 'align' => 'center', 'bold' => false],
                ['id' => 'photo', 'label' => 'Member Photo', 'type' => 'image', 'x' => 50, 'y' => 260, 'width' => 180, 'height' => 220, 'shape' => 'round']
            ])
        ],
        [
            'title' => 'Executive Premium Business Card',
            'type' => 'business_card',
            'orientation' => 'landscape',
            'width_px' => 1050,
            'height_px' => 600,
            'front_bg' => 'card-sample/business-card-template_1435-1940.avif',
            'back_bg' => 'card-sample/elegant-business-card-template-abstract-260nw-2271231581.webp',
            'fields' => json_encode([
                ['id' => 'name', 'label' => 'Full Name', 'type' => 'text', 'default' => 'SARAH CONNOR', 'x' => 100, 'y' => 200, 'font' => 'Inter', 'size' => 32, 'color' => '#0f172a', 'align' => 'left', 'bold' => true],
                ['id' => 'role', 'label' => 'Job Title', 'type' => 'text', 'default' => 'CHIEF EXECUTIVE OFFICER', 'x' => 100, 'y' => 245, 'font' => 'Inter', 'size' => 16, 'color' => '#2563eb', 'align' => 'left', 'bold' => true],
                ['id' => 'phone', 'label' => 'Phone', 'type' => 'text', 'default' => '+1 (555) 019-2834', 'x' => 100, 'y' => 330, 'font' => 'Inter', 'size' => 16, 'color' => '#334155', 'align' => 'left', 'bold' => false],
                ['id' => 'email', 'label' => 'Email Address', 'type' => 'text', 'default' => 'sarah@company.com', 'x' => 100, 'y' => 370, 'font' => 'Inter', 'size' => 16, 'color' => '#334155', 'align' => 'left', 'bold' => false],
                ['id' => 'company', 'label' => 'Company Name', 'type' => 'text', 'default' => 'APEX GLOBAL MEDIA', 'x' => 100, 'y' => 130, 'font' => 'Inter', 'size' => 22, 'color' => '#1e293b', 'align' => 'left', 'bold' => true]
            ])
        ]
    ];

    $stmt = $pdo->prepare("INSERT INTO card_templates (title, type, orientation, width_px, height_px, front_bg_image, back_bg_image, fields_json) VALUES (:t, :type, :ori, :w, :h, :f, :b, :fields)");
    foreach ($samples as $s) {
        $stmt->execute([
            't' => $s['title'],
            'type' => $s['type'],
            'ori' => $s['orientation'],
            'w' => $s['width_px'],
            'h' => $s['height_px'],
            'f' => $s['front_bg'],
            'b' => $s['back_bg'],
            'fields' => $s['fields']
        ]);
    }
}

// Stage 1 requirements check
$phpReq = version_compare(PHP_VERSION, '7.4.0', '>=');
$extPdo = extension_loaded('pdo');
$extCurl = extension_loaded('curl');
$extGd = extension_loaded('gd');
$allRequirementsMet = $phpReq && $extPdo && $extCurl && $extGd;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CARD-CREATOR Setup & Verification Engine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex items-center justify-center p-4">

<div class="max-w-2xl w-full bg-slate-800 border border-slate-700 rounded-2xl shadow-2xl p-6 sm:p-8">
    <!-- Header -->
    <div class="flex items-center space-x-3 mb-6 pb-6 border-b border-slate-700">
        <div class="w-12 h-12 rounded-xl bg-blue-600 flex items-center justify-center text-2xl font-bold text-white shadow-lg shadow-blue-500/30">
            C
        </div>
        <div>
            <h1 class="text-2xl font-bold text-white">CARD-CREATOR Setup Wizard</h1>
            <p class="text-sm text-slate-400">Step-by-step application installation and system configuration</p>
        </div>
    </div>

    <!-- Stepper -->
    <div class="grid grid-cols-4 gap-2 mb-8">
        <?php
        $steps = [1 => 'System Check', 2 => 'Database', 3 => 'Admin Account', 4 => 'Complete'];
        foreach ($steps as $stepNum => $stepLabel):
            $active = $stage === $stepNum;
            $completed = $stage > $stepNum;
        ?>
            <div class="flex flex-col items-center">
                <div class="w-8 h-8 rounded-full flex items-center justify-center font-semibold text-sm mb-1
                    <?= $completed ? 'bg-emerald-500 text-white' : ($active ? 'bg-blue-600 text-white shadow-md shadow-blue-500/50' : 'bg-slate-700 text-slate-400') ?>">
                    <?= $completed ? '✓' : $stepNum ?>
                </div>
                <span class="text-xs text-center <?= $active ? 'text-blue-400 font-semibold' : 'text-slate-400' ?>"><?= $stepLabel ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($error): ?>
        <div class="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400 text-sm flex items-center space-x-2">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <!-- STAGE 1: REQUIREMENTS & ACTIVATION -->
    <?php if ($stage === 1): ?>
        <form method="POST" class="space-y-6">
            <input type="hidden" name="action" value="stage1_activation">
            <h2 class="text-lg font-semibold text-white">Stage 1: System Requirements & Verification</h2>

            <div class="bg-slate-900/60 p-4 rounded-xl border border-slate-700/60 space-y-3">
                <div class="flex justify-between items-center text-sm">
                    <span>PHP Version (>= 7.4.0): <strong class="text-slate-300"><?= PHP_VERSION ?></strong></span>
                    <span class="<?= $phpReq ? 'text-emerald-400' : 'text-red-400' ?>"><?= $phpReq ? '✓ Pass' : '✗ Fail' ?></span>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <span>PDO Extension:</span>
                    <span class="<?= $extPdo ? 'text-emerald-400' : 'text-red-400' ?>"><?= $extPdo ? '✓ Pass' : '✗ Fail' ?></span>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <span>cURL Extension:</span>
                    <span class="<?= $extCurl ? 'text-emerald-400' : 'text-red-400' ?>"><?= $extCurl ? '✓ Pass' : '✗ Fail' ?></span>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <span>GD / Image Extension:</span>
                    <span class="<?= $extGd ? 'text-emerald-400' : 'text-red-400' ?>"><?= $extGd ? '✓ Pass' : '✗ Fail' ?></span>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">System Key / Passcode Required</label>
                <input type="text" name="activation_key" placeholder="Enter your system key" required value="<?= htmlspecialchars($_SESSION['installer_key'] ?? '') ?>" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>

            <button type="submit" <?= !$allRequirementsMet ? 'disabled' : '' ?> class="w-full py-3.5 px-4 bg-blue-600 hover:bg-blue-500 disabled:bg-slate-700 disabled:cursor-not-allowed font-semibold text-white rounded-xl shadow-lg transition duration-200">
                Continue to Database Setup →
            </button>
        </form>
    <?php endif; ?>

    <!-- STAGE 2: DATABASE CONFIG -->
    <?php if ($stage === 2): ?>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="stage2_database">
            <h2 class="text-lg font-semibold text-white">Stage 2: Database Configuration & Schema</h2>

            <div class="grid grid-cols-3 gap-4">
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-slate-400 mb-1">Database Host</label>
                    <input type="text" name="db_host" value="127.0.0.1" required class="w-full px-3 py-2.5 rounded-lg bg-slate-900 border border-slate-700 text-white text-sm focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Port</label>
                    <input type="text" name="db_port" value="3306" required class="w-full px-3 py-2.5 rounded-lg bg-slate-900 border border-slate-700 text-white text-sm focus:outline-none focus:border-blue-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Database Name</label>
                <input type="text" name="db_name" value="card_creator_db" required class="w-full px-3 py-2.5 rounded-lg bg-slate-900 border border-slate-700 text-white text-sm focus:outline-none focus:border-blue-500">
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Database Username</label>
                <input type="text" name="db_user" value="root" required class="w-full px-3 py-2.5 rounded-lg bg-slate-900 border border-slate-700 text-white text-sm focus:outline-none focus:border-blue-500">
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Database Password</label>
                <input type="password" name="db_pass" placeholder="••••••••" class="w-full px-3 py-2.5 rounded-lg bg-slate-900 border border-slate-700 text-white text-sm focus:outline-none focus:border-blue-500">
            </div>

            <button type="submit" class="w-full mt-4 py-3.5 px-4 bg-blue-600 hover:bg-blue-500 font-semibold text-white rounded-xl shadow-lg transition duration-200">
                Install Database Schema & Proceed →
            </button>
        </form>
    <?php endif; ?>

    <!-- STAGE 3: ADMIN ACCOUNT & SMTP -->
    <?php if ($stage === 3): ?>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="stage3_admin">
            <h2 class="text-lg font-semibold text-white">Stage 3: Admin Details & System Settings</h2>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Website Title</label>
                    <input type="text" name="site_title" value="CARD-CREATOR" required class="w-full px-3 py-2.5 rounded-lg bg-slate-900 border border-slate-700 text-white text-sm focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Admin Username</label>
                    <input type="text" name="admin_user" value="admin" required class="w-full px-3 py-2.5 rounded-lg bg-slate-900 border border-slate-700 text-white text-sm focus:outline-none focus:border-blue-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Admin Email Address</label>
                    <input type="email" name="admin_email" value="admin@example.com" required class="w-full px-3 py-2.5 rounded-lg bg-slate-900 border border-slate-700 text-white text-sm focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Admin Password</label>
                    <input type="password" name="admin_pass" required placeholder="••••••••" class="w-full px-3 py-2.5 rounded-lg bg-slate-900 border border-slate-700 text-white text-sm focus:outline-none focus:border-blue-500">
                </div>
            </div>

            <div class="pt-2 border-t border-slate-700">
                <p class="text-xs font-semibold text-blue-400 mb-3">SMTP Mailer Settings (Optional during setup)</p>
                <div class="grid grid-cols-2 gap-3">
                    <input type="text" name="smtp_host" placeholder="SMTP Host (e.g. smtp.mailtrap.io)" class="px-3 py-2 rounded-lg bg-slate-900 border border-slate-700 text-white text-xs">
                    <input type="text" name="smtp_port" placeholder="SMTP Port (587)" value="587" class="px-3 py-2 rounded-lg bg-slate-900 border border-slate-700 text-white text-xs">
                    <input type="text" name="smtp_user" placeholder="SMTP User" class="px-3 py-2 rounded-lg bg-slate-900 border border-slate-700 text-white text-xs">
                    <input type="password" name="smtp_pass" placeholder="SMTP Password" class="px-3 py-2 rounded-lg bg-slate-900 border border-slate-700 text-white text-xs">
                </div>
            </div>

            <button type="submit" class="w-full mt-4 py-3.5 px-4 bg-emerald-600 hover:bg-emerald-500 font-semibold text-white rounded-xl shadow-lg transition duration-200">
                Complete Installation →
            </button>
        </form>
    <?php endif; ?>

    <!-- STAGE 4: CONGRATULATIONS -->
    <?php if ($stage === 4): ?>
        <div class="text-center space-y-6 py-4">
            <div class="w-16 h-16 bg-emerald-500/20 text-emerald-400 rounded-full flex items-center justify-center mx-auto border border-emerald-500/30">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            </div>

            <h2 class="text-2xl font-bold text-white">Congratulations! Installation Complete</h2>
            <p class="text-slate-300 text-sm max-w-md mx-auto">
                CARD-CREATOR has been successfully configured. You can now log into your Admin Panel to manage card templates, anti-brute-force security settings, and dynamic forms.
            </p>

            <div class="p-4 rounded-xl bg-slate-900/80 border border-slate-700 text-left text-sm space-y-2">
                <p class="text-xs text-slate-400 font-semibold uppercase">Admin Login Guidelines:</p>
                <p class="text-slate-300">1. Sign in using your Admin credentials.</p>
                <p class="text-slate-300">2. Verify SMTP mailer settings under Admin Security & Settings.</p>
                <p class="text-slate-300">3. Monitor Whitelisted IPs and Anti-Brute-Force log records on the Admin Dashboard.</p>
            </div>

            <a href="../login.php" class="inline-block w-full py-3.5 px-4 bg-blue-600 hover:bg-blue-500 font-semibold text-white rounded-xl shadow-lg transition duration-200">
                Go to Admin Sign In →
            </a>
        </div>
    <?php endif; ?>

</div>

</body>
</html>
