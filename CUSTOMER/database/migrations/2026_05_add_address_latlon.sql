-- Migration: Add latitude and longitude to addresses
ALTER TABLE addresses
  ADD COLUMN latitude DOUBLE NULL AFTER zip,
  ADD COLUMN longitude DOUBLE NULL AFTER latitude;

-- Optional: index for faster spatial queries (non-spatial)
CREATE INDEX IF NOT EXISTS idx_addresses_lat_lon ON addresses (latitude, longitude);
