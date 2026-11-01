ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verified TINYINT(1) NOT NULL DEFAULT 1;

CREATE TABLE IF NOT EXISTS settings (
    setting_key   VARCHAR(50) PRIMARY KEY,
    setting_value VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('voting_enabled', '1'), ('results_mode', 'live');

CREATE TABLE IF NOT EXISTS positions (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    position_key VARCHAR(40) NOT NULL UNIQUE,
    label        VARCHAR(80) NOT NULL,
    sort_order   INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS candidates (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    position_key VARCHAR(40) NOT NULL,
    name         VARCHAR(100) NOT NULL,
    year_section VARCHAR(60) NOT NULL DEFAULT '',
    platform     TEXT NULL,
    photo        VARCHAR(60) NULL,
    sort_order   INT NOT NULL DEFAULT 0,
    UNIQUE KEY uq_position_name (position_key, name),
    CONSTRAINT fk_cand_pos FOREIGN KEY (position_key) REFERENCES positions (position_key) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Your current ballot, so nothing changes until you edit it in the admin pages
INSERT IGNORE INTO positions (position_key, label, sort_order) VALUES
    ('president', 'President', 1), ('vice_president', 'Vice President', 2), ('secretary', 'Secretary', 3);
INSERT IGNORE INTO candidates (position_key, name, sort_order) VALUES
    ('president', 'Jamyl Baloro', 1), ('president', 'Aaron Balba', 2),
    ('vice_president', 'Mark Patlonag', 3), ('vice_president', 'Shin Caibigan', 4),
    ('secretary', 'Tristan Juts', 5), ('secretary', 'Russel Salas', 6);

CREATE TABLE IF NOT EXISTS announcements (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    title      VARCHAR(150) NOT NULL,
    body       TEXT NOT NULL,
    is_active  TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS activity_log (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    actor_id   VARCHAR(20) NOT NULL,
    actor_name VARCHAR(100) NOT NULL DEFAULT '',
    action     VARCHAR(60) NOT NULL,
    details    VARCHAR(500) NOT NULL DEFAULT '',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS voter_roll (
    student_id VARCHAR(20) PRIMARY KEY,
    fullname   VARCHAR(100) NOT NULL DEFAULT '',
    section    VARCHAR(60) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS email_verifications (
    token_hash CHAR(64) PRIMARY KEY,
    user_id    INT NOT NULL,
    expires_at DATETIME NOT NULL,
    KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS remember_tokens (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    selector       CHAR(18) NOT NULL UNIQUE,
    validator_hash CHAR(64) NOT NULL,
    user_id        INT NOT NULL,
    expires_at     DATETIME NOT NULL,
    KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
