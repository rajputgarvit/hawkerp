-- Add tracking columns to products table
ALTER TABLE products
ADD COLUMN has_serial_number BOOLEAN DEFAULT 0 AFTER is_active,
ADD COLUMN has_warranty BOOLEAN DEFAULT 0 AFTER has_serial_number,
ADD COLUMN has_expiry_date BOOLEAN DEFAULT 0 AFTER has_warranty;

-- Add tracking columns to invoice_items table
ALTER TABLE invoice_items
ADD COLUMN serial_number VARCHAR(255) NULL AFTER line_total,
ADD COLUMN warranty_period VARCHAR(100) NULL AFTER serial_number,
ADD COLUMN expiry_date DATE NULL AFTER warranty_period;
