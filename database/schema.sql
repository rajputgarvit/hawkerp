-- ============================================
-- Tiger ERP - Advanced Database Schema
-- ============================================

DROP DATABASE IF EXISTS tiger_erp;
CREATE DATABASE tiger_erp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tiger_erp;

-- ============================================
-- CORE AUTHENTICATION & AUTHORIZATION
-- ============================================

CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    module VARCHAR(50) NOT NULL,
    action VARCHAR(50) NOT NULL,
    description TEXT,
    UNIQUE KEY unique_permission (module, action)
);

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_username (username)
);

CREATE TABLE user_roles (
    user_id INT NOT NULL,
    role_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

CREATE TABLE role_permissions (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

CREATE TABLE audit_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_table (table_name, record_id),
    INDEX idx_created (created_at)
);

-- ============================================
-- HR MANAGEMENT MODULE
-- ============================================

CREATE TABLE departments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    parent_id INT NULL,
    manager_id INT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES departments(id) ON DELETE SET NULL,
    INDEX idx_parent (parent_id)
);

CREATE TABLE designations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL,
    department_id INT,
    level INT DEFAULT 1,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
);

CREATE TABLE employees (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_code VARCHAR(20) UNIQUE NOT NULL,
    user_id INT UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other'),
    marital_status ENUM('Single', 'Married', 'Divorced', 'Widowed'),
    blood_group VARCHAR(5),
    address TEXT,
    city VARCHAR(50),
    state VARCHAR(50),
    country VARCHAR(50),
    postal_code VARCHAR(10),
    department_id INT,
    designation_id INT,
    reporting_to INT,
    date_of_joining DATE NOT NULL,
    date_of_leaving DATE NULL,
    employment_type ENUM('Permanent', 'Contract', 'Intern', 'Temporary') DEFAULT 'Permanent',
    status ENUM('Active', 'Inactive', 'Terminated', 'Resigned') DEFAULT 'Active',
    bank_name VARCHAR(100),
    bank_account_number VARCHAR(50),
    bank_ifsc VARCHAR(20),
    pan_number VARCHAR(20),
    aadhar_number VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (designation_id) REFERENCES designations(id) ON DELETE SET NULL,
    FOREIGN KEY (reporting_to) REFERENCES employees(id) ON DELETE SET NULL,
    INDEX idx_dept (department_id),
    INDEX idx_status (status)
);

ALTER TABLE departments ADD FOREIGN KEY (manager_id) REFERENCES employees(id) ON DELETE SET NULL;

CREATE TABLE attendance (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    check_in TIME,
    check_out TIME,
    status ENUM('Present', 'Absent', 'Half Day', 'Leave', 'Holiday') DEFAULT 'Present',
    working_hours DECIMAL(4,2),
    overtime_hours DECIMAL(4,2) DEFAULT 0,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    UNIQUE KEY unique_attendance (employee_id, attendance_date),
    INDEX idx_date (attendance_date),
    INDEX idx_employee_date (employee_id, attendance_date)
);

CREATE TABLE leave_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    days_per_year INT DEFAULT 0,
    is_paid BOOLEAN DEFAULT TRUE,
    requires_approval BOOLEAN DEFAULT TRUE,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE
);

CREATE TABLE leave_applications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    leave_type_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_days DECIMAL(4,1) NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected', 'Cancelled') DEFAULT 'Pending',
    approved_by INT,
    approved_at TIMESTAMP NULL,
    rejection_reason TEXT,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(id),
    FOREIGN KEY (approved_by) REFERENCES employees(id) ON DELETE SET NULL,
    INDEX idx_employee (employee_id),
    INDEX idx_status (status),
    INDEX idx_dates (start_date, end_date)
);

CREATE TABLE payroll_components (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    type ENUM('Earning', 'Deduction') NOT NULL,
    calculation_type ENUM('Fixed', 'Percentage', 'Formula') DEFAULT 'Fixed',
    formula TEXT,
    is_taxable BOOLEAN DEFAULT TRUE,
    is_active BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0
);

