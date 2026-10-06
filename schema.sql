-- Database Schema for CARD-CREATOR

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'staff',
    is_suspended INTEGER DEFAULT 0,
    otp_code VARCHAR(10),
    otp_expires_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS card_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    cardholder_name VARCHAR(150),
    card_type VARCHAR(50) NOT NULL,
    template_title VARCHAR(150),
    data_json TEXT,
    preview_front MEDIUMTEXT,
    preview_back MEDIUMTEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT
);

CREATE TABLE IF NOT EXISTS card_templates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title VARCHAR(100) NOT NULL,
    type VARCHAR(50) NOT NULL DEFAULT 'id_card',
    orientation VARCHAR(20) NOT NULL DEFAULT 'portrait',
    width_px INTEGER DEFAULT 600,
    height_px INTEGER DEFAULT 960,
    front_bg_image VARCHAR(255),
    back_bg_image VARCHAR(255),
    fields_json TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS login_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(100),
    ip_address VARCHAR(45) NOT NULL,
    status VARCHAR(20) NOT NULL,
    reason VARCHAR(255),
    user_agent VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS ip_blocks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ip_address VARCHAR(45) NOT NULL UNIQUE,
    blocked_until DATETIME NOT NULL,
    reason VARCHAR(255),
    is_permanent INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS ip_whitelists (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ip_address VARCHAR(45) NOT NULL UNIQUE,
    label VARCHAR(100) DEFAULT 'Trusted IP',
    successful_sessions_count INTEGER DEFAULT 1,
    is_auto INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS otps (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    otp_code VARCHAR(10) NOT NULL,
    type VARCHAR(50) NOT NULL,
    expires_at DATETIME NOT NULL,
    used INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
