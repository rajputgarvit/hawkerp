-- Advanced Sales Module - Database Migration Script
-- Phase 1: Sales Analytics, Customer Insights, Payment Tracking, Sales Reports

-- ============================================
-- 1. PAYMENTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_id INT NOT NULL,
    payment_date DATE NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    payment_method ENUM('Cash', 'Cheque', 'Bank Transfer', 'UPI', 'Card', 'Other') DEFAULT 'Cash',
    reference_number VARCHAR(100),
    bank_name VARCHAR(100),
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_invoice (invoice_id),
    INDEX idx_payment_date (payment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 2. CUSTOMER ENHANCEMENTS
-- ============================================
ALTER TABLE customers 
ADD COLUMN IF NOT EXISTS customer_segment ENUM('VIP', 'Premium', 'Regular', 'New') DEFAULT 'Regular',
ADD COLUMN IF NOT EXISTS last_purchase_date DATE,
ADD COLUMN IF NOT EXISTS total_purchases DECIMAL(15,2) DEFAULT 0,
ADD COLUMN IF NOT EXISTS outstanding_balance DECIMAL(12,2) DEFAULT 0;

-- ============================================
-- 3. QUOTATION ENHANCEMENTS (for Pipeline)
-- ============================================
ALTER TABLE quotations
ADD COLUMN IF NOT EXISTS pipeline_stage ENUM('Lead', 'Quotation', 'Negotiation', 'Won', 'Lost') DEFAULT 'Quotation',
ADD COLUMN IF NOT EXISTS expected_close_date DATE,
ADD COLUMN IF NOT EXISTS win_probability INT DEFAULT 50,
ADD COLUMN IF NOT EXISTS lost_reason TEXT;

-- ============================================
-- 4. SALES TARGETS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS sales_targets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    target_period ENUM('Monthly', 'Quarterly', 'Yearly') DEFAULT 'Monthly',
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    target_amount DECIMAL(15,2) NOT NULL,
    achieved_amount DECIMAL(15,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_period (user_id, period_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 5. PAYMENT REMINDERS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS payment_reminders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_id INT NOT NULL,
    reminder_date DATE NOT NULL,
    reminder_type ENUM('Email', 'SMS', 'Both') DEFAULT 'Email',
    status ENUM('Pending', 'Sent', 'Failed') DEFAULT 'Pending',
    sent_at TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    INDEX idx_invoice (invoice_id),
    INDEX idx_reminder_date (reminder_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 6. CUSTOMER NOTES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS customer_notes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    note TEXT NOT NULL,
    note_type ENUM('General', 'Follow-up', 'Complaint', 'Feedback') DEFAULT 'General',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_customer (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 7. UPDATE EXISTING DATA
-- ============================================

-- Update customer last purchase date
UPDATE customers c
SET last_purchase_date = (
    SELECT MAX(invoice_date)
    FROM invoices i
    WHERE i.customer_id = c.id
);

-- Update customer total purchases
UPDATE customers c
SET total_purchases = (
    SELECT COALESCE(SUM(total_amount), 0)
    FROM invoices i
    WHERE i.customer_id = c.id
    AND i.status != 'Cancelled'
);

-- Update customer outstanding balance
UPDATE customers c
SET outstanding_balance = (
    SELECT COALESCE(SUM(balance_amount), 0)
    FROM invoices i
    WHERE i.customer_id = c.id
    AND i.status IN ('Sent', 'Partially Paid', 'Overdue')
);

-- ============================================
-- 8. CREATE VIEWS FOR REPORTING
-- ============================================

-- Sales Summary View
CREATE OR REPLACE VIEW v_sales_summary AS
SELECT 
    DATE_FORMAT(invoice_date, '%Y-%m') as period,
    COUNT(*) as total_invoices,
    SUM(subtotal) as total_sales,
    SUM(tax_amount) as total_tax,
    SUM(total_amount) as total_revenue,
    SUM(paid_amount) as total_collected,
    SUM(balance_amount) as total_outstanding
FROM invoices
WHERE status != 'Cancelled'
GROUP BY DATE_FORMAT(invoice_date, '%Y-%m')
ORDER BY period DESC;

-- Customer Sales View
CREATE OR REPLACE VIEW v_customer_sales AS
SELECT 
    c.id,
    c.customer_code,
    c.company_name,
    c.customer_segment,
    COUNT(i.id) as total_orders,
    SUM(i.total_amount) as total_revenue,
    SUM(i.paid_amount) as total_paid,
    SUM(i.balance_amount) as outstanding_balance,
    MAX(i.invoice_date) as last_purchase_date,
    AVG(i.total_amount) as avg_order_value
FROM customers c
LEFT JOIN invoices i ON c.id = i.customer_id AND i.status != 'Cancelled'
GROUP BY c.id, c.customer_code, c.company_name, c.customer_segment;

-- Product Sales View
CREATE OR REPLACE VIEW v_product_sales AS
SELECT 
    p.id,
    p.product_code,
    p.name,
    p.category_id,
    SUM(ii.quantity) as total_quantity_sold,
    SUM(ii.quantity * ii.unit_price) as total_sales,
    COUNT(DISTINCT ii.invoice_id) as times_sold
FROM products p
LEFT JOIN invoice_items ii ON p.id = ii.product_id
LEFT JOIN invoices i ON ii.invoice_id = i.id AND i.status != 'Cancelled'
GROUP BY p.id, p.product_code, p.name, p.category_id;

-- ============================================
-- MIGRATION COMPLETE
-- ============================================
