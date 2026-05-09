-- ═══════════════════════════════════════════════════════════
--  BEJEWELRY — Complete MySQL Schema (fresh install)
--  Single source of truth for admin + customer. No API required for admin.
--
--  HOW TO USE:
--    Fresh install: Run this entire file in MySQL (e.g. phpMyAdmin → Import).
--    To wipe and start over, uncomment the next line and run again:
-- ═══════════════════════════════════════════════════════════
-- DROP DATABASE IF EXISTS bejewelry;

CREATE DATABASE IF NOT EXISTS bejewelry
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE bejewelry;

-- ── Users ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  first_name    VARCHAR(80)  NOT NULL,
  last_name     VARCHAR(80)  NOT NULL,
  username      VARCHAR(80)  UNIQUE,
  email         VARCHAR(191) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  phone         VARCHAR(30),
  gender        ENUM('Male','Female','Other','Prefer not to say'),
  birthday      DATE,
  city          VARCHAR(100),
  role          ENUM('customer','super_admin','manager','inventory','courier') NOT NULL DEFAULT 'customer',
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  email_verified_at DATETIME NULL DEFAULT NULL,
  activation_token  VARCHAR(64) NULL DEFAULT NULL,
  activation_expires DATETIME NULL DEFAULT NULL,
  totp_secret       VARCHAR(64) NULL DEFAULT NULL
);

