CREATE TABLE IF NOT EXISTS password_resets (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email      VARCHAR(255) NOT NULL,
  token_hash VARCHAR(255) NOT NULL UNIQUE,
  expires_at DATETIME    NOT NULL,
  used_at    DATETIME    DEFAULT NULL,
  created_at DATETIME    DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_pr_email (email),
  INDEX idx_pr_token (token_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
