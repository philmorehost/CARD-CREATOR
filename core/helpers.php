<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Get system database connection if installed
 */
function get_db_connection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $configFile = __DIR__ . '/../config/database.php';
    if (!file_exists($configFile)) {
        return null;
    }

    $config = require $configFile;

    try {
        if (($config['driver'] ?? 'mysql') === 'sqlite') {
            $dbPath = $config['db_path'] ?? (__DIR__ . '/../card_creator.sqlite');
            $pdo = new PDO("sqlite:" . $dbPath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return $pdo;
        }

        $dsn = "mysql:host={$config['host']};port=" . ($config['port'] ?? 3306) . ";dbname={$config['db_name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (\PDOException $e) {
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
