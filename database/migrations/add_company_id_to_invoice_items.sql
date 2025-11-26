ALTER TABLE invoice_items
ADD COLUMN company_id INT NOT NULL AFTER invoice_id;
