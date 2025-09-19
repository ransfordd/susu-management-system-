-- Database Update Script for Susu System
-- Run this script to ensure all tables and data are properly set up

-- First, let's make sure we're using the correct database
USE `thedeterminers_susu-loan`;

-- Check if tables exist and create them if they don't
-- This will ensure all required tables are present

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('business_admin', 'agent', 'client') NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT,
    id_number VARCHAR(50),
    profile_image VARCHAR(255),
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Agents table
CREATE TABLE IF NOT EXISTS agents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    agent_code VARCHAR(20) UNIQUE NOT NULL,
    hire_date DATE NOT NULL,
    commission_rate DECIMAL(5,2) DEFAULT 5.00,
    territory VARCHAR(100),
    supervisor_id INT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_agents_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_agents_supervisor FOREIGN KEY (supervisor_id) REFERENCES agents(id)
) ENGINE=InnoDB;

-- Clients table
CREATE TABLE IF NOT EXISTS clients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    client_code VARCHAR(20) UNIQUE NOT NULL,
    agent_id INT NOT NULL,
    daily_deposit_amount DECIMAL(10,2) NOT NULL,
    preferred_collection_time TIME,
    collection_location TEXT,
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(20),
    emergency_contact_relationship VARCHAR(50),
    employment_status ENUM('employed', 'self_employed', 'unemployed', 'student') DEFAULT 'employed',
    monthly_income DECIMAL(10,2),
    employer_name VARCHAR(100),
    employer_phone VARCHAR(20),
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_clients_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_clients_agent FOREIGN KEY (agent_id) REFERENCES agents(id)
) ENGINE=InnoDB;

-- Susu Cycles table
CREATE TABLE IF NOT EXISTS susu_cycles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    cycle_number INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    daily_amount DECIMAL(10,2) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    payout_amount DECIMAL(10,2) NOT NULL,
    agent_fee DECIMAL(10,2) NOT NULL,
    status ENUM('active', 'completed', 'defaulted', 'cancelled') DEFAULT 'active',
    payout_date DATE,
    completion_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cycles_client FOREIGN KEY (client_id) REFERENCES clients(id),
    CONSTRAINT uq_client_cycle UNIQUE (client_id, cycle_number)
) ENGINE=InnoDB;

-- Daily Collections table
CREATE TABLE IF NOT EXISTS daily_collections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    susu_cycle_id INT NOT NULL,
    collection_date DATE NOT NULL,
    day_number INT NOT NULL,
    expected_amount DECIMAL(10,2) NOT NULL,
    collected_amount DECIMAL(10,2) DEFAULT 0,
    collection_status ENUM('pending', 'collected', 'missed', 'partial') DEFAULT 'pending',
    collection_time TIMESTAMP NULL,
    collected_by INT,
    notes TEXT,
    receipt_number VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_dc_cycle FOREIGN KEY (susu_cycle_id) REFERENCES susu_cycles(id),
    CONSTRAINT fk_dc_agent FOREIGN KEY (collected_by) REFERENCES agents(id),
    CONSTRAINT uq_cycle_day UNIQUE (susu_cycle_id, day_number)
) ENGINE=InnoDB;

-- Loan Products table
CREATE TABLE IF NOT EXISTS loan_products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_name VARCHAR(100) NOT NULL,
    product_code VARCHAR(20) UNIQUE NOT NULL,
    description TEXT,
    min_amount DECIMAL(10,2) NOT NULL,
    max_amount DECIMAL(10,2) NOT NULL,
    interest_rate DECIMAL(5,2) NOT NULL,
    interest_type ENUM('flat', 'reducing_balance') DEFAULT 'flat',
    min_term_months INT DEFAULT 1,
    max_term_months INT DEFAULT 12,
    processing_fee_rate DECIMAL(5,2) DEFAULT 0,
    eligibility_criteria JSON,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Loan Applications table
