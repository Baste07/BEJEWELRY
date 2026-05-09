-- Order Manager: flag fields (run once on existing DB)
-- MySQL / MariaDB

ALTER TABLE orders
  ADD COLUMN is_flagged TINYINT(1) NOT NULL DEFAULT 0 AFTER notes,
  ADD COLUMN flag_reason VARCHAR(255) NULL DEFAULT NULL AFTER is_flagged;