CREATE TABLE employee_salary_structure (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    component_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    effective_from DATE NOT NULL,
    effective_to DATE NULL,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (component_id) REFERENCES payroll_components(id),
    INDEX idx_employee (employee_id),
    INDEX idx_effective (effective_from, effective_to)
);

CREATE TABLE payroll (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    month INT NOT NULL,
    year INT NOT NULL,
    gross_salary DECIMAL(10,2) NOT NULL,
    total_deductions DECIMAL(10,2) DEFAULT 0,
    net_salary DECIMAL(10,2) NOT NULL,
    payment_date DATE,
    payment_status ENUM('Pending', 'Processed', 'Paid') DEFAULT 'Pending',
    payment_method ENUM('Bank Transfer', 'Cash', 'Cheque'),
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    UNIQUE KEY unique_payroll (employee_id, month, year),
    INDEX idx_period (year, month),
    INDEX idx_status (payment_status)
);

CREATE TABLE payroll_details (
    id INT PRIMARY KEY AUTO_INCREMENT,
    payroll_id INT NOT NULL,
    component_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (payroll_id) REFERENCES payroll(id) ON DELETE CASCADE,
    FOREIGN KEY (component_id) REFERENCES payroll_components(id)
);

-- ============================================
-- INVENTORY MANAGEMENT MODULE
-- ============================================

CREATE TABLE product_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    parent_id INT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES product_categories(id) ON DELETE SET NULL,
    INDEX idx_parent (parent_id)
);

CREATE TABLE units_of_measure (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    symbol VARCHAR(10) NOT NULL,
    type ENUM('Weight', 'Length', 'Volume', 'Quantity', 'Other') DEFAULT 'Quantity'
);

CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(200) NOT NULL,
    category_id INT,
    description TEXT,
    uom_id INT NOT NULL,
    product_type ENUM('Goods', 'Service', 'Raw Material', 'Finished Goods') DEFAULT 'Goods',
    hsn_code VARCHAR(20),
    barcode VARCHAR(100),
    sku VARCHAR(100),
    reorder_level DECIMAL(10,2) DEFAULT 0,
    reorder_quantity DECIMAL(10,2) DEFAULT 0,
    standard_cost DECIMAL(10,2) DEFAULT 0,
    selling_price DECIMAL(10,2) DEFAULT 0,
    tax_rate DECIMAL(5,2) DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES product_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (uom_id) REFERENCES units_of_measure(id),
    INDEX idx_code (product_code),
    INDEX idx_category (category_id),
    INDEX idx_barcode (barcode)
);

CREATE TABLE warehouses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) UNIQUE NOT NULL,
    address TEXT,
    city VARCHAR(50),
    state VARCHAR(50),
    country VARCHAR(50),
    postal_code VARCHAR(10),
    manager_id INT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (manager_id) REFERENCES employees(id) ON DELETE SET NULL
);

CREATE TABLE stock_balance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    warehouse_id INT NOT NULL,
    quantity DECIMAL(10,2) DEFAULT 0,
    reserved_quantity DECIMAL(10,2) DEFAULT 0,
    available_quantity DECIMAL(10,2) GENERATED ALWAYS AS (quantity - reserved_quantity) STORED,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_stock (product_id, warehouse_id),
    INDEX idx_product (product_id),
    INDEX idx_warehouse (warehouse_id)
);

CREATE TABLE stock_transactions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    transaction_type ENUM('IN', 'OUT', 'TRANSFER', 'ADJUSTMENT') NOT NULL,
    product_id INT NOT NULL,
    warehouse_id INT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    reference_type VARCHAR(50),
    reference_id INT,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    remarks TEXT,
    created_by INT,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_product (product_id),
    INDEX idx_warehouse (warehouse_id),
    INDEX idx_date (transaction_date),
    INDEX idx_reference (reference_type, reference_id)
);

-- ============================================
-- SALES MANAGEMENT MODULE
-- ============================================

