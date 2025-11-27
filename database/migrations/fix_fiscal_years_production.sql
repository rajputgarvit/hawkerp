-- Migration to add company_id column to fiscal_years table
-- This migration is safe to run multiple times (idempotent)

-- Check if column exists before adding
SET @dbname = DATABASE();
SET @tablename = 'fiscal_years';
SET @columnname = 'company_id';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  'ALTER TABLE fiscal_years ADD COLUMN company_id INT NOT NULL DEFAULT 1 AFTER id'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Create index if it doesn't exist
SET @indexname = 'idx_fiscal_years_company';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (index_name = @indexname)
  ) > 0,
  'SELECT 1',
  'CREATE INDEX idx_fiscal_years_company ON fiscal_years(company_id)'
));
PREPARE createIndexIfNotExists FROM @preparedStatement;
EXECUTE createIndexIfNotExists;
DEALLOCATE PREPARE createIndexIfNotExists;
