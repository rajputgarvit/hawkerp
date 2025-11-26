-- Add payment_status and paid_amount columns to invoices table
-- Run this migration to enable payment status tracking

ALTER TABLE invoices 
ADD COLUMN payment_status VARCHAR(50) DEFAULT 'Unpaid' AFTER status,
ADD COLUMN paid_amount DECIMAL(10,2) DEFAULT 0.00 AFTER payment_status;

-- Update existing invoices to have default payment status
UPDATE invoices 
SET payment_status = 'Unpaid' 
WHERE payment_status IS NULL OR payment_status = '';

-- Optional: Set payment status based on existing data
-- Uncomment if you want to auto-set status for existing invoices

UPDATE invoices 
SET payment_status = CASE 
    WHEN status = 'Draft' THEN 'Draft'
    WHEN paid_amount >= total_amount THEN 'Paid'
    WHEN paid_amount > 0 AND paid_amount < total_amount THEN 'Partially Paid'
    WHEN due_date < CURDATE() THEN 'Overdue'
    ELSE 'Unpaid'
END;
