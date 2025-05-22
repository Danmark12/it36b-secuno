CREATE TABLE pending_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    user_type VARCHAR(50),
    password_hash VARCHAR(255),
    token VARCHAR(64) NOT NULL,
    token_expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    user_type VARCHAR(50),
    password_hash VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE otp_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    otp_code VARCHAR(10) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Assuming your 'users' table already exists
ALTER TABLE users
ADD COLUMN failed_login_attempts INT DEFAULT 0;

CREATE TABLE IF NOT EXISTS account_lockouts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    locked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    unlock_at DATETIME NOT NULL,
    reason VARCHAR(255) DEFAULT 'Too many failed login attempts',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE (user_id) -- Ensures only one active lockout record per user
);