CREATE TABLE customers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_code VARCHAR(20) UNIQUE NOT NULL,
    company_name VARCHAR(200) NOT NULL,
    contact_person VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    mobile VARCHAR(20),
    website VARCHAR(100),
    gstin VARCHAR(20),
    pan VARCHAR(20),
    credit_limit DECIMAL(12,2) DEFAULT 0,
    payment_terms INT DEFAULT 0,
    customer_type ENUM('Individual', 'Company') DEFAULT 'Company',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (customer_code),
    INDEX idx_company (company_name)
);

CREATE TABLE customer_addresses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    address_type ENUM('Billing', 'Shipping', 'Both') DEFAULT 'Both',
    address_line1 VARCHAR(200),
    address_line2 VARCHAR(200),
    city VARCHAR(50),
    state VARCHAR(50),
    country VARCHAR(50) DEFAULT 'India',
    postal_code VARCHAR(10),
    is_default BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_customer (customer_id)
);

CREATE TABLE quotations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    quotation_number VARCHAR(50) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    quotation_date DATE NOT NULL,
    valid_until DATE,
    reference VARCHAR(100),
    status ENUM('Draft', 'Sent', 'Accepted', 'Rejected', 'Expired') DEFAULT 'Draft',
    subtotal DECIMAL(12,2) DEFAULT 0,
    tax_amount DECIMAL(12,2) DEFAULT 0,
    discount_amount DECIMAL(12,2) DEFAULT 0,
    total_amount DECIMAL(12,2) DEFAULT 0,
    terms_conditions TEXT,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_customer (customer_id),
    INDEX idx_date (quotation_date),
    INDEX idx_status (status)
);

CREATE TABLE quotation_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    quotation_id INT NOT NULL,
    product_id INT NOT NULL,
    description TEXT,
    quantity DECIMAL(10,2) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    tax_rate DECIMAL(5,2) DEFAULT 0,
    discount_percent DECIMAL(5,2) DEFAULT 0,
    line_total DECIMAL(12,2) GENERATED ALWAYS AS (quantity * unit_price * (1 - discount_percent/100) * (1 + tax_rate/100)) STORED,
    FOREIGN KEY (quotation_id) REFERENCES quotations(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id),
    INDEX idx_quotation (quotation_id)
);

CREATE TABLE sales_orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    quotation_id INT,
    order_date DATE NOT NULL,
    expected_delivery_date DATE,
    status ENUM('Draft', 'Confirmed', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Draft',
    subtotal DECIMAL(12,2) DEFAULT 0,
    tax_amount DECIMAL(12,2) DEFAULT 0,
    discount_amount DECIMAL(12,2) DEFAULT 0,
    shipping_charges DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(12,2) DEFAULT 0,
    payment_status ENUM('Unpaid', 'Partially Paid', 'Paid') DEFAULT 'Unpaid',
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (quotation_id) REFERENCES quotations(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_customer (customer_id),
    INDEX idx_date (order_date),
    INDEX idx_status (status)
);

CREATE TABLE sales_order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    description TEXT,
    quantity DECIMAL(10,2) NOT NULL,
    delivered_quantity DECIMAL(10,2) DEFAULT 0,
    unit_price DECIMAL(10,2) NOT NULL,
    tax_rate DECIMAL(5,2) DEFAULT 0,
    discount_percent DECIMAL(5,2) DEFAULT 0,
    line_total DECIMAL(12,2),
    FOREIGN KEY (order_id) REFERENCES sales_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id),
    INDEX idx_order (order_id)
);

CREATE TABLE invoices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    sales_order_id INT,
    invoice_date DATE NOT NULL,
    due_date DATE,
    status ENUM('Draft', 'Sent', 'Paid', 'Partially Paid', 'Overdue', 'Cancelled') DEFAULT 'Draft',
    subtotal DECIMAL(12,2) DEFAULT 0,
    tax_amount DECIMAL(12,2) DEFAULT 0,
    discount_amount DECIMAL(12,2) DEFAULT 0,
    total_amount DECIMAL(12,2) DEFAULT 0,
    paid_amount DECIMAL(12,2) DEFAULT 0,
    balance_amount DECIMAL(12,2) GENERATED ALWAYS AS (total_amount - paid_amount) STORED,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (sales_order_id) REFERENCES sales_orders(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_customer (customer_id),
    INDEX idx_date (invoice_date),
    INDEX idx_status (status)
);

