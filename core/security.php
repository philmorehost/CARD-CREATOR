<?php
/**
 * Security, Anti-Brute-Force & Email Notification Engine
 */

require_once __DIR__ . '/helpers.php';

class SecurityEngine {

    /**
     * Wraps raw email content into a modern, responsive HTML email template
     */
    public static function renderEmailTemplate($subject, $contentHtml, $preheader = '') {
        $siteTitle = htmlspecialchars(get_setting('site_title', 'CARD-CREATOR'));
        $firstChar = strtoupper(substr($siteTitle, 0, 1));
        $currentYear = date('Y');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$subject}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #0f172a;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #334155;
            -webkit-text-size-adjust: 100%;
        }
        table {
            border-spacing: 0;
            border-collapse: collapse;
        }
        td {
            padding: 0;
        }
        .wrapper {
            width: 100%;
            table-layout: fixed;
            background-color: #0f172a;
            padding: 40px 0;
        }
        .main-card {
            background-color: #ffffff;
            margin: 0 auto;
            width: 100%;
            max-width: 600px;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
        }
        .header {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 50%, #0f172a 100%);
            padding: 32px 40px;
            text-align: left;
        }
        .logo-badge {
            display: inline-block;
            width: 44px;
            height: 44px;
            line-height: 44px;
            background: #38bdf8;
            color: #0f172a;
            border-radius: 12px;
            font-size: 22px;
            font-weight: 800;
            text-align: center;
            vertical-align: middle;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2);
        }
        .header-title {
            display: inline-block;
            color: #ffffff;
            font-size: 22px;
            font-weight: 700;
            margin-left: 12px;
            vertical-align: middle;
            letter-spacing: -0.5px;
        }
        .content {
            padding: 40px;
            font-size: 15px;
            line-height: 1.6;
            color: #334155;
        }
        .content h2 {
            margin-top: 0;
            color: #0f172a;
            font-size: 20px;
            font-weight: 700;
        }
        .otp-box {
            background: #f0f9ff;
            border: 2px dashed #0284c7;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin: 24px 0;
        }
        .otp-code {
            font-family: "Courier New", Courier, monospace;
            font-size: 32px;
            font-weight: 800;
            letter-spacing: 6px;
            color: #0369a1;
        }
        .alert-box {
            background-color: #fef2f2;
            border-left: 4px solid #ef4444;
            padding: 16px 20px;
            border-radius: 6px;
            margin: 20px 0;
            color: #991b1b;
        }
        .info-table {
            width: 100%;
            margin: 20px 0;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .info-table td {
            padding: 12px 16px;
            font-size: 14px;
            border-bottom: 1px solid #e2e8f0;
        }
        .info-table tr:last-child td {
            border-bottom: none;
        }
        .footer {
            background-color: #f8fafc;
            padding: 24px 40px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            font-size: 13px;
            color: #64748b;
        }
        .btn {
            display: inline-block;
            padding: 12px 28px;
            background-color: #0284c7;
            color: #ffffff !important;
            text-decoration: none;
            font-weight: 600;
            border-radius: 8px;
            margin-top: 16px;
            box-shadow: 0 4px 6px -1px rgba(2, 132, 199, 0.3);
        }
    </style>
</head>
<body>
    <div style="display:none;font-size:1px;color:#0f172a;line-height:1px;max-height:0px;max-width:0px;opacity:0;overflow:hidden;">
        {$preheader}
    </div>
    <div class="wrapper">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
            <tr>
                <td align="center">
                    <div class="main-card">
                        <div class="header">
                            <span class="logo-badge">{$firstChar}</span>
                            <span class="header-title">{$siteTitle}</span>
                        </div>
                        <div class="content">
                            {$contentHtml}
                        </div>
                        <div class="footer">
                            <p style="margin:0 0 8px 0;">This is an automated notification from <strong>{$siteTitle}</strong>.</p>
                            <p style="margin:0;">&copy; {$currentYear} {$siteTitle}. All rights reserved.</p>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Mailer helper using PHP mail() or configured SMTP settings with detailed status output
     * Returns array ['success' => bool, 'error' => string]
     */
    public static function sendEmail($to, $subject, $message, $preheader = '') {
        if (is_demo_mode()) {
            return [
                'success' => false,
                'error' => 'Email dispatch is disabled while running in Demo Mode.'
            ];
        }

        // Wrap message in modern HTML template if not already a full HTML document
        if (stripos($message, '<!DOCTYPE html>') === false && stripos($message, '<html') === false) {
            $message = self::renderEmailTemplate($subject, $message, $preheader);
        }

        $siteTitle = get_setting('site_title', 'CARD-CREATOR');
        $smtpHost = get_setting('smtp_host', '');
        $smtpPort = intval(get_setting('smtp_port', '587'));
        $smtpUser = get_setting('smtp_user', '');
        $smtpPass = get_setting('smtp_pass', '');
        $fromEmail = get_setting('admin_email', 'no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));

        if (!empty($smtpHost)) {
            try {
                $host = ($smtpPort == 465 ? 'ssl://' : '') . $smtpHost;
                $socket = @fsockopen($host, $smtpPort, $errno, $errstr, 10);
                if (!$socket) {
                    return [
                        'success' => false,
                        'error' => "Failed to connect to SMTP host {$smtpHost}:{$smtpPort} - ({$errno}) {$errstr}"
                    ];
                }

                stream_set_timeout($socket, 10);

                $read = function() use ($socket) {
                    $res = '';
                    while ($str = fgets($socket, 515)) {
                        $res .= $str;
                        if (substr($str, 3, 1) == ' ') break;
                    }
                    return $res;
                };

                $write = function($cmd) use ($socket) {
                    fputs($socket, $cmd . "\r\n");
                };

                $response = $read(); // Banner
                if (substr($response, 0, 3) != '220') {
                    fclose($socket);
                    return ['success' => false, 'error' => 'SMTP greeting failed: ' . trim($response)];
                }

                $write("EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
                $response = $read();

                if ($smtpPort == 587) {
                    $write("STARTTLS");
                    $res = $read();
                    if (substr($res, 0, 3) == '220') {
                        $crypto = stream_socket_enable_crypto(
                            $socket,
                            true,
                            STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT
                        );
                        if (!$crypto) {
                            fclose($socket);
                            return ['success' => false, 'error' => 'TLS encryption handshake failed on port 587.'];
                        }
                        $write("EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
                        $read();
                    } else {
                        fclose($socket);
                        return ['success' => false, 'error' => 'SMTP server refused STARTTLS: ' . trim($res)];
                    }
                }

                if (!empty($smtpUser) && !empty($smtpPass)) {
                    $write("AUTH LOGIN");
                    $res = $read();
                    if (substr($res, 0, 3) != '334') {
                        fclose($socket);
                        return ['success' => false, 'error' => 'AUTH LOGIN not accepted: ' . trim($res)];
                    }

                    $write(base64_encode($smtpUser));
                    $res = $read();
                    if (substr($res, 0, 3) != '334') {
                        fclose($socket);
                        return ['success' => false, 'error' => 'SMTP Username rejected: ' . trim($res)];
                    }

                    $write(base64_encode($smtpPass));
                    $res = $read();
                    if (substr($res, 0, 3) != '235') {
                        fclose($socket);
                        return ['success' => false, 'error' => 'SMTP Authentication failed: ' . trim($res)];
                    }
                }

                $write("MAIL FROM: <{$fromEmail}>");
                $res = $read();
                if (substr($res, 0, 3) != '250') {
                    fclose($socket);
                    return ['success' => false, 'error' => 'Sender address rejected: ' . trim($res)];
                }

                $write("RCPT TO: <{$to}>");
                $res = $read();
                if (substr($res, 0, 3) != '250' && substr($res, 0, 3) != '251') {
                    fclose($socket);
                    return ['success' => false, 'error' => 'Recipient address rejected: ' . trim($res)];
                }

                $write("DATA");
                $res = $read();
                if (substr($res, 0, 3) != '354') {
                    fclose($socket);
                    return ['success' => false, 'error' => 'DATA command rejected: ' . trim($res)];
                }

                $headers = "MIME-Version: 1.0\r\n";
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                $headers .= "From: {$siteTitle} <{$fromEmail}>\r\n";
                $headers .= "To: <{$to}>\r\n";
                $headers .= "Subject: {$subject}\r\n";

                $write($headers . "\r\n" . $message . "\r\n.");
                $res = $read();

                $write("QUIT");
                fclose($socket);

                if (substr($res, 0, 3) == '250') {
                    return ['success' => true, 'error' => ''];
                } else {
                    return ['success' => false, 'error' => 'SMTP server error on message delivery: ' . trim($res)];
                }

            } catch (\Exception $ex) {
                error_log("SMTP Error: " . $ex->getMessage());
                return ['success' => false, 'error' => 'SMTP Exception: ' . $ex->getMessage()];
            }
        }

        // Native PHP mail() fallback
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$siteTitle} Security <{$fromEmail}>\r\n";

        $mailSent = @mail($to, $subject, $message, $headers);
        if ($mailSent) {
            return ['success' => true, 'error' => ''];
        } else {
            return [
                'success' => false,
                'error' => 'Native PHP mail() failed to send message. Please configure valid SMTP settings in the Admin Panel.'
            ];
        }
    }

    /**
     * Check if client IP or username is currently blocked by brute force protection
     */
    public static function checkAccessBlock($username = '') {
        $pdo = get_db_connection();
        if (!$pdo) return ['blocked' => false];

        $ip = get_client_ip();
        $now = date('Y-m-d H:i:s');

        // 1. Check IP Blocks
        if (get_setting('security_ip_protection', '1') === '1') {
            $stmt = $pdo->prepare("SELECT * FROM ip_blocks WHERE ip_address = :ip AND (is_permanent = 1 OR blocked_until > :now)");
            $stmt->execute(['ip' => $ip, 'now' => $now]);
            $block = $stmt->fetch();

            if ($block) {
                return [
                    'blocked' => true,
                    'reason' => 'IP Address blocked due to excessive failed attempts: ' . htmlspecialchars($block['reason'])
                ];
            }
        }

        // 2. Check Account Suspension (Username Protection)
        if (!empty($username) && get_setting('security_user_protection', '1') === '1') {
            $allowAdminLock = get_setting('security_lock_admin_user', '1') === '1';
            $isDefaultAdmin = in_array(strtolower($username), ['admin', 'administrator']);

            if (!$isDefaultAdmin || $allowAdminLock) {
                $stmt = $pdo->prepare("SELECT is_suspended FROM users WHERE username = :u");
                $stmt->execute(['u' => $username]);
                $user = $stmt->fetch();

                if ($user && $user['is_suspended'] == 1) {
                    return [
                        'blocked' => true,
                        'reason' => 'User account is suspended due to anti-brute-force security policy.'
                    ];
                }
            }
        }

        return ['blocked' => false];
    }

    /**
     * Record a login attempt and apply brute-force locks if limits exceeded
     */
    public static function recordLoginAttempt($username, $success) {
        $pdo = get_db_connection();
        if (!$pdo) return;

        $ip = get_client_ip();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $stmt = $pdo->prepare("INSERT INTO login_logs (username, ip_address, status, user_agent, created_at) VALUES (:u, :ip, :st, :ua, :ca)");
        $stmt->execute([
            'u' => $username,
            'ip' => $ip,
            'st' => $success ? 'success' : 'failed',
            'ua' => $userAgent,
            'ca' => date('Y-m-d H:i:s')
        ]);

        if ($success) {
            self::handleSuccessfulLogin($username, $ip);
        } else {
            self::handleFailedLogin($username, $ip);
        }
    }

    private static function handleSuccessfulLogin($username, $ip) {
        $pdo = get_db_connection();
        if (!$pdo) return;

        $stmt = $pdo->prepare("SELECT id, successful_sessions_count FROM ip_whitelists WHERE ip_address = :ip");
        $stmt->execute(['ip' => $ip]);
        $wl = $stmt->fetch();

        if ($wl) {
            $newCount = $wl['successful_sessions_count'] + 1;
            $stmtUpd = $pdo->prepare("UPDATE ip_whitelists SET successful_sessions_count = :c WHERE id = :id");
            $stmtUpd->execute(['c' => $newCount, 'id' => $wl['id']]);
        } else {
            $stmtFail = $pdo->prepare("SELECT COUNT(*) FROM login_logs WHERE ip_address = :ip AND status = 'failed'");
            $stmtFail->execute(['ip' => $ip]);
            $failedCount = $stmtFail->fetchColumn();

            if ($failedCount == 0) {
                $stmtSucc = $pdo->prepare("SELECT COUNT(*) FROM login_logs WHERE ip_address = :ip AND status = 'success'");
                $stmtSucc->execute(['ip' => $ip]);
                $succCount = $stmtSucc->fetchColumn();

                if ($succCount >= 5) {
                    $stmtIns = $pdo->prepare("INSERT INTO ip_whitelists (ip_address, label, successful_sessions_count, is_auto, created_at) VALUES (:ip, 'Recognized Trusted IP', :c, 1, :ca)");
                    $stmtIns->execute(['ip' => $ip, 'c' => $succCount, 'ca' => date('Y-m-d H:i:s')]);
                }
            }
        }

        if (get_setting('security_notify_unwhitelisted_login', '1') === '1') {
            $stmtIsWl = $pdo->prepare("SELECT COUNT(*) FROM ip_whitelists WHERE ip_address = :ip");
            $stmtIsWl->execute(['ip' => $ip]);
            $isWhitelisted = $stmtIsWl->fetchColumn() > 0;

            if (!$isWhitelisted) {
                $adminEmail = get_setting('admin_email', 'admin@example.com');
                $subject = "Security Alert: Successful Login from Non-Whitelisted IP ({$ip})";
                $timeNow = date('Y-m-d H:i:s');
                $ua = htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown');
                $msg = "<h2>Security Alert: New Sign-in Detected</h2>
                <p>Hello Admin,</p>
                <p>A successful login to your account was detected from a non-whitelisted IP address.</p>
                <table class='info-table'>
                    <tr><td><strong>Username</strong></td><td>" . htmlspecialchars($username) . "</td></tr>
                    <tr><td><strong>IP Address</strong></td><td><code>{$ip}</code></td></tr>
                    <tr><td><strong>Time</strong></td><td>{$timeNow}</td></tr>
                    <tr><td><strong>User Agent</strong></td><td>{$ua}</td></tr>
                </table>
                <div class='alert-box'>If this was not you, please immediately sign in to your admin dashboard and review active IP blocks and change your password.</div>";
                self::sendEmail($adminEmail, $subject, $msg, "New successful login from non-whitelisted IP {$ip}");
            }
        }
    }

    private static function handleFailedLogin($username, $ip) {
        $pdo = get_db_connection();
        if (!$pdo) return;

        $bruteMins = intval(get_setting('security_brute_period_mins', '15'));
        $timeThreshold = date('Y-m-d H:i:s', strtotime("-{$bruteMins} minutes"));

        // 1. Check IP-Based Failures
        if (get_setting('security_ip_protection', '1') === '1') {
            $maxIpFailures = intval(get_setting('security_max_ip_failures', '5'));

            $stmtIp = $pdo->prepare("SELECT COUNT(*) FROM login_logs WHERE ip_address = :ip AND status = 'failed' AND created_at >= :tt");
            $stmtIp->execute(['ip' => $ip, 'tt' => $timeThreshold]);
            $ipFailures = $stmtIp->fetchColumn();

            if ($ipFailures >= $maxIpFailures) {
                $duration = get_setting('security_ip_block_duration', 'one_day');
                $blockedUntil = date('Y-m-d H:i:s', strtotime('+1 day'));
                if ($duration === 'one_week') $blockedUntil = date('Y-m-d H:i:s', strtotime('+1 week'));
                if ($duration === 'one_month') $blockedUntil = date('Y-m-d H:i:s', strtotime('+1 month'));
                if ($duration === 'one_year') $blockedUntil = date('Y-m-d H:i:s', strtotime('+1 year'));

                try {
                    $stmtBlock = $pdo->prepare("INSERT INTO ip_blocks (ip_address, reason, blocked_until, created_at) VALUES (:ip, 'Automated IP Brute Force Protection Triggered', :bu, :ca)");
                    $stmtBlock->execute(['ip' => $ip, 'bu' => $blockedUntil, 'ca' => date('Y-m-d H:i:s')]);
                } catch (\Exception $e) {
                    $stmtBlock = $pdo->prepare("UPDATE ip_blocks SET blocked_until = :bu, reason = 'Automated IP Brute Force Protection Triggered' WHERE ip_address = :ip");
                    $stmtBlock->execute(['ip' => $ip, 'bu' => $blockedUntil]);
                }

                if (get_setting('security_notify_bruteforce', '1') === '1') {
                    $adminEmail = get_setting('admin_email', 'admin@example.com');
                    $subject = "Security Alert: Brute-force Attack Blocked (IP: {$ip})";
                    $timeNow = date('Y-m-d H:i:s');
                    $msg = "<h2>Anti-Brute-Force Security Shield Activated</h2>
                    <p>Hello Admin,</p>
                    <p>Our security system automatically blocked an IP address after detecting excessive failed login attempts.</p>
                    <table class='info-table'>
                        <tr><td><strong>Targeted Username</strong></td><td>" . htmlspecialchars($username) . "</td></tr>
                        <tr><td><strong>Offending IP</strong></td><td><code>{$ip}</code></td></tr>
                        <tr><td><strong>Action Taken</strong></td><td><span style='color:#dc2626;font-weight:bold;'>IP Firewall Blocked</span></td></tr>
                        <tr><td><strong>Block Duration Until</strong></td><td>{$blockedUntil}</td></tr>
                        <tr><td><strong>Timestamp</strong></td><td>{$timeNow}</td></tr>
                    </table>
                    <p>No further action is required as the offending IP address is currently isolated by the system firewall.</p>";
                    self::sendEmail($adminEmail, $subject, $msg, "Brute-force attack blocked from IP {$ip}");
                }
            }
        }

        // 2. Check Username-Based Failures
        if (!empty($username) && get_setting('security_user_protection', '1') === '1') {
            $maxUserFailures = intval(get_setting('security_max_account_failures', '5'));

            $stmtUser = $pdo->prepare("SELECT COUNT(*) FROM login_logs WHERE username = :u AND status = 'failed' AND created_at >= :tt");
            $stmtUser->execute(['u' => $username, 'tt' => $timeThreshold]);
            $userFailures = $stmtUser->fetchColumn();

            if ($userFailures >= $maxUserFailures) {
                $allowAdminLock = get_setting('security_lock_admin_user', '1') === '1';
                $isDefaultAdmin = in_array(strtolower($username), ['admin', 'administrator']);

                if (!$isDefaultAdmin || $allowAdminLock) {
                    $stmtLock = $pdo->prepare("UPDATE users SET is_suspended = 1 WHERE username = :u");
                    $stmtLock->execute(['u' => $username]);
                }
            }
        }
    }

    public static function generateAndSendOTP($email) {
        $pdo = get_db_connection();
        if (!$pdo) {
            return ['status' => false, 'message' => 'Database connection unavailable.'];
        }

        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = :e");
        $stmt->execute(['e' => $email]);
        $user = $stmt->fetch();

        if (!$user) {
            // Generic message for security, or explicit feedback if account not found
            return ['status' => false, 'message' => 'No account registered with that email address.'];
        }

        $otp = sprintf("%06d", mt_rand(100000, 999999));
        $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        $stmtUpd = $pdo->prepare("UPDATE users SET otp_code = :o, otp_expires_at = :ex WHERE id = :id");
        $stmtUpd->execute(['o' => $otp, 'ex' => $expires, 'id' => $user['id']]);

        $subject = "Your Account Recovery OTP - CARD-CREATOR";
        $msg = "<h2>Password Reset & Unblock Request</h2>
        <p>Hello <strong>" . htmlspecialchars($user['username']) . "</strong>,</p>
        <p>We received a request to unblock your account or reset your password. Use the One-Time Password (OTP) code below to proceed:</p>
        <div class='otp-box'>
            <div style='font-size:12px;color:#0369a1;text-transform:uppercase;font-weight:700;margin-bottom:6px;'>Your Verification Code</div>
            <div class='otp-code'>{$otp}</div>
        </div>
        <p style='font-size:13px;color:#64748b;text-align:center;'>This code is valid for <strong>15 minutes</strong>. Do not share this code with anyone.</p>
        <div class='alert-box'>If you did not request this OTP, please ignore this email or contact your administrator immediately.</div>";

        $mailRes = self::sendEmail($email, $subject, $msg, "Your verification OTP is {$otp}");
        if ($mailRes['success']) {
            return [
                'status' => true,
                'message' => 'An OTP code has been dispatched to your email address (' . htmlspecialchars($email) . ').'
            ];
        } else {
            return [
                'status' => false,
                'message' => 'Failed to send OTP email: ' . $mailRes['error']
            ];
        }
    }

    public static function verifyOTPAndUnlock($email, $otp, $newPassword) {
        $pdo = get_db_connection();
        if (!$pdo) return ['status' => false, 'message' => 'Database error'];

        $now = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :e AND otp_code = :o AND otp_expires_at >= :now");
        $stmt->execute(['e' => $email, 'o' => $otp, 'now' => $now]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['status' => false, 'message' => 'Invalid or expired OTP code.'];
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmtUpd = $pdo->prepare("UPDATE users SET password_hash = :p, is_suspended = 0, otp_code = NULL, otp_expires_at = NULL WHERE id = :id");
        $stmtUpd->execute(['p' => $newHash, 'id' => $user['id']]);

        return ['status' => true, 'message' => 'Account unlocked and password updated successfully!'];
    }
}
