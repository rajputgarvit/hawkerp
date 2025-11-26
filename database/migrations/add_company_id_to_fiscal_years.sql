ALTER TABLE fiscal_years ADD COLUMN company_id INT NOT NULL DEFAULT 1 AFTER id;
CREATE INDEX idx_fiscal_years_company ON fiscal_years(company_id);