CREATE TABLE invoice_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_id INT NOT NULL,
    product_id INT NOT NULL,
    description TEXT,
    quantity DECIMAL(10,2) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    tax_rate DECIMAL(5,2) DEFAULT 0,
    discount_percent DECIMAL(5,2) DEFAULT 0,
    line_total DECIMAL(12,2),
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id),
    INDEX idx_invoice (invoice_id)
);

CREATE TABLE payments_received (
    id INT PRIMARY KEY AUTO_INCREMENT,
    payment_number VARCHAR(50) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    payment_date DATE NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    payment_mode ENUM('Cash', 'Cheque', 'Bank Transfer', 'Credit Card', 'UPI', 'Other') DEFAULT 'Bank Transfer',
    reference_number VARCHAR(100),
    bank_account_id INT,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_customer (customer_id),
    INDEX idx_date (payment_date)
);

CREATE TABLE payment_allocations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    payment_id INT NOT NULL,
    invoice_id INT NOT NULL,
    allocated_amount DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (payment_id) REFERENCES payments_received(id) ON DELETE CASCADE,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
);

-- ============================================
-- PURCHASE MANAGEMENT MODULE
-- ============================================

CREATE TABLE suppliers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    supplier_code VARCHAR(20) UNIQUE NOT NULL,
    company_name VARCHAR(200) NOT NULL,
    contact_person VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    mobile VARCHAR(20),
    website VARCHAR(100),
    gstin VARCHAR(20),
    pan VARCHAR(20),
    credit_limit DECIMAL(12,2) DEFAULT 0,
    payment_terms INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (supplier_code)
);

CREATE TABLE supplier_addresses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    supplier_id INT NOT NULL,
    address_type ENUM('Billing', 'Shipping', 'Both') DEFAULT 'Both',
    address_line1 VARCHAR(200),
    address_line2 VARCHAR(200),
    city VARCHAR(50),
    state VARCHAR(50),
    country VARCHAR(50) DEFAULT 'India',
    postal_code VARCHAR(10),
    is_default BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE
);

CREATE TABLE purchase_orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    po_number VARCHAR(50) UNIQUE NOT NULL,
    supplier_id INT NOT NULL,
    order_date DATE NOT NULL,
    expected_delivery_date DATE,
    status ENUM('Draft', 'Sent', 'Confirmed', 'Partially Received', 'Received', 'Cancelled') DEFAULT 'Draft',
    subtotal DECIMAL(12,2) DEFAULT 0,
    tax_amount DECIMAL(12,2) DEFAULT 0,
    discount_amount DECIMAL(12,2) DEFAULT 0,
    shipping_charges DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(12,2) DEFAULT 0,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_supplier (supplier_id),
    INDEX idx_date (order_date),
    INDEX idx_status (status)
);

CREATE TABLE purchase_order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    po_id INT NOT NULL,
    product_id INT NOT NULL,
    description TEXT,
    quantity DECIMAL(10,2) NOT NULL,
    received_quantity DECIMAL(10,2) DEFAULT 0,
    unit_price DECIMAL(10,2) NOT NULL,
    tax_rate DECIMAL(5,2) DEFAULT 0,
    discount_percent DECIMAL(5,2) DEFAULT 0,
    line_total DECIMAL(12,2),
    FOREIGN KEY (po_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE TABLE goods_received_notes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    grn_number VARCHAR(50) UNIQUE NOT NULL,
    po_id INT NOT NULL,
    warehouse_id INT NOT NULL,
    received_date DATE NOT NULL,
    received_by INT,
    status ENUM('Draft', 'Completed') DEFAULT 'Draft',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(id),
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
    FOREIGN KEY (received_by) REFERENCES employees(id) ON DELETE SET NULL
);

