ALTER TABLE `users`
  ADD COLUMN `failed_login_attempts` int(11) NOT NULL DEFAULT 0 AFTER `totp_secret`,
  ADD COLUMN `locked_at` datetime DEFAULT NULL AFTER `failed_login_attempts`,
  ADD COLUMN `locked_by` int(11) DEFAULT NULL AFTER `locked_at`,
  ADD COLUMN `lock_reason` varchar(255) DEFAULT NULL AFTER `locked_by`;

UPDATE `users`
SET `failed_login_attempts` = 0,
    `locked_at` = NULL,
    `locked_by` = NULL,
    `lock_reason` = NULL;
