ALTER TABLE quotation_items ADD COLUMN company_id INT NOT NULL DEFAULT 1 AFTER quotation_id;
CREATE INDEX idx_quotation_items_company ON quotation_items(company_id);