CREATE TABLE grn_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    grn_id INT NOT NULL,
    po_item_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity_received DECIMAL(10,2) NOT NULL,
    quantity_accepted DECIMAL(10,2) NOT NULL,
    quantity_rejected DECIMAL(10,2) DEFAULT 0,
    remarks TEXT,
    FOREIGN KEY (grn_id) REFERENCES goods_received_notes(id) ON DELETE CASCADE,
    FOREIGN KEY (po_item_id) REFERENCES purchase_order_items(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE TABLE purchase_invoices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bill_number VARCHAR(50) UNIQUE NOT NULL,
    supplier_id INT NOT NULL,
    po_id INT,
    bill_date DATE NOT NULL,
    due_date DATE,
    status ENUM('Draft', 'Submitted', 'Paid', 'Partially Paid', 'Overdue') DEFAULT 'Draft',
    subtotal DECIMAL(12,2) DEFAULT 0,
    tax_amount DECIMAL(12,2) DEFAULT 0,
    discount_amount DECIMAL(12,2) DEFAULT 0,
    total_amount DECIMAL(12,2) DEFAULT 0,
    paid_amount DECIMAL(12,2) DEFAULT 0,
    balance_amount DECIMAL(12,2) GENERATED ALWAYS AS (total_amount - paid_amount) STORED,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (po_id) REFERENCES purchase_orders(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_supplier (supplier_id),
    INDEX idx_date (bill_date)
);

CREATE TABLE purchase_invoice_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bill_id INT NOT NULL,
    product_id INT NOT NULL,
    description TEXT,
    quantity DECIMAL(10,2) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    tax_rate DECIMAL(5,2) DEFAULT 0,
    discount_percent DECIMAL(5,2) DEFAULT 0,
    line_total DECIMAL(12,2),
    FOREIGN KEY (bill_id) REFERENCES purchase_invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE TABLE payments_made (
    id INT PRIMARY KEY AUTO_INCREMENT,
    payment_number VARCHAR(50) UNIQUE NOT NULL,
    supplier_id INT NOT NULL,
    payment_date DATE NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    payment_mode ENUM('Cash', 'Cheque', 'Bank Transfer', 'Credit Card', 'UPI', 'Other') DEFAULT 'Bank Transfer',
    reference_number VARCHAR(100),
    bank_account_id INT,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_supplier (supplier_id),
    INDEX idx_date (payment_date)
);

CREATE TABLE payment_made_allocations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    payment_id INT NOT NULL,
    bill_id INT NOT NULL,
    allocated_amount DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (payment_id) REFERENCES payments_made(id) ON DELETE CASCADE,
    FOREIGN KEY (bill_id) REFERENCES purchase_invoices(id) ON DELETE CASCADE
);

-- ============================================
-- ACCOUNTING MODULE
-- ============================================

CREATE TABLE account_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    category ENUM('Asset', 'Liability', 'Equity', 'Income', 'Expense') NOT NULL
);

CREATE TABLE chart_of_accounts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    account_code VARCHAR(20) UNIQUE NOT NULL,
    account_name VARCHAR(200) NOT NULL,
    account_type_id INT NOT NULL,
    parent_account_id INT,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_type_id) REFERENCES account_types(id),
    FOREIGN KEY (parent_account_id) REFERENCES chart_of_accounts(id) ON DELETE SET NULL,
    INDEX idx_code (account_code),
    INDEX idx_type (account_type_id)
);

CREATE TABLE fiscal_years (
    id INT PRIMARY KEY AUTO_INCREMENT,
    year_name VARCHAR(20) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_closed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE journal_entries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    entry_number VARCHAR(50) UNIQUE NOT NULL,
    entry_date DATE NOT NULL,
    fiscal_year_id INT NOT NULL,
    reference_type VARCHAR(50),
    reference_id INT,
    description TEXT,
    total_debit DECIMAL(15,2) DEFAULT 0,
    total_credit DECIMAL(15,2) DEFAULT 0,
    status ENUM('Draft', 'Posted', 'Cancelled') DEFAULT 'Draft',
    created_by INT,
    posted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fiscal_year_id) REFERENCES fiscal_years(id),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_date (entry_date),
    INDEX idx_reference (reference_type, reference_id)
);

