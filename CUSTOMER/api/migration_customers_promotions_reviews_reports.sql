-- Run this on an existing bejewelry database to add customers/promotions/reviews/reports tables.
-- If you are doing a fresh install, use schema.sql instead.

USE bejewelry;

-- ── Customer notes (admin notes on customers) ───────────────
CREATE TABLE IF NOT EXISTS customer_notes (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NOT NULL,
  note       TEXT NOT NULL,
  created_by INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── Promotions (discount codes / campaigns) ────────────────
CREATE TABLE IF NOT EXISTS promotions (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  code         VARCHAR(50) NOT NULL UNIQUE,
  name         VARCHAR(160) DEFAULT NULL,
  type         ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
  value        DECIMAL(10,2) NOT NULL,
  min_order    DECIMAL(10,2) DEFAULT 0,
  max_uses     INT DEFAULT NULL,
  used_count   INT NOT NULL DEFAULT 0,
  start_at     DATETIME DEFAULT NULL,
  end_at       DATETIME DEFAULT NULL,
  is_active    TINYINT(1) NOT NULL DEFAULT 1,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ── Promotion redemptions (track usage) ────────────────────
CREATE TABLE IF NOT EXISTS promotion_redemptions (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  promotion_id  INT NOT NULL,
  order_id      VARCHAR(20) NOT NULL,
  user_id       INT NOT NULL,
  discount_amt  DECIMAL(10,2) NOT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (promotion_id) REFERENCES promotions(id) ON DELETE CASCADE,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── Product reviews (customer reviews) ─────────────────────
CREATE TABLE IF NOT EXISTS product_reviews (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  user_id    INT NOT NULL,
  order_id   VARCHAR(20) DEFAULT NULL,
  rating     TINYINT NOT NULL,
  title      VARCHAR(160) DEFAULT NULL,
  body       TEXT DEFAULT NULL,
  status     ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
);

-- ── Report snapshots (saved report data) ───────────────────
CREATE TABLE IF NOT EXISTS report_snapshots (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(160) NOT NULL,
  period     VARCHAR(50) DEFAULT NULL,
  data_json  JSON DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed: sample promotions
INSERT IGNORE INTO promotions (code, name, type, value, min_order, max_uses, start_at, end_at, is_active) VALUES
('WELCOME10', 'Welcome 10% off', 'percent', 10.00, 500, 1000, NULL, NULL, 1),
('FREESHIP', 'Free shipping over ₱2k', 'fixed', 150.00, 2000, NULL, NULL, NULL, 1);
