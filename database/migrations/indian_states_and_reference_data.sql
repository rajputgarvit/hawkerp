-- Indian States and Reference Data Migration
-- Database: tiger_erp

-- Create Indian States Table
CREATE TABLE IF NOT EXISTS indian_states (
    id INT PRIMARY KEY AUTO_INCREMENT,
    state_name VARCHAR(100) NOT NULL UNIQUE,
    state_code VARCHAR(10) NOT NULL UNIQUE,
    gst_code VARCHAR(5) NOT NULL UNIQUE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert all Indian States and Union Territories
INSERT INTO indian_states (state_name, state_code, gst_code) VALUES
('Andaman and Nicobar Islands', 'AN', '35'),
('Andhra Pradesh', 'AP', '37'),
('Arunachal Pradesh', 'AR', '12'),
('Assam', 'AS', '18'),
('Bihar', 'BR', '10'),
('Chandigarh', 'CH', '04'),
('Chhattisgarh', 'CG', '22'),
('Dadra and Nagar Haveli and Daman and Diu', 'DD', '26'),
('Delhi', 'DL', '07'),
('Goa', 'GA', '30'),
('Gujarat', 'GJ', '24'),
('Haryana', 'HR', '06'),
('Himachal Pradesh', 'HP', '02'),
('Jammu and Kashmir', 'JK', '01'),
('Jharkhand', 'JH', '20'),
('Karnataka', 'KA', '29'),
('Kerala', 'KL', '32'),
('Ladakh', 'LA', '38'),
('Lakshadweep', 'LD', '31'),
('Madhya Pradesh', 'MP', '23'),
('Maharashtra', 'MH', '27'),
('Manipur', 'MN', '14'),
('Meghalaya', 'ML', '17'),
('Mizoram', 'MZ', '15'),
('Nagaland', 'NL', '13'),
('Odisha', 'OR', '21'),
('Puducherry', 'PY', '34'),
('Punjab', 'PB', '03'),
('Rajasthan', 'RJ', '08'),
('Sikkim', 'SK', '11'),
('Tamil Nadu', 'TN', '33'),
('Telangana', 'TS', '36'),
('Tripura', 'TR', '16'),
('Uttar Pradesh', 'UP', '09'),
('Uttarakhand', 'UK', '05'),
('West Bengal', 'WB', '19');

-- Create Countries Table
CREATE TABLE IF NOT EXISTS countries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    country_name VARCHAR(100) NOT NULL UNIQUE,
    country_code VARCHAR(5) NOT NULL UNIQUE,
    currency_code VARCHAR(10),
    currency_symbol VARCHAR(10),
    phone_code VARCHAR(10),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert common countries
INSERT INTO countries (country_name, country_code, currency_code, currency_symbol, phone_code) VALUES
('India', 'IN', 'INR', '₹', '+91'),
('United States', 'US', 'USD', '$', '+1'),
('United Kingdom', 'GB', 'GBP', '£', '+44'),
('United Arab Emirates', 'AE', 'AED', 'د.إ', '+971'),
('Singapore', 'SG', 'SGD', 'S$', '+65'),
('Australia', 'AU', 'AUD', 'A$', '+61'),
('Canada', 'CA', 'CAD', 'C$', '+1'),
('Germany', 'DE', 'EUR', '€', '+49'),
('France', 'FR', 'EUR', '€', '+33'),
('Japan', 'JP', 'JPY', '¥', '+81'),
('China', 'CN', 'CNY', '¥', '+86'),
('Saudi Arabia', 'SA', 'SAR', '﷼', '+966'),
('Malaysia', 'MY', 'MYR', 'RM', '+60'),
('Thailand', 'TH', 'THB', '฿', '+66'),
('South Africa', 'ZA', 'ZAR', 'R', '+27');

-- Create Payment Methods Table
CREATE TABLE IF NOT EXISTS payment_methods (
    id INT PRIMARY KEY AUTO_INCREMENT,
    method_name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert payment methods
INSERT INTO payment_methods (method_name, description, display_order) VALUES
('Cash', 'Cash payment', 1),
('Cheque', 'Payment by cheque', 2),
('Bank Transfer', 'Direct bank transfer/NEFT/RTGS/IMPS', 3),
('UPI', 'UPI payment (Google Pay, PhonePe, Paytm, etc.)', 4),
('Credit Card', 'Credit card payment', 5),
('Debit Card', 'Debit card payment', 6),
('Net Banking', 'Online net banking', 7),
('Wallet', 'Digital wallet payment', 8),
('Other', 'Other payment methods', 99);

-- Create Tax Rates Table
CREATE TABLE IF NOT EXISTS tax_rates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tax_name VARCHAR(50) NOT NULL,
    tax_rate DECIMAL(5,2) NOT NULL,
    description VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert common GST rates
INSERT INTO tax_rates (tax_name, tax_rate, description) VALUES
('GST 0%', 0.00, 'Zero rated GST'),
('GST 0.25%', 0.25, 'GST at 0.25%'),
('GST 3%', 3.00, 'GST at 3%'),
('GST 5%', 5.00, 'GST at 5%'),
('GST 12%', 12.00, 'GST at 12%'),
('GST 18%', 18.00, 'GST at 18%'),
('GST 28%', 28.00, 'GST at 28%');

-- Add description column to units_of_measure if it doesn't exist
ALTER TABLE units_of_measure 
ADD COLUMN IF NOT EXISTS description VARCHAR(255) AFTER symbol;

-- Insert common units of measure (only if they don't exist)
INSERT IGNORE INTO units_of_measure (name, symbol, description) VALUES
('Pieces', 'Pcs', 'Individual pieces or units'),
('Kilograms', 'Kg', 'Weight in kilograms'),
('Grams', 'g', 'Weight in grams'),
('Liters', 'L', 'Volume in liters'),
('Milliliters', 'ml', 'Volume in milliliters'),
('Meters', 'm', 'Length in meters'),
('Centimeters', 'cm', 'Length in centimeters'),
('Square Meters', 'sq.m', 'Area in square meters'),
('Square Feet', 'sq.ft', 'Area in square feet'),
('Boxes', 'Box', 'Boxes or cartons'),
('Dozens', 'Doz', 'Dozens (12 pieces)'),
('Sets', 'Set', 'Sets or kits'),
('Pairs', 'Pair', 'Pairs'),
('Bags', 'Bag', 'Bags or sacks'),
('Bottles', 'Btl', 'Bottles'),
('Cans', 'Can', 'Cans or tins'),
('Packets', 'Pkt', 'Packets'),
('Rolls', 'Roll', 'Rolls'),
('Sheets', 'Sheet', 'Sheets'),
('Hours', 'Hr', 'Time in hours');

-- Create Banks Table
CREATE TABLE IF NOT EXISTS banks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bank_name VARCHAR(100) NOT NULL,
    bank_code VARCHAR(20),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert major Indian banks
INSERT INTO banks (bank_name, bank_code) VALUES
('State Bank of India', 'SBIN'),
('HDFC Bank', 'HDFC'),
('ICICI Bank', 'ICIC'),
('Axis Bank', 'UTIB'),
('Kotak Mahindra Bank', 'KKBK'),
('Punjab National Bank', 'PUNB'),
('Bank of Baroda', 'BARB'),
('Canara Bank', 'CNRB'),
('Union Bank of India', 'UBIN'),
('Bank of India', 'BKID'),
('IndusInd Bank', 'INDB'),
('IDFC First Bank', 'IDFB'),
('Yes Bank', 'YESB'),
('Federal Bank', 'FDRL'),
('RBL Bank', 'RATN'),
('South Indian Bank', 'SIBL'),
('Karur Vysya Bank', 'KVBL'),
('City Union Bank', 'CIUB'),
('IDBI Bank', 'IBKL'),
('Central Bank of India', 'CBIN');

-- Create Invoice Status Table
CREATE TABLE IF NOT EXISTS invoice_statuses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    status_name VARCHAR(50) NOT NULL UNIQUE,
    status_color VARCHAR(20),
    description VARCHAR(255),
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert invoice statuses
INSERT INTO invoice_statuses (status_name, status_color, description, display_order) VALUES
('Draft', '#6b7280', 'Invoice is in draft state', 1),
('Sent', '#3b82f6', 'Invoice has been sent to customer', 2),
('Partially Paid', '#f59e0b', 'Invoice is partially paid', 3),
('Paid', '#10b981', 'Invoice is fully paid', 4),
('Overdue', '#ef4444', 'Invoice payment is overdue', 5),
('Cancelled', '#dc2626', 'Invoice has been cancelled', 6);

-- Create Quotation Status Table
CREATE TABLE IF NOT EXISTS quotation_statuses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    status_name VARCHAR(50) NOT NULL UNIQUE,
    status_color VARCHAR(20),
    description VARCHAR(255),
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert quotation statuses
INSERT INTO quotation_statuses (status_name, status_color, description, display_order) VALUES
('Draft', '#6b7280', 'Quotation is in draft state', 1),
('Sent', '#3b82f6', 'Quotation has been sent to customer', 2),
('Accepted', '#10b981', 'Quotation has been accepted', 3),
('Rejected', '#ef4444', 'Quotation has been rejected', 4),
('Expired', '#9ca3af', 'Quotation has expired', 5),
('Converted', '#8b5cf6', 'Quotation converted to invoice/order', 6);

-- Create Lead Sources Table
CREATE TABLE IF NOT EXISTS lead_sources (
    id INT PRIMARY KEY AUTO_INCREMENT,
    source_name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert lead sources
INSERT INTO lead_sources (source_name, description) VALUES
('Website', 'Leads from company website'),
('Referral', 'Customer referrals'),
('Social Media', 'Facebook, LinkedIn, Twitter, etc.'),
('Email Campaign', 'Email marketing campaigns'),
('Trade Show', 'Trade shows and exhibitions'),
('Cold Call', 'Cold calling'),
('Advertisement', 'Online or offline advertisements'),
('Partner', 'Business partners'),
('Direct Walk-in', 'Direct customer walk-in'),
('Other', 'Other sources');

-- Create Expense Categories Table
CREATE TABLE IF NOT EXISTS expense_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert expense categories
INSERT INTO expense_categories (category_name, description) VALUES
('Rent', 'Office/warehouse rent'),
('Utilities', 'Electricity, water, internet'),
('Salaries', 'Employee salaries and wages'),
('Travel', 'Business travel expenses'),
('Marketing', 'Marketing and advertising'),
('Office Supplies', 'Stationery and office supplies'),
('Equipment', 'Equipment purchase and maintenance'),
('Insurance', 'Business insurance'),
('Professional Fees', 'Legal, accounting, consulting fees'),
('Taxes', 'Business taxes and fees'),
('Transportation', 'Vehicle and logistics'),
('Miscellaneous', 'Other expenses');

-- Create Priority Levels Table
CREATE TABLE IF NOT EXISTS priority_levels (
    id INT PRIMARY KEY AUTO_INCREMENT,
    priority_name VARCHAR(50) NOT NULL UNIQUE,
    priority_color VARCHAR(20),
    priority_value INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert priority levels
INSERT INTO priority_levels (priority_name, priority_color, priority_value) VALUES
('Low', '#10b981', 1),
('Medium', '#f59e0b', 2),
('High', '#ef4444', 3),
('Urgent', '#dc2626', 4);

-- Create indexes for better performance
CREATE INDEX idx_indian_states_name ON indian_states(state_name);
CREATE INDEX idx_countries_name ON countries(country_name);
CREATE INDEX idx_payment_methods_active ON payment_methods(is_active);
CREATE INDEX idx_tax_rates_active ON tax_rates(is_active);
CREATE INDEX idx_banks_name ON banks(bank_name);
