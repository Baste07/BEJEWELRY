-- Run this on an existing bejewelry database to add promotion tracking to orders.
-- If you are doing a fresh install, use schema.sql instead.

USE bejewelry;

ALTER TABLE orders
  ADD COLUMN promotion_id INT DEFAULT NULL AFTER total,
  ADD KEY idx_orders_promotion_id (promotion_id),
  ADD CONSTRAINT fk_orders_promotion_id FOREIGN KEY (promotion_id) REFERENCES promotions(id) ON DELETE SET NULL;
