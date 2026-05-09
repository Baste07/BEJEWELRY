-- Audit log (login / logout / CRUD). Run once in MySQL.

CREATE TABLE IF NOT EXISTS audit_log (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  email VARCHAR(191) NULL,
  action VARCHAR(64) NOT NULL,
  ip VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_user (user_id),
  KEY idx_created (created_at),
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE audit_log MODIFY action VARCHAR(64) NOT NULL;
