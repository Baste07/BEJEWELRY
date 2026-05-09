-- Bejewelry: widen/convert columns for encrypted sensitive data storage
-- Run this once before enabling encryption in production.

ALTER TABLE users
  MODIFY COLUMN phone VARCHAR(512) NULL,
  MODIFY COLUMN city VARCHAR(512) NULL,
  MODIFY COLUMN birthday VARCHAR(255) NULL;

ALTER TABLE addresses
  MODIFY COLUMN street VARCHAR(1024) NOT NULL,
  MODIFY COLUMN city VARCHAR(512) NOT NULL,
  MODIFY COLUMN province VARCHAR(512) NULL,
  MODIFY COLUMN zip VARCHAR(512) NULL,
  MODIFY COLUMN phone VARCHAR(512) NULL;

ALTER TABLE orders
  MODIFY COLUMN ship_street VARCHAR(1024) NULL,
  MODIFY COLUMN ship_city VARCHAR(512) NULL,
  MODIFY COLUMN ship_province VARCHAR(512) NULL,
  MODIFY COLUMN ship_zip VARCHAR(512) NULL,
  MODIFY COLUMN ship_phone VARCHAR(512) NULL;

ALTER TABLE paymongo_checkout_pending
  MODIFY COLUMN post_json LONGTEXT NOT NULL;