CREATE TABLE IF NOT EXISTS loan_applications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    application_number VARCHAR(50) UNIQUE NOT NULL,
    client_id INT NOT NULL,
    loan_product_id INT NOT NULL,
    requested_amount DECIMAL(10,2) NOT NULL,
    requested_term_months INT NOT NULL,
    purpose TEXT NOT NULL,
    guarantor_name VARCHAR(100),
    guarantor_phone VARCHAR(20),
    guarantor_id_number VARCHAR(50),
    agent_recommendation TEXT,
    agent_score INT,
    auto_eligibility_score INT,
    application_status ENUM('pending', 'under_review', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    applied_date DATE NOT NULL,
    reviewed_by INT,
    review_date DATE,
    review_notes TEXT,
    approved_amount DECIMAL(10,2),
    approved_term_months INT,
    approval_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_la_client FOREIGN KEY (client_id) REFERENCES clients(id),
    CONSTRAINT fk_la_product FOREIGN KEY (loan_product_id) REFERENCES loan_products(id),
    CONSTRAINT fk_la_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- Loans table
CREATE TABLE IF NOT EXISTS loans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    loan_number VARCHAR(50) UNIQUE NOT NULL,
    client_id INT NOT NULL,
    loan_product_id INT NOT NULL,
    application_id INT,
    principal_amount DECIMAL(10,2) NOT NULL,
    interest_rate DECIMAL(5,2) NOT NULL,
    term_months INT NOT NULL,
    monthly_payment DECIMAL(10,2) NOT NULL,
    total_repayment_amount DECIMAL(10,2) NOT NULL,
    disbursement_date DATE NOT NULL,
    maturity_date DATE NOT NULL,
    current_balance DECIMAL(10,2) NOT NULL,
    total_paid DECIMAL(10,2) DEFAULT 0,
    payments_made INT DEFAULT 0,
    loan_status ENUM('active', 'completed', 'defaulted', 'cancelled') DEFAULT 'active',
    disbursed_by INT NOT NULL,
    disbursement_method ENUM('cash', 'bank_transfer', 'mobile_money') DEFAULT 'cash',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_loans_client FOREIGN KEY (client_id) REFERENCES clients(id),
    CONSTRAINT fk_loans_product FOREIGN KEY (loan_product_id) REFERENCES loan_products(id),
    CONSTRAINT fk_loans_application FOREIGN KEY (application_id) REFERENCES loan_applications(id),
    CONSTRAINT fk_loans_disbursed_by FOREIGN KEY (disbursed_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- Loan Payments table
CREATE TABLE IF NOT EXISTS loan_payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    loan_id INT NOT NULL,
    payment_number INT NOT NULL,
    due_date DATE NOT NULL,
    principal_amount DECIMAL(10,2) NOT NULL,
    interest_amount DECIMAL(10,2) NOT NULL,
    total_due DECIMAL(10,2) NOT NULL,
    amount_paid DECIMAL(10,2) DEFAULT 0,
    payment_date DATE,
    payment_status ENUM('pending', 'paid', 'overdue', 'partial') DEFAULT 'pending',
    receipt_number VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payments_loan FOREIGN KEY (loan_id) REFERENCES loans(id)
) ENGINE=InnoDB;

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    notification_type ENUM('info', 'warning', 'success', 'error') NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- System Settings table
CREATE TABLE IF NOT EXISTS system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    category VARCHAR(50),
    description TEXT,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_settings_user FOREIGN KEY (updated_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- Loan Payment Schedule table
CREATE TABLE IF NOT EXISTS loan_payment_schedule (
    id INT PRIMARY KEY AUTO_INCREMENT,
    loan_id INT NOT NULL,
    payment_number INT NOT NULL,
    payment_date DATE NOT NULL,
    monthly_payment DECIMAL(10,2) NOT NULL,
    principal_payment DECIMAL(10,2) NOT NULL,
    interest_payment DECIMAL(10,2) NOT NULL,
    remaining_balance DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending', 'paid', 'overdue') DEFAULT 'pending',
    paid_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_schedule_loan FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Holidays Calendar table
CREATE TABLE IF NOT EXISTS holidays_calendar (
    id INT PRIMARY KEY AUTO_INCREMENT,
    holiday_date DATE NOT NULL,
    holiday_name VARCHAR(100) NOT NULL,
    holiday_type ENUM('national', 'regional', 'custom') DEFAULT 'national',
    is_recurring BOOLEAN DEFAULT FALSE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_holidays_user FOREIGN KEY (created_by) REFERENCES users(id),
    CONSTRAINT uq_holiday_date UNIQUE (holiday_date)
) ENGINE=InnoDB;

-- Agent Commission Payments table
CREATE TABLE IF NOT EXISTS agent_commission_payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    agent_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'bank_transfer', 'mobile_money') DEFAULT 'cash',
    notes TEXT,
    processed_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_acp_agent FOREIGN KEY (agent_id) REFERENCES agents(id),
    CONSTRAINT fk_acp_processed_by FOREIGN KEY (processed_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- Manual Transactions table
CREATE TABLE IF NOT EXISTS manual_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    transaction_type ENUM('deposit', 'withdrawal') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT NOT NULL,
    reference VARCHAR(50) UNIQUE NOT NULL,
    processed_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mt_client FOREIGN KEY (client_id) REFERENCES clients(id),
    CONSTRAINT fk_mt_processed_by FOREIGN KEY (processed_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- Update existing admin user or insert if doesn't exist
-- First, check if admin user exists and update if needed
UPDATE users SET 
    email = 'admin@example.com',
    role = 'business_admin',
    first_name = 'Admin',
    last_name = 'User',
    phone = '1234567890',
    status = 'active'
WHERE username = 'admin';

-- Insert admin user if it doesn't exist (this will only run if no admin user exists)
INSERT IGNORE INTO users (username, email, password_hash, role, first_name, last_name, phone, status) 
VALUES ('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'business_admin', 'Admin', 'User', '1234567890', 'active');

-- Insert some default loan products if they don't exist
INSERT IGNORE INTO loan_products (product_name, product_code, description, min_amount, max_amount, interest_rate, min_term_months, max_term_months, status) VALUES
('Personal Loan', 'PL001', 'Quick personal loan for immediate needs', 1000.00, 50000.00, 15.00, 1, 12, 'active'),
('Business Loan', 'BL001', 'Business expansion and working capital loan', 5000.00, 200000.00, 12.00, 3, 24, 'active'),
('Emergency Loan', 'EL001', 'Emergency loan for urgent situations', 500.00, 10000.00, 20.00, 1, 6, 'active'),
('Education Loan', 'ED001', 'Education and training loan', 2000.00, 100000.00, 10.00, 6, 36, 'active');

-- Insert some default system settings if they don't exist
INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_type, category, description) VALUES
('company_name', 'The Determiners Susu System', 'string', 'general', 'Company name'),
('company_phone', '+233-XXX-XXXX', 'string', 'general', 'Company phone number'),
('company_email', 'info@thedeterminers.com', 'string', 'general', 'Company email'),
('default_interest_rate', '15.00', 'number', 'loans', 'Default interest rate for loans'),
('max_loan_amount', '200000.00', 'number', 'loans', 'Maximum loan amount'),
('min_loan_amount', '500.00', 'number', 'loans', 'Minimum loan amount'),
('susu_cycle_days', '31', 'number', 'susu', 'Number of days in a Susu cycle'),
('agent_commission_rate', '5.00', 'number', 'agents', 'Default agent commission rate');

-- Show completion message
SELECT 'Database update completed successfully!' as status;
