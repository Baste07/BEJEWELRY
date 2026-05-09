UPDATE orders SET status = 'processing' WHERE status = 'pending';

ALTER TABLE users
  MODIFY role ENUM('customer','super_admin','manager','inventory','courier') NOT NULL DEFAULT 'customer';

ALTER TABLE orders
  MODIFY status ENUM('processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'processing',
  ADD COLUMN IF NOT EXISTS courier_user_id INT NULL DEFAULT NULL AFTER notes,
  ADD COLUMN IF NOT EXISTS courier_name VARCHAR(160) NULL DEFAULT NULL AFTER courier_user_id,
  ADD COLUMN IF NOT EXISTS courier_assigned_at DATETIME NULL DEFAULT NULL AFTER courier_name;

ALTER TABLE orders
  ADD INDEX IF NOT EXISTS idx_orders_courier_user_id (courier_user_id),
  ADD CONSTRAINT fk_orders_courier_user FOREIGN KEY (courier_user_id) REFERENCES users(id) ON DELETE SET NULL;