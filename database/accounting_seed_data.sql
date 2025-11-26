-- Seed data for Accounting Module
-- Run this SQL to populate initial data

-- 1. Account Types (Foundation)
INSERT INTO account_types (name, category) VALUES
('Current Assets', 'Asset'),
('Fixed Assets', 'Asset'),
('Current Liabilities', 'Liability'),
('Long-term Liabilities', 'Liability'),
('Owner Equity', 'Equity'),
('Sales Revenue', 'Income'),
('Service Revenue', 'Income'),
('Operating Expenses', 'Expense'),
('Administrative Expenses', 'Expense');

-- 2. Sample Chart of Accounts
INSERT INTO chart_of_accounts (account_code, account_name, account_type_id, parent_account_id, description, is_active) VALUES
-- Assets (1xxx)
('1000', 'Cash and Bank', 1, NULL, 'Cash and bank accounts', 1),
('1010', 'Cash in Hand', 1, NULL, 'Physical cash', 1),
('1020', 'Cash at Bank', 1, NULL, 'Bank account balance', 1),
('1100', 'Accounts Receivable', 1, NULL, 'Money owed by customers', 1),
('1200', 'Inventory', 1, NULL, 'Stock inventory', 1),
('1500', 'Furniture & Fixtures', 2, NULL, 'Office furniture', 1),
('1600', 'Computer Equipment', 2, NULL, 'Computers and IT equipment', 1),

-- Liabilities (2xxx)
('2000', 'Accounts Payable', 3, NULL, 'Money owed to suppliers', 1),
('2100', 'GST Payable', 3, NULL, 'GST collected from customers', 1),
('2200', 'TDS Payable', 3, NULL, 'TDS deducted', 1),
('2500', 'Bank Loan', 4, NULL, 'Long-term bank loans', 1),

-- Equity (3xxx)
('3000', 'Owner Capital', 5, NULL, 'Owner investment', 1),
('3100', 'Retained Earnings', 5, NULL, 'Accumulated profits', 1),

-- Income (4xxx)
('4000', 'Product Sales', 6, NULL, 'Revenue from product sales', 1),
('4100', 'Service Income', 7, NULL, 'Revenue from services', 1),
('4200', 'Other Income', 7, NULL, 'Miscellaneous income', 1),

-- Expenses (5xxx)
('5000', 'Rent Expense', 8, NULL, 'Office rent', 1),
('5100', 'Salary Expense', 8, NULL, 'Employee salaries', 1),
('5200', 'Utilities Expense', 8, NULL, 'Electricity, water, internet', 1),
('5300', 'Office Supplies', 9, NULL, 'Stationery and supplies', 1),
('5400', 'Depreciation Expense', 9, NULL, 'Asset depreciation', 1);

-- 3. Create a Fiscal Year
INSERT INTO fiscal_years (year_name, start_date, end_date, is_closed) VALUES
('FY 2024-25', '2024-04-01', '2025-03-31', 0);

