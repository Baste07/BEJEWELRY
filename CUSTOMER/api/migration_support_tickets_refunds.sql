-- Run this on an existing bejewelry database to extend support tickets with photos, resolution tracking, and refund logs.
USE bejewelry;

ALTER TABLE support_tickets
  ADD COLUMN photo_path VARCHAR(255) DEFAULT NULL AFTER description,
  ADD COLUMN resolution ENUM('reorder','redeliver','refund') DEFAULT NULL AFTER photo_path,
  ADD COLUMN rejection_reason TEXT DEFAULT NULL AFTER admin_note,
  ADD COLUMN reviewed_by INT DEFAULT NULL AFTER rejection_reason,
  ADD COLUMN reviewed_at DATETIME DEFAULT NULL AFTER reviewed_by;

CREATE TABLE IF NOT EXISTS refund_logs (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  ticket_id         INT NOT NULL,
  user_id           INT NOT NULL,
  customer_name     VARCHAR(160) NOT NULL,
  order_id          VARCHAR(20) NOT NULL,
  refund_amount     DECIMAL(10,2) NOT NULL,
  ticket_category   VARCHAR(32) NOT NULL,
  approved_by       INT NOT NULL,
  approved_by_name  VARCHAR(160) NOT NULL,
  created_at        DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_refund_ticket (ticket_id),
  KEY idx_refund_created (created_at),
  FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE CASCADE
);