<?php
require_once __DIR__ . '/core/helpers.php';
require_once __DIR__ . '/core/security.php';

if (!is_installed()) {
    header('Location: installer/index.php');
    exit;
}

if (isset($_SESSION['user_id'])) {
    if (($_SESSION['user_role'] ?? 'staff') === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: studio.php');
    }
    exit;
}

$error = '';
$success = '';
$showOtpForm = isset($_GET['action']) && $_GET['action'] === 'reset_otp';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token (CSRF). Please refresh and try again.';
    } else {
        $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password.';
        } else {
            // Check anti-brute-force block status
            $checkBlock = SecurityEngine::checkAccessBlock($username);
            if ($checkBlock['blocked']) {
                $error = $checkBlock['reason'];
            } else {
                $pdo = get_db_connection();
                if ($pdo) {
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :u OR email = :u");
                    $stmt->execute(['u' => $username]);
                    $user = $stmt->fetch();

                    if ($user && password_verify($password, $user['password_hash'])) {
                        if ($user['is_suspended'] == 1) {
                            $error = 'Your account is suspended. Use the Account Recovery OTP option below to unblock.';
                            SecurityEngine::recordLoginAttempt($username, false);
                        } else {
                            // Record success login
                            SecurityEngine::recordLoginAttempt($username, true);

                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['username'] = $user['username'];
                            $_SESSION['user_role'] = $user['role'];

                            if ($user['role'] === 'admin') {
                                header('Location: admin/dashboard.php');
                            } else {
                                header('Location: studio.php');
                            }
                            exit;
                        }
                    } else {
                        // Record failed login
                        SecurityEngine::recordLoginAttempt($username, false);
                        $error = 'Invalid credentials. Please try again.';
                    }
                }
            }
        }
    } elseif ($action === 'request_otp') {
        $email = trim($_POST['email'] ?? '');
        if (empty($email)) {
            $error = 'Please enter your registered email address.';
            $showOtpForm = true;
        } else {
            $otpRes = SecurityEngine::generateAndSendOTP($email);
            if ($otpRes['status']) {
                $success = $otpRes['message'];
            } else {
                $error = $otpRes['message'];
            }
            $showOtpForm = true;
        }
    } elseif ($action === 'verify_otp') {
        $email = trim($_POST['email'] ?? '');
        $otp = trim($_POST['otp_code'] ?? '');
        $newPass = $_POST['new_password'] ?? '';

        if (empty($email) || empty($otp) || empty($newPass)) {
            $error = 'All fields are required to reset password.';
            $showOtpForm = true;
        } else {
            $res = SecurityEngine::verifyOTPAndUnlock($email, $otp, $newPass);
            if ($res['status']) {
                $success = $res['message'];
                $showOtpForm = false;
            } else {
                $error = $res['message'];
                $showOtpForm = true;
            }
        }
    }
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Sign In - CARD-CREATOR</title>
    <link rel="icon" type="image/svg+xml" href="favicon.php">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex items-center justify-center p-4">

<div class="max-w-md w-full bg-slate-800 border border-slate-700 rounded-2xl shadow-2xl p-6 sm:p-8">
    <!-- Header -->
    <div class="text-center mb-6">
        <div class="inline-flex w-14 h-14 rounded-2xl bg-blue-600 items-center justify-center text-2xl font-bold text-white shadow-lg shadow-blue-500/30 mb-3">
            <?= htmlspecialchars(strtoupper(substr(get_setting('site_title', 'C'), 0, 1))) ?>
        </div>
        <h1 class="text-2xl font-bold text-white"><?= htmlspecialchars(get_setting('site_title', 'CARD-CREATOR')) ?></h1>
        <p class="text-xs text-slate-400 mt-1">Admin Management Portal</p>
    </div>

    <?php if ($error): ?>
        <div class="mb-5 p-3.5 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400 text-sm">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="mb-5 p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if (!$showOtpForm): ?>
        <!-- LOGIN FORM -->
        <form method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            <input type="hidden" name="action" value="login">

            <div>
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Username or Email</label>
                <input type="text" name="username" required placeholder="admin" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Password</label>
                <input type="password" name="password" required placeholder="••••••••" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>

            <button type="submit" class="w-full py-3.5 px-4 bg-blue-600 hover:bg-blue-500 font-semibold text-white rounded-xl shadow-lg transition duration-200">
                Sign In to Dashboard →
            </button>
        </form>

        <div class="mt-6 pt-4 border-t border-slate-700 text-center text-xs">
            <a href="login.php?action=reset_otp" class="text-blue-400 hover:underline">Locked out or forgot password? Account Recovery OTP →</a>
        </div>

    <?php else: ?>
        <!-- OTP ACCOUNT UNBLOCK / RESET FORM -->
        <form method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            <input type="hidden" name="action" value="verify_otp">
            <p class="text-xs text-slate-400 mb-2">Request OTP or enter your received OTP to unlock account and reset password.</p>

            <div>
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Registered Email</label>
                <div class="flex space-x-2">
                    <input type="email" name="email" id="reset_email" required placeholder="admin@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" class="w-full px-3 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm">
                    <button type="submit" formaction="login.php" name="action" value="request_otp" class="px-3 py-2.5 bg-slate-700 hover:bg-slate-600 text-xs font-semibold text-slate-200 rounded-xl whitespace-nowrap">Send OTP</button>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">6-Digit OTP Code</label>
                <input type="text" name="otp_code" placeholder="123456" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">New Password</label>
                <input type="password" name="new_password" placeholder="••••••••" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm">
            </div>

            <button type="submit" class="w-full py-3.5 px-4 bg-emerald-600 hover:bg-emerald-500 font-semibold text-white rounded-xl shadow-lg transition duration-200">
                Verify OTP & Unlock Account →
            </button>
        </form>

        <div class="mt-6 pt-4 border-t border-slate-700 text-center text-xs">
            <a href="login.php" class="text-slate-400 hover:underline">← Back to Login</a>
        </div>
    <?php endif; ?>

</div>

</body>
</html>