-- ── Email Preferences ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS email_prefs (
  user_id        INT PRIMARY KEY,
  order_updates  TINYINT(1) DEFAULT 1,
  launches       TINYINT(1) DEFAULT 1,
  promos         TINYINT(1) DEFAULT 0,
  wishlist       TINYINT(1) DEFAULT 1,
  updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── Addresses ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS addresses (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NOT NULL,
  label      VARCHAR(50)  DEFAULT 'Home',
  name       VARCHAR(160) NOT NULL,
  street     VARCHAR(255) NOT NULL,
  city       VARCHAR(100) NOT NULL,
  province   VARCHAR(100),
  zip        VARCHAR(20),
  phone      VARCHAR(30),
  is_default TINYINT(1) DEFAULT 0,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── Categories ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS categories (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(80) NOT NULL UNIQUE,
  slug       VARCHAR(80) NOT NULL UNIQUE,
  sort_order INT DEFAULT 0
);

INSERT IGNORE INTO categories (name, slug, sort_order) VALUES
  ('Rings',     'rings',     1),
  ('Necklaces', 'necklaces', 2),
  ('Bracelets', 'bracelets', 3),
  ('Earrings',  'earrings',  4);

-- ── Products ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS products (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  name         VARCHAR(160) NOT NULL,
  description  TEXT,
  category_id  INT,
  price        DECIMAL(10,2) NOT NULL,
  orig_price   DECIMAL(10,2) DEFAULT NULL,
  image        VARCHAR(255)  DEFAULT NULL,
  badge        ENUM('','new','best','sale') DEFAULT '',
  stars        TINYINT DEFAULT 5,
  reviews      INT DEFAULT 0,
  size_default VARCHAR(50) DEFAULT 'One Size',
  material     VARCHAR(100),
  stock        INT DEFAULT 100,
  is_active    TINYINT(1) DEFAULT 1,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

INSERT IGNORE INTO products (id, name, description, category_id, price, orig_price, image, badge, stars, reviews, size_default, material) VALUES
(1, 'Solara Ring',        'A radiant ring inspired by the sun, crafted in 14K gold.',               1, 580.00,    NULL, NULL, 'best', 5, 128, 'Ring',     '14K Gold'),
(2, 'Luna Earrings',      'Delicate stud earrings with a luminous sterling silver finish.',          4, 500.00,    NULL, NULL, 'best', 5,  94, 'One Size', 'Sterling Silver'),
(3, 'Celestial Necklace', 'An 18-inch gold necklace adorned with star and moon charms.',             2, 970.00,    NULL, NULL, 'best', 5, 203, '18"',      '14K Gold'),
(4, 'Dia Charm Bracelet', 'A charm bracelet featuring dazzling crystal accents.',                   3, 700.00,    NULL, NULL, 'best', 4,  67, 'One Size', 'Sterling Silver'),
(5, 'Infinity Band',      'A sleek infinity-symbol band in polished 14K gold.',                     1, 750.00,    NULL, NULL, '',     5,  45, 'Ring',     '14K Gold'),
(6, 'Star Pendant',       'A sparkling star-shaped pendant on a 16-inch rose gold chain.',          2, 820.00, 1020.00, NULL, 'sale', 4,  88, '16"',      'Rose Gold'),
(7, 'Diamond Hoops',      'Classic hoop earrings with a diamond-cut finish in 14K gold.',           4, 650.00,    NULL, NULL, '',     5, 112, 'One Size', '14K Gold'),
(8, 'Aurora Bangle',      'A wide rose-gold bangle with an iridescent enamel finish.',              3, 890.00,    NULL, NULL, 'new',  4,  56, 'One Size', 'Rose Gold');

-- ── Cart Items ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS cart_items (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NOT NULL,
  product_id INT NOT NULL,
  size       VARCHAR(50) DEFAULT 'One Size',
  qty        INT NOT NULL DEFAULT 1,
  added_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_cart (user_id, product_id, size),
  FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- ── PayMongo pending checkout (recover order if PHP session drops but user returns with pm_state / cs id)
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

-- ── Wishlist ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS wishlist (
  user_id    INT NOT NULL,
  product_id INT NOT NULL,
  added_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, product_id),
  FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- ── Orders ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS orders (
  id             VARCHAR(20) PRIMARY KEY,
  user_id        INT NOT NULL,
  status         ENUM('processing','shipped','delivered','cancelled') DEFAULT 'processing',
  subtotal       DECIMAL(10,2) NOT NULL,
  shipping_fee   DECIMAL(10,2) DEFAULT 150.00,
  total          DECIMAL(10,2) NOT NULL,
  promotion_id   INT DEFAULT NULL,
  ship_name      VARCHAR(160),
  ship_street    VARCHAR(255),
  ship_city      VARCHAR(100),
  ship_province  VARCHAR(100),
  ship_zip       VARCHAR(20),
  ship_phone     VARCHAR(30),
  payment_method VARCHAR(50) DEFAULT 'ewallet',
  notes          TEXT,
  courier_user_id INT DEFAULT NULL,
  courier_name   VARCHAR(160) DEFAULT NULL,
  courier_assigned_at DATETIME NULL DEFAULT NULL,
  tracking_number VARCHAR(120) DEFAULT NULL,
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (courier_user_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (promotion_id) REFERENCES promotions(id) ON DELETE SET NULL,
  KEY idx_orders_promotion_id (promotion_id),
  KEY idx_orders_courier_user_id (courier_user_id)
);

-- ── Delivery proofs from third-party carriers ─────────────
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

-- ── Order email logs (customer notification history) ──────
CREATE TABLE IF NOT EXISTS order_email_logs (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  user_id         INT NOT NULL,
  order_id        VARCHAR(20) NOT NULL,
  email_type      VARCHAR(50) NOT NULL,
  recipient_email VARCHAR(191) NOT NULL,
  subject         VARCHAR(255) NOT NULL,
  status          ENUM('sent','failed') NOT NULL DEFAULT 'sent',
  error_message   TEXT DEFAULT NULL,
  sent_at         DATETIME DEFAULT NULL,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_order_email_log (user_id, order_id, email_type),
  KEY idx_order_email_user (user_id, created_at),
  KEY idx_order_email_order (order_id, created_at),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- ── Customer notifications (in-app bell feed) ─────────────
CREATE TABLE IF NOT EXISTS customer_notifications (
  id         BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NOT NULL,
  type       VARCHAR(40) NOT NULL,
  event_key  VARCHAR(191) NOT NULL,
  title      VARCHAR(160) NOT NULL,
  message    VARCHAR(255) NOT NULL,
  link_url   VARCHAR(255) DEFAULT NULL,
  is_read    TINYINT(1) NOT NULL DEFAULT 0,
  read_at    DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_customer_notifications_event (user_id, event_key),
  KEY idx_customer_notifications_feed (user_id, is_read, created_at),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── Order Items ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS order_items (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  order_id   VARCHAR(20) NOT NULL,
  product_id INT,
  name       VARCHAR(160) NOT NULL,
  image      VARCHAR(255),
  cat        VARCHAR(80),
  size       VARCHAR(50),
  price      DECIMAL(10,2) NOT NULL,
  qty        INT NOT NULL,
  FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

-- ── Support Tickets ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS support_tickets (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT NOT NULL,
  order_id    VARCHAR(20) NOT NULL,
  type        ENUM('wrong_item','damaged','not_delivered','delayed','refund','missing_item','other') NOT NULL,
  category    VARCHAR(32) DEFAULT 'product',
  scope       ENUM('manager','super_admin') NOT NULL DEFAULT 'manager',
  description TEXT NOT NULL,
  photo_path  VARCHAR(255) DEFAULT NULL,
  resolution  ENUM('reorder','redeliver','refund') DEFAULT NULL,
  status      ENUM('open','resolved','closed') NOT NULL DEFAULT 'open',
  admin_note  TEXT DEFAULT NULL,
  rejection_reason TEXT DEFAULT NULL,
  reviewed_by INT DEFAULT NULL,
  reviewed_at DATETIME DEFAULT NULL,
  created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── Ticket refunds (refund approvals log) ─────────────────
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

-- ── Account lock log (immutable history of locked accounts) ──
CREATE TABLE IF NOT EXISTS account_lock_logs (
  id                       INT AUTO_INCREMENT PRIMARY KEY,
  username                 VARCHAR(191) NOT NULL,
  failed_login_attempts    INT NOT NULL,
  locked_at                TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_username (username),
  KEY idx_locked_at (locked_at)
);

-- Seed: sample promotion
INSERT IGNORE INTO promotions (code, name, type, value, min_order, max_uses, start_at, end_at, is_active) VALUES
('WELCOME10', 'Welcome 10% off', 'percent', 10.00, 500, 1000, NULL, NULL, 1),
('FREESHIP', 'Free shipping over ₱2k', 'fixed', 150.00, 2000, NULL, NULL, NULL, 1);

-- ── Default Admin (password: admin123 — change after first login) ──
INSERT IGNORE INTO users (first_name, last_name, email, password_hash, role)
VALUES ('Admin', 'Bejewelry', 'admin@bejewelry.ph',
        '$2y$12$wKjJFLPm7QXTxvkh3Ry.7.tAQZj2fkNuALqBLuBnFBnJpYhgOoSRy', 'admin');
