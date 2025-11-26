-- User Registration & Subscription System
-- Database: tiger_erp

-- Add email verification and onboarding fields to users table
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS email_verified BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS email_verification_token VARCHAR(100),
ADD COLUMN IF NOT EXISTS onboarding_completed BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS company_name VARCHAR(255);

-- Create subscriptions table
CREATE TABLE IF NOT EXISTS subscriptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    plan_name VARCHAR(50) NOT NULL,
    plan_price DECIMAL(10,2) NOT NULL,
    billing_cycle ENUM('monthly', 'annual') DEFAULT 'monthly',
    status ENUM('trial', 'active', 'cancelled', 'expired') DEFAULT 'trial',
    trial_ends_at DATETIME,
    current_period_start DATETIME,
    current_period_end DATETIME,
    razorpay_subscription_id VARCHAR(100),
    razorpay_customer_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
);

-- Create payment transactions table
CREATE TABLE IF NOT EXISTS payment_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    subscription_id INT NOT NULL,
    razorpay_payment_id VARCHAR(100),
    razorpay_order_id VARCHAR(100),
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'INR',
    status ENUM('pending', 'success', 'failed') DEFAULT 'pending',
    payment_method VARCHAR(50),
    transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE,
    INDEX idx_subscription_id (subscription_id),
    INDEX idx_status (status)
);

-- Create subscription plans reference table
CREATE TABLE IF NOT EXISTS subscription_plans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    plan_name VARCHAR(50) NOT NULL UNIQUE,
    plan_code VARCHAR(20) NOT NULL UNIQUE,
    monthly_price DECIMAL(10,2) NOT NULL,
    annual_price DECIMAL(10,2) NOT NULL,
    max_users INT NOT NULL,
    storage_gb INT NOT NULL,
    features JSON,
    is_active BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default subscription plans
INSERT INTO subscription_plans (plan_name, plan_code, monthly_price, annual_price, max_users, storage_gb, features, display_order) VALUES
('Starter', 'STARTER', 4999.00, 47990.40, 3, 5, 
'["Basic inventory management", "Sales & purchase invoicing", "Basic accounting", "Email support", "5 GB storage"]', 1),
('Professional', 'PROFESSIONAL', 9999.00, 95990.40, 10, 50, 
'["Advanced inventory management", "Multi-warehouse support", "Complete accounting suite", "HR & payroll module", "Priority support", "50 GB storage", "Custom reports"]', 2),
('Enterprise', 'ENTERPRISE', 19999.00, 191990.40, 999999, 999999, 
'["Unlimited users", "All Professional features", "Multi-company support", "Advanced analytics", "API access", "24/7 dedicated support", "Unlimited storage", "Custom integrations", "On-premise deployment option"]', 3);

-- Create indexes for better performance
CREATE INDEX idx_email_verification ON users(email_verification_token);
CREATE INDEX idx_razorpay_payment ON payment_transactions(razorpay_payment_id);
CREATE INDEX idx_razorpay_order ON payment_transactions(razorpay_order_id);
