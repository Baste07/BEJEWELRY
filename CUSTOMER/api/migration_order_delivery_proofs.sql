-- Delivery proof records for third-party carrier confirmations
CREATE TABLE IF NOT EXISTS order_delivery_proofs (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  order_id          VARCHAR(20) NOT NULL,
  carrier_name      VARCHAR(120) NOT NULL,
  carrier_reference VARCHAR(120) DEFAULT NULL,
  proof_photo       VARCHAR(255) NOT NULL,
  note              TEXT DEFAULT NULL,
  delivered_at      DATETIME NULL DEFAULT NULL,
  created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  KEY idx_order_delivery_proofs_order (order_id),
  KEY idx_order_delivery_proofs_created (created_at)
);