CREATE TABLE journal_entry_lines (
    id INT PRIMARY KEY AUTO_INCREMENT,
    journal_entry_id INT NOT NULL,
    account_id INT NOT NULL,
    debit_amount DECIMAL(15,2) DEFAULT 0,
    credit_amount DECIMAL(15,2) DEFAULT 0,
    description TEXT,
    FOREIGN KEY (journal_entry_id) REFERENCES journal_entries(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES chart_of_accounts(id),
    INDEX idx_journal (journal_entry_id),
    INDEX idx_account (account_id)
);

CREATE TABLE cost_centers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE
);

CREATE TABLE bank_accounts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    account_name VARCHAR(100) NOT NULL,
    bank_name VARCHAR(100) NOT NULL,
    account_number VARCHAR(50) NOT NULL,
    ifsc_code VARCHAR(20),
    branch VARCHAR(100),
    account_type ENUM('Savings', 'Current', 'Cash Credit', 'Overdraft') DEFAULT 'Current',
    opening_balance DECIMAL(15,2) DEFAULT 0,
    current_balance DECIMAL(15,2) DEFAULT 0,
    chart_account_id INT,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (chart_account_id) REFERENCES chart_of_accounts(id)
);

-- ============================================
-- CRM MODULE
-- ============================================

CREATE TABLE lead_sources (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description TEXT
);

CREATE TABLE lead_statuses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    color VARCHAR(7),
    display_order INT DEFAULT 0
);

CREATE TABLE leads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lead_number VARCHAR(50) UNIQUE NOT NULL,
    company_name VARCHAR(200),
    contact_person VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    mobile VARCHAR(20),
    source_id INT,
    status_id INT,
    assigned_to INT,
    expected_revenue DECIMAL(12,2),
    probability INT DEFAULT 0,
    expected_close_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (source_id) REFERENCES lead_sources(id) ON DELETE SET NULL,
    FOREIGN KEY (status_id) REFERENCES lead_statuses(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status_id),
    INDEX idx_assigned (assigned_to)
);

CREATE TABLE opportunities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    opportunity_number VARCHAR(50) UNIQUE NOT NULL,
    lead_id INT,
    customer_id INT,
    title VARCHAR(200) NOT NULL,
    amount DECIMAL(12,2),
    probability INT DEFAULT 0,
    stage ENUM('Qualification', 'Needs Analysis', 'Proposal', 'Negotiation', 'Closed Won', 'Closed Lost') DEFAULT 'Qualification',
    expected_close_date DATE,
    assigned_to INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE SET NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE activities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    activity_type ENUM('Call', 'Email', 'Meeting', 'Task', 'Note') NOT NULL,
    subject VARCHAR(200) NOT NULL,
    description TEXT,
    related_to_type VARCHAR(50),
    related_to_id INT,
    scheduled_at TIMESTAMP,
    completed_at TIMESTAMP NULL,
    status ENUM('Scheduled', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
    assigned_to INT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_related (related_to_type, related_to_id),
    INDEX idx_scheduled (scheduled_at)
);

-- ============================================
-- PRODUCTION MODULE
-- ============================================

CREATE TABLE bill_of_materials (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bom_number VARCHAR(50) UNIQUE NOT NULL,
    product_id INT NOT NULL,
    quantity DECIMAL(10,2) DEFAULT 1,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    INDEX idx_product (product_id)
);

CREATE TABLE bom_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bom_id INT NOT NULL,
    component_id INT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    scrap_percentage DECIMAL(5,2) DEFAULT 0,
    notes TEXT,
    FOREIGN KEY (bom_id) REFERENCES bill_of_materials(id) ON DELETE CASCADE,
    FOREIGN KEY (component_id) REFERENCES products(id)
);

