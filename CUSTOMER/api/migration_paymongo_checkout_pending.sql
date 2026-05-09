-- Run once on existing databases (phpMyAdmin → SQL), if you already installed bejewelry before this table existed.

USE bejewelry;

CREATE TABLE IF NOT EXISTS paymongo_checkout_pending (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  checkout_session_id VARCHAR(128) NOT NULL,
  pm_state VARCHAR(64) NOT NULL,
  expected_total_cents INT NOT NULL,
  post_json TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_pm_state (pm_state),
  UNIQUE KEY uq_checkout_session (checkout_session_id),
  KEY idx_user (user_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