-- 4. Sample Journal Entries
-- Get the fiscal year ID (assuming it's 1, adjust if needed)
SET @fiscal_year_id = (SELECT id FROM fiscal_years WHERE year_name = 'FY 2024-25' LIMIT 1);

-- Entry 1: Initial Capital Investment
INSERT INTO journal_entries (entry_number, entry_date, fiscal_year_id, description, total_debit, total_credit, status, created_by, posted_at) VALUES
('JE-20240401-001', '2024-04-01', @fiscal_year_id, 'Initial capital investment by owner', 500000.00, 500000.00, 'Posted', 1, NOW());

SET @je1_id = LAST_INSERT_ID();

INSERT INTO journal_entry_lines (journal_entry_id, account_id, debit_amount, credit_amount, description) VALUES
(@je1_id, (SELECT id FROM chart_of_accounts WHERE account_code = '1020'), 500000.00, 0.00, 'Cash deposited in bank'),
(@je1_id, (SELECT id FROM chart_of_accounts WHERE account_code = '3000'), 0.00, 500000.00, 'Owner capital contribution');

-- Entry 2: Purchase of Furniture
INSERT INTO journal_entries (entry_number, entry_date, fiscal_year_id, description, total_debit, total_credit, status, created_by, posted_at) VALUES
('JE-20240405-001', '2024-04-05', @fiscal_year_id, 'Purchase of office furniture', 50000.00, 50000.00, 'Posted', 1, NOW());

SET @je2_id = LAST_INSERT_ID();

INSERT INTO journal_entry_lines (journal_entry_id, account_id, debit_amount, credit_amount, description) VALUES
(@je2_id, (SELECT id FROM chart_of_accounts WHERE account_code = '1500'), 50000.00, 0.00, 'Office furniture purchased'),
(@je2_id, (SELECT id FROM chart_of_accounts WHERE account_code = '1020'), 0.00, 50000.00, 'Payment from bank');

-- Entry 3: Sales Revenue
INSERT INTO journal_entries (entry_number, entry_date, fiscal_year_id, description, total_debit, total_credit, status, created_by, posted_at) VALUES
('JE-20240410-001', '2024-04-10', @fiscal_year_id, 'Sales revenue for the month', 150000.00, 150000.00, 'Posted', 1, NOW());

SET @je3_id = LAST_INSERT_ID();

INSERT INTO journal_entry_lines (journal_entry_id, account_id, debit_amount, credit_amount, description) VALUES
(@je3_id, (SELECT id FROM chart_of_accounts WHERE account_code = '1020'), 150000.00, 0.00, 'Cash received from sales'),
(@je3_id, (SELECT id FROM chart_of_accounts WHERE account_code = '4000'), 0.00, 150000.00, 'Product sales revenue');

-- Entry 4: Rent Expense
INSERT INTO journal_entries (entry_number, entry_date, fiscal_year_id, description, total_debit, total_credit, status, created_by, posted_at) VALUES
('JE-20240415-001', '2024-04-15', @fiscal_year_id, 'Office rent payment', 20000.00, 20000.00, 'Posted', 1, NOW());

SET @je4_id = LAST_INSERT_ID();

INSERT INTO journal_entry_lines (journal_entry_id, account_id, debit_amount, credit_amount, description) VALUES
(@je4_id, (SELECT id FROM chart_of_accounts WHERE account_code = '5000'), 20000.00, 0.00, 'Monthly rent expense'),
(@je4_id, (SELECT id FROM chart_of_accounts WHERE account_code = '1020'), 0.00, 20000.00, 'Rent paid from bank');

-- Entry 5: Salary Expense
INSERT INTO journal_entries (entry_number, entry_date, fiscal_year_id, description, total_debit, total_credit, status, created_by, posted_at) VALUES
('JE-20240420-001', '2024-04-20', @fiscal_year_id, 'Employee salary payment', 80000.00, 80000.00, 'Posted', 1, NOW());

SET @je5_id = LAST_INSERT_ID();

INSERT INTO journal_entry_lines (journal_entry_id, account_id, debit_amount, credit_amount, description) VALUES
(@je5_id, (SELECT id FROM chart_of_accounts WHERE account_code = '5100'), 80000.00, 0.00, 'Monthly salaries'),
(@je5_id, (SELECT id FROM chart_of_accounts WHERE account_code = '1020'), 0.00, 80000.00, 'Salaries paid from bank');

-- Entry 6: Service Income
INSERT INTO journal_entries (entry_number, entry_date, fiscal_year_id, description, total_debit, total_credit, status, created_by, posted_at) VALUES
('JE-20240425-001', '2024-04-25', @fiscal_year_id, 'Service income received', 75000.00, 75000.00, 'Posted', 1, NOW());

SET @je6_id = LAST_INSERT_ID();

INSERT INTO journal_entry_lines (journal_entry_id, account_id, debit_amount, credit_amount, description) VALUES
(@je6_id, (SELECT id FROM chart_of_accounts WHERE account_code = '1020'), 75000.00, 0.00, 'Cash received for services'),
(@je6_id, (SELECT id FROM chart_of_accounts WHERE account_code = '4100'), 0.00, 75000.00, 'Service revenue');

-- Entry 7: Utilities Expense
INSERT INTO journal_entries (entry_number, entry_date, fiscal_year_id, description, total_debit, total_credit, status, created_by, posted_at) VALUES
('JE-20240430-001', '2024-04-30', @fiscal_year_id, 'Utilities payment', 5000.00, 5000.00, 'Posted', 1, NOW());

SET @je7_id = LAST_INSERT_ID();

INSERT INTO journal_entry_lines (journal_entry_id, account_id, debit_amount, credit_amount, description) VALUES
(@je7_id, (SELECT id FROM chart_of_accounts WHERE account_code = '5200'), 5000.00, 0.00, 'Electricity and internet bills'),
(@je7_id, (SELECT id FROM chart_of_accounts WHERE account_code = '1020'), 0.00, 5000.00, 'Utilities paid from bank');

-- Verification Queries
-- Run these to verify the data was inserted correctly

-- Check account types
SELECT * FROM account_types;

-- Check chart of accounts
SELECT coa.account_code, coa.account_name, at.category 
FROM chart_of_accounts coa 
JOIN account_types at ON coa.account_type_id = at.id 
ORDER BY coa.account_code;

-- Check fiscal years
SELECT * FROM fiscal_years;

-- Check journal entries
SELECT je.entry_number, je.entry_date, je.description, je.total_debit, je.total_credit, je.status
FROM journal_entries je
ORDER BY je.entry_date;

-- Check journal entry lines
SELECT je.entry_number, coa.account_code, coa.account_name, 
       jel.debit_amount, jel.credit_amount, jel.description
FROM journal_entry_lines jel
JOIN journal_entries je ON jel.journal_entry_id = je.id
JOIN chart_of_accounts coa ON jel.account_id = coa.id
ORDER BY je.entry_date, jel.id;

-- Expected Results After Running This Script:
-- 
-- Trial Balance:
-- Total Debits = Total Credits = ₹880,000
--
-- Profit & Loss:
-- Income: ₹225,000 (Product Sales + Service Income)
-- Expenses: ₹105,000 (Rent + Salary + Utilities)
-- Net Profit: ₹120,000
--
-- Balance Sheet:
-- Assets: ₹570,000 (Cash: 520,000 + Furniture: 50,000)
-- Liabilities: ₹0
-- Equity: ₹570,000 (Capital: 500,000 + Retained Earnings: 120,000 - but this needs closing entry)
