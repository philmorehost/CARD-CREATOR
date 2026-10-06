<?php
/**
 * Security, Anti-Brute-Force & Email Notification Engine
 */

require_once __DIR__ . '/helpers.php';

class SecurityEngine {

    /**
     * Mailer helper using PHP mail() or configured SMTP settings
     */
    public static function sendEmail($to, $subject, $message) {
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
                if ($socket) {
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

                    $read(); // banner
                    $write("EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
                    $read();

                    if ($smtpPort == 587) {
                        $write("STARTTLS");
                        $res = $read();
                        if (substr($res, 0, 3) == '220') {
                            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT);
                            $write("EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
                            $read();
                        }
                    }

                    if (!empty($smtpUser) && !empty($smtpPass)) {
                        $write("AUTH LOGIN");
                        $read();
                        $write(base64_encode($smtpUser));
                        $read();
                        $write(base64_encode($smtpPass));
                        $read();
                    }

                    $write("MAIL FROM: <{$fromEmail}>");
                    $read();
                    $write("RCPT TO: <{$to}>");
                    $read();
                    $write("DATA");
                    $read();

                    $headers = "MIME-Version: 1.0\r\n";
                    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                    $headers .= "From: {$siteTitle} <{$fromEmail}>\r\n";
                    $headers .= "To: <{$to}>\r\n";
                    $headers .= "Subject: {$subject}\r\n";

                    $write($headers . "\r\n" . $message . "\r\n.");
                    $read();
                    $write("QUIT");
                    fclose($socket);
                    return true;
                }
            } catch (\Exception $ex) {
                error_log("SMTP Error: " . $ex->getMessage());
            }
        }

        // Fallback to PHP mail() if SMTP is not configured or failed
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$siteTitle} Security <{$fromEmail}>\r\n";

        $logDir = __DIR__ . '/../logs/';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }
        $logEntry = "[" . date('Y-m-d H:i:s') . "] TO: {$to} | SUBJECT: {$subject} | METHOD: Native mail() Fallback\n";
        @file_put_contents($logDir . 'email.log', $logEntry, FILE_APPEND);

        @mail($to, $subject, $message, $headers);
        return true;
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
                $subject = "Security Alert: Successful Login from Non-Whitelisted IP (" . $ip . ")";
                $msg = "<p>Hello Admin,</p><p>A successful login to your CARD-CREATOR account was detected from an un-whitelisted IP address: <strong>{$ip}</strong> on " . date('Y-m-d H:i:s') . ".</p>";
                self::sendEmail($adminEmail, $subject, $msg);
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

                $stmtBlock = $pdo->prepare("INSERT INTO ip_blocks (ip_address, reason, blocked_until, created_at) VALUES (:ip, 'Automated IP Brute Force Protection Triggered', :bu, :ca)");
                $stmtBlock->execute(['ip' => $ip, 'bu' => $blockedUntil, 'ca' => date('Y-m-d H:i:s')]);

                if (get_setting('security_notify_bruteforce', '1') === '1') {
                    $adminEmail = get_setting('admin_email', 'admin@example.com');
                    $subject = "Security Alert: Brute-force Attack Detected from IP {$ip}";
                    $msg = "<p>Hello Admin,</p><p>The system detected a brute-force attack from IP: <strong>{$ip}</strong> using targeted username: <strong>" . htmlspecialchars($username) . "</strong>.</p>";
                    self::sendEmail($adminEmail, $subject, $msg);
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
        if (!$pdo) return false;

        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = :e");
        $stmt->execute(['e' => $email]);
        $user = $stmt->fetch();

        if (!$user) return false;

        $otp = sprintf("%06d", mt_rand(100000, 999999));
        $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        $stmtUpd = $pdo->prepare("UPDATE users SET otp_code = :o, otp_expires_at = :ex WHERE id = :id");
        $stmtUpd->execute(['o' => $otp, 'ex' => $expires, 'id' => $user['id']]);

        $subject = "Your Account Recovery OTP - CARD-CREATOR";
        $msg = "<p>Hello " . htmlspecialchars($user['username']) . ",</p><p>Your One-Time Password (OTP) for account unblocking and password reset is: <strong>{$otp}</strong></p>";

        return self::sendEmail($email, $subject, $msg);
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
