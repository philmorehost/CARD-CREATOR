<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Get system database connection if installed
 */
function get_db_connection(&$errorMsg = null) {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $configFile = __DIR__ . '/../config/database.php';
    if (!file_exists($configFile)) {
        $errorMsg = 'Database configuration file (config/database.php) not found.';
        return null;
    }

    $config = require $configFile;

    try {
        if (($config['driver'] ?? 'mysql') === 'sqlite') {
            $defaultDbPath = dirname(__DIR__) . '/card_creator.sqlite';
            $configuredPath = $config['db_path'] ?? '';

            if (!empty($configuredPath) && file_exists($configuredPath) && is_writable($configuredPath)) {
                $dbPath = $configuredPath;
            } elseif (!empty($configuredPath) && file_exists(dirname($configuredPath)) && is_writable(dirname($configuredPath))) {
                $dbPath = $configuredPath;
            } else {
                $dbPath = $defaultDbPath;
            }

            $pdo = new PDO("sqlite:" . $dbPath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Auto-migrate tables if missing
            $pdo->exec("CREATE TABLE IF NOT EXISTS card_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                cardholder_name VARCHAR(150),
                card_type VARCHAR(50) NOT NULL,
                template_title VARCHAR(150),
                data_json TEXT,
                preview_front TEXT,
                preview_back TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            // Auto-migrate additional columns if missing
            try { $pdo->exec("ALTER TABLE users ADD COLUMN otp_code VARCHAR(10)"); } catch (\Exception $e) {}
            try { $pdo->exec("ALTER TABLE users ADD COLUMN otp_expires_at DATETIME"); } catch (\Exception $e) {}
            try { $pdo->exec("ALTER TABLE login_logs ADD COLUMN user_agent VARCHAR(255)"); } catch (\Exception $e) {}
            try { $pdo->exec("ALTER TABLE ip_blocks ADD COLUMN is_permanent INTEGER DEFAULT 0"); } catch (\Exception $e) {}
            try { $pdo->exec("ALTER TABLE ip_whitelists ADD COLUMN label VARCHAR(100)"); } catch (\Exception $e) {}
            try { $pdo->exec("ALTER TABLE ip_whitelists ADD COLUMN successful_sessions_count INTEGER DEFAULT 1"); } catch (\Exception $e) {}
            try { $pdo->exec("ALTER TABLE ip_whitelists ADD COLUMN is_auto INTEGER DEFAULT 0"); } catch (\Exception $e) {}

            return $pdo;
        }

        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? 3306;
        $dbName = $config['db_name'] ?? '';
        $user = $config['user'] ?? '';
        $pass = $config['pass'] ?? '';

        $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        return $pdo;
    } catch (\PDOException $e) {
        $errorMsg = $e->getMessage();
        error_log("Database connection error: " . $e->getMessage());
        return null;
    }
}

/**
 * Fetch system setting from database
 */
function get_setting($key, $default = '') {
    static $settings = null;

    $pdo = get_db_connection();
    if (!$pdo) {
        return $default;
    }

    if ($settings === null) {
        try {
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
            $settings = [];
            while ($row = $stmt->fetch()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (\Exception $e) {
            return $default;
        }
    }

    return $settings[$key] ?? $default;
}

/**
 * Save or update system setting
 */
function set_setting($key, $value) {
    $pdo = get_db_connection();
    if (!$pdo) return false;

    if (is_demo_mode()) {
        return false;
    }

    $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (:key, :val)");
    return $stmt->execute(['key' => $key, 'val' => $value]);
}

/**
 * Check if app is in Demo mode
 */
function is_demo_mode() {
    return get_setting('demo_mode', '0') === '1';
}

/**
 * Sanitize string input
 */
function sanitize_input($data) {
    return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate & Verify CSRF Token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
}

/**
 * JSON Response helper
 */
function json_response($status, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode(array_merge([
        'status' => $status,
        'message' => $message
    ], $data));
    exit;
}

/**
 * Client IP address retriever
 */
function get_client_ip() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '127.0.0.1';
}

/**
 * Check if app installer is complete
 */
function is_installed() {
    return file_exists(__DIR__ . '/../config/installed.lock');
}

/**
 * Image Compression and Resizing Helper using GD Library
 */
function compressAndResizeImage($sourcePath, $targetPath, $maxWidth = 800, $maxHeight = 800, $quality = 85) {
    if (!extension_loaded('gd')) {
        return copy($sourcePath, $targetPath);
    }

    $imageInfo = @getimagesize($sourcePath);
    if (!$imageInfo) {
        return copy($sourcePath, $targetPath);
    }

    $width = $imageInfo[0];
    $height = $imageInfo[1];
    $mime = $imageInfo['mime'];

    switch ($mime) {
        case 'image/jpeg':
            $srcImage = @imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $srcImage = @imagecreatefrompng($sourcePath);
            break;
        case 'image/webp':
            $srcImage = @imagecreatefromwebp($sourcePath);
            break;
        default:
            return copy($sourcePath, $targetPath);
    }

    if (!$srcImage) {
        return copy($sourcePath, $targetPath);
    }

    // Calculate dimensions maintaining aspect ratio
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    if ($ratio < 1) {
        $newWidth = (int) round($width * $ratio);
        $newHeight = (int) round($height * $ratio);
    } else {
        $newWidth = $width;
        $newHeight = $height;
    }

    $dstImage = imagecreatetruecolor($newWidth, $newHeight);

    if ($mime === 'image/png' || $mime === 'image/webp') {
        imagealphablending($dstImage, false);
        imagesavealpha($dstImage, true);
    }

    imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    // Save as WebP if supported, otherwise JPEG
    $success = false;
    if (function_exists('imagewebp')) {
        $success = imagewebp($dstImage, $targetPath, $quality);
    } else {
        $success = imagejpeg($dstImage, $targetPath, $quality);
    }

    imagedestroy($srcImage);
    imagedestroy($dstImage);

    return $success;
}