CREATE TABLE work_orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    wo_number VARCHAR(50) UNIQUE NOT NULL,
    product_id INT NOT NULL,
    bom_id INT,
    quantity_to_produce DECIMAL(10,2) NOT NULL,
    quantity_produced DECIMAL(10,2) DEFAULT 0,
    warehouse_id INT NOT NULL,
    planned_start_date DATE,
    planned_end_date DATE,
    actual_start_date DATE,
    actual_end_date DATE,
    status ENUM('Draft', 'Released', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Draft',
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (bom_id) REFERENCES bill_of_materials(id) ON DELETE SET NULL,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_product (product_id)
);

CREATE TABLE production_entries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    entry_number VARCHAR(50) UNIQUE NOT NULL,
    work_order_id INT NOT NULL,
    production_date DATE NOT NULL,
    quantity_produced DECIMAL(10,2) NOT NULL,
    quantity_rejected DECIMAL(10,2) DEFAULT 0,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (work_order_id) REFERENCES work_orders(id),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================
-- INITIAL DATA INSERTION
-- ============================================

-- Insert default roles
INSERT INTO roles (name, description) VALUES
('Super Admin', 'Full system access'),
('Admin', 'Administrative access'),
('Manager', 'Department manager access'),
('Employee', 'Basic employee access'),
('Accountant', 'Accounting module access'),
('Sales Person', 'Sales module access'),
('Purchase Officer', 'Purchase module access');

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password_hash, full_name) VALUES
('admin', 'admin@tigererp.com', '$2y$12$8VPm.EJoFaI330m1N.e/lOad7dvaF3NeN9BdTYD59pe/zpG66/dve', 'System Administrator');

-- Assign admin role
INSERT INTO user_roles (user_id, role_id) VALUES (1, 1);

-- Insert leave types
INSERT INTO leave_types (name, days_per_year, is_paid) VALUES
('Casual Leave', 12, TRUE),
('Sick Leave', 12, TRUE),
('Earned Leave', 15, TRUE),
('Maternity Leave', 180, TRUE),
('Paternity Leave', 15, TRUE),
('Loss of Pay', 0, FALSE);

-- Insert units of measure
INSERT INTO units_of_measure (name, symbol, type) VALUES
('Piece', 'Pcs', 'Quantity'),
('Kilogram', 'Kg', 'Weight'),
('Gram', 'g', 'Weight'),
('Liter', 'L', 'Volume'),
('Meter', 'm', 'Length'),
('Box', 'Box', 'Quantity'),
('Dozen', 'Dzn', 'Quantity'),
('Set', 'Set', 'Quantity');

-- Insert account types
INSERT INTO account_types (name, category) VALUES
('Current Asset', 'Asset'),
('Fixed Asset', 'Asset'),
('Current Liability', 'Liability'),
('Long Term Liability', 'Liability'),
('Equity', 'Equity'),
('Direct Income', 'Income'),
('Indirect Income', 'Income'),
('Direct Expense', 'Expense'),
('Indirect Expense', 'Expense');

-- Insert basic chart of accounts
INSERT INTO chart_of_accounts (account_code, account_name, account_type_id) VALUES
('1000', 'Assets', 1),
('1100', 'Cash and Bank', 1),
('1200', 'Accounts Receivable', 1),
('1300', 'Inventory', 1),
('2000', 'Liabilities', 3),
('2100', 'Accounts Payable', 3),
('2200', 'Tax Payable', 3),
('3000', 'Equity', 5),
('3100', 'Capital', 5),
('4000', 'Revenue', 6),
('4100', 'Sales Revenue', 6),
('5000', 'Expenses', 8),
('5100', 'Cost of Goods Sold', 8),
('5200', 'Operating Expenses', 9);

-- Insert fiscal year
INSERT INTO fiscal_years (year_name, start_date, end_date) VALUES
('FY 2025-26', '2025-04-01', '2026-03-31');

-- Insert lead sources
INSERT INTO lead_sources (name) VALUES
('Website'),
('Referral'),
('Cold Call'),
('Trade Show'),
('Social Media'),
('Email Campaign');

