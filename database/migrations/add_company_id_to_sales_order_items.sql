ALTER TABLE sales_order_items ADD COLUMN company_id INT NOT NULL DEFAULT 1 AFTER order_id;
CREATE INDEX idx_sales_order_items_company ON sales_order_items(company_id);
