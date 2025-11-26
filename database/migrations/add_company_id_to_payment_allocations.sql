ALTER TABLE payment_allocations
ADD COLUMN company_id INT NOT NULL AFTER invoice_id;