-- Insert lead statuses
INSERT INTO lead_statuses (name, color, display_order) VALUES
('New', '#3498db', 1),
('Contacted', '#f39c12', 2),
('Qualified', '#2ecc71', 3),
('Proposal Sent', '#9b59b6', 4),
('Negotiation', '#e67e22', 5),
('Won', '#27ae60', 6),
('Lost', '#e74c3c', 7);

-- Insert payroll components
INSERT INTO payroll_components (name, type, calculation_type, is_taxable) VALUES
('Basic Salary', 'Earning', 'Fixed', TRUE),
('HRA', 'Earning', 'Percentage', TRUE),
('Conveyance Allowance', 'Earning', 'Fixed', TRUE),
('Medical Allowance', 'Earning', 'Fixed', TRUE),
('Special Allowance', 'Earning', 'Fixed', TRUE),
('Provident Fund', 'Deduction', 'Percentage', FALSE),
('Professional Tax', 'Deduction', 'Fixed', FALSE),
('Income Tax', 'Deduction', 'Fixed', FALSE),
('ESI', 'Deduction', 'Percentage', FALSE);

-- Create triggers for stock balance updates
DELIMITER //

CREATE TRIGGER after_stock_transaction_insert
AFTER INSERT ON stock_transactions
FOR EACH ROW
BEGIN
    IF NEW.transaction_type = 'IN' THEN
        INSERT INTO stock_balance (product_id, warehouse_id, quantity)
        VALUES (NEW.product_id, NEW.warehouse_id, NEW.quantity)
        ON DUPLICATE KEY UPDATE quantity = quantity + NEW.quantity;
    ELSEIF NEW.transaction_type = 'OUT' THEN
        UPDATE stock_balance 
        SET quantity = quantity - NEW.quantity
        WHERE product_id = NEW.product_id AND warehouse_id = NEW.warehouse_id;
    END IF;
END//

DELIMITER ;

-- Views for reporting
CREATE VIEW vw_employee_details AS
SELECT 
    e.id,
    e.employee_code,
    CONCAT(e.first_name, ' ', e.last_name) AS full_name,
    e.email,
    e.phone,
    d.name AS department,
    des.title AS designation,
    CONCAT(mgr.first_name, ' ', mgr.last_name) AS manager_name,
    e.status,
    e.date_of_joining
FROM employees e
LEFT JOIN departments d ON e.department_id = d.id
LEFT JOIN designations des ON e.designation_id = des.id
LEFT JOIN employees mgr ON e.reporting_to = mgr.id;

CREATE VIEW vw_stock_summary AS
SELECT 
    p.id,
    p.product_code,
    p.name AS product_name,
    pc.name AS category,
    w.name AS warehouse,
    sb.quantity,
    sb.reserved_quantity,
    sb.available_quantity,
    p.reorder_level,
    CASE 
        WHEN sb.available_quantity <= p.reorder_level THEN 'Low Stock'
        ELSE 'In Stock'
    END AS stock_status
FROM stock_balance sb
JOIN products p ON sb.product_id = p.id
JOIN warehouses w ON sb.warehouse_id = w.id
LEFT JOIN product_categories pc ON p.category_id = pc.id;

CREATE VIEW vw_sales_summary AS
SELECT 
    i.id,
    i.invoice_number,
    i.invoice_date,
    c.company_name AS customer,
    i.total_amount,
    i.paid_amount,
    i.balance_amount,
    i.status,
    DATEDIFF(CURDATE(), i.due_date) AS days_overdue
FROM invoices i
JOIN customers c ON i.customer_id = c.id;

CREATE VIEW vw_purchase_summary AS
SELECT 
    pi.id,
    pi.bill_number,
    pi.bill_date,
    s.company_name AS supplier,
    pi.total_amount,
    pi.paid_amount,
    pi.balance_amount,
    pi.status,
    DATEDIFF(CURDATE(), pi.due_date) AS days_overdue
FROM purchase_invoices pi
JOIN suppliers s ON pi.supplier_id = s.id;

-- ============================================
-- END OF SCHEMA
-- ============================================
