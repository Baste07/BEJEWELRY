-- Add tracking_number column to orders table for courier delivery tracking
ALTER TABLE orders
ADD COLUMN tracking_number VARCHAR(120) DEFAULT NULL AFTER courier_assigned_at;

-- Create index for faster lookups
CREATE INDEX idx_orders_tracking_number ON orders(tracking_number);
