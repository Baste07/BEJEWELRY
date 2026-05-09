ALTER TABLE `users`
  ADD COLUMN `archived_at` datetime DEFAULT NULL AFTER `lock_reason`,
  ADD COLUMN `archived_by` int(11) DEFAULT NULL AFTER `archived_at`,
  ADD COLUMN `archive_reason` varchar(255) DEFAULT NULL AFTER `archived_by`;
