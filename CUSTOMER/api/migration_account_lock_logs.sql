-- Immutable log for account lock events. Run once on an existing bejewelry database.
USE bejewelry;

CREATE TABLE IF NOT EXISTS account_lock_logs (
  id                    INT AUTO_INCREMENT PRIMARY KEY,
  username              VARCHAR(191) NOT NULL,
  failed_login_attempts  INT NOT NULL,
  locked_at             TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_username (username),
  KEY idx_locked_at (locked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;