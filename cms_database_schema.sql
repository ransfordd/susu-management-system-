-- Customer Management System Database Schema
-- The Determiners CMS Database

-- Drop existing tables if they exist (in correct order due to foreign keys)
DROP TABLE IF EXISTS daily_collections;
DROP TABLE IF EXISTS susu_cycles;
DROP TABLE IF EXISTS loan_repayments;
DROP TABLE IF EXISTS loans;
DROP TABLE IF EXISTS loan_applications;
DROP TABLE IF EXISTS customer_documents;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS loan_products;
DROP TABLE IF EXISTS system_settings;

-- Users table (for authentication)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'agent', 'customer') DEFAULT 'customer',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Customers table (detailed customer information)
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    customer_number VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    date_of_birth DATE NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    marital_status ENUM('single', 'married', 'divorced', 'widowed') NOT NULL,
    nationality VARCHAR(50) DEFAULT 'Ghanaian',
    ghana_card_number VARCHAR(20) UNIQUE NOT NULL,
    phone_number VARCHAR(15) NOT NULL,
    email VARCHAR(255),
    occupation VARCHAR(100) NOT NULL,
    employer VARCHAR(100),
    monthly_income DECIMAL(12,2),
    residential_address TEXT NOT NULL,
    postal_address TEXT,
    emergency_contact_name VARCHAR(100) NOT NULL,
    emergency_contact_phone VARCHAR(15) NOT NULL,
    emergency_contact_relationship VARCHAR(50) NOT NULL,
    profile_picture VARCHAR(255),
    status ENUM('active', 'inactive', 'suspended', 'pending_verification') DEFAULT 'pending_verification',
    verification_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    verification_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_customer_number (customer_number),
    INDEX idx_ghana_card (ghana_card_number),
    INDEX idx_phone (phone_number)
);

-- Customer documents table
CREATE TABLE customer_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    document_type ENUM('ghana_card', 'proof_of_income', 'proof_of_address', 'guarantor_id', 'business_registration', 'financial_statement', 'other') NOT NULL,
    document_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT,
    mime_type VARCHAR(100),
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    notes TEXT,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

-- Loan products table
CREATE TABLE loan_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(100) NOT NULL,
    product_code VARCHAR(20) UNIQUE NOT NULL,
    description TEXT,
    min_amount DECIMAL(12,2) NOT NULL,
    max_amount DECIMAL(12,2) NOT NULL,
    interest_rate DECIMAL(5,2) NOT NULL, -- Annual percentage rate
    min_term_months INT NOT NULL,
    max_term_months INT NOT NULL,
    processing_fee DECIMAL(12,2) DEFAULT 0,
    late_payment_fee DECIMAL(12,2) DEFAULT 0,
    requires_guarantor BOOLEAN DEFAULT TRUE,
    requires_collateral BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Loan applications table
CREATE TABLE loan_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_number VARCHAR(20) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    product_id INT NOT NULL,
    requested_amount DECIMAL(12,2) NOT NULL,
    requested_term_months INT NOT NULL,
    purpose TEXT NOT NULL,
    guarantor_name VARCHAR(100),
    guarantor_phone VARCHAR(15),
    guarantor_relationship VARCHAR(50),
    guarantor_address TEXT,
    guarantor_ghana_card VARCHAR(20),
    monthly_income DECIMAL(12,2),
    monthly_expenses DECIMAL(12,2),
    other_loans DECIMAL(12,2) DEFAULT 0,
    application_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'under_review', 'approved', 'rejected', 'disbursed') DEFAULT 'pending',
    review_notes TEXT,
    approved_amount DECIMAL(12,2),
    approved_term_months INT,
    approved_interest_rate DECIMAL(5,2),
    approved_by INT,
    approval_date TIMESTAMP NULL,
    rejection_reason TEXT,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES loan_products(id),
    FOREIGN KEY (approved_by) REFERENCES users(id),
    INDEX idx_application_number (application_number),
    INDEX idx_status (status)
);

-- Loans table (approved and disbursed loans)
CREATE TABLE loans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loan_number VARCHAR(20) UNIQUE NOT NULL,
    application_id INT NOT NULL,
    customer_id INT NOT NULL,
    product_id INT NOT NULL,
    principal_amount DECIMAL(12,2) NOT NULL,
    interest_rate DECIMAL(5,2) NOT NULL,
    term_months INT NOT NULL,
    monthly_payment DECIMAL(12,2) NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL,
    disbursement_date DATE NOT NULL,
    maturity_date DATE NOT NULL,
    current_balance DECIMAL(12,2) NOT NULL,
    status ENUM('active', 'completed', 'defaulted', 'written_off') DEFAULT 'active',
    next_payment_date DATE,
    days_overdue INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES loan_applications(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES loan_products(id),
    INDEX idx_loan_number (loan_number),
    INDEX idx_customer_id (customer_id),
    INDEX idx_status (status)
);

-- Loan repayments table
CREATE TABLE loan_repayments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loan_id INT NOT NULL,
    payment_amount DECIMAL(12,2) NOT NULL,
    principal_amount DECIMAL(12,2) NOT NULL,
    interest_amount DECIMAL(12,2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method ENUM('cash', 'mobile_money', 'bank_transfer', 'cheque') NOT NULL,
    transaction_reference VARCHAR(100),
    collected_by INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
    FOREIGN KEY (collected_by) REFERENCES users(id),
    INDEX idx_loan_id (loan_id),
    INDEX idx_payment_date (payment_date)
);

-- Susu cycles table
CREATE TABLE susu_cycles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cycle_number VARCHAR(20) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    cycle_type ENUM('daily', 'weekly', 'monthly') DEFAULT 'daily',
    daily_amount DECIMAL(10,2) NOT NULL,
    total_cycle_amount DECIMAL(12,2) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    completion_date DATE NULL,
    payout_amount DECIMAL(12,2),
    agent_id INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (agent_id) REFERENCES users(id),
    INDEX idx_cycle_number (cycle_number),
    INDEX idx_customer_id (customer_id),
    INDEX idx_status (status)
);

-- Daily collections table
CREATE TABLE daily_collections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    susu_cycle_id INT NOT NULL,
    collection_date DATE NOT NULL,
    collection_time TIME,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'mobile_money', 'bank_transfer') DEFAULT 'cash',
    transaction_reference VARCHAR(100),
    collection_status ENUM('collected', 'missed', 'pending') DEFAULT 'collected',
    collected_by INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (susu_cycle_id) REFERENCES susu_cycles(id) ON DELETE CASCADE,
    FOREIGN KEY (collected_by) REFERENCES users(id),
    INDEX idx_cycle_id (susu_cycle_id),
    INDEX idx_collection_date (collection_date),
    UNIQUE KEY unique_daily_collection (susu_cycle_id, collection_date)
);

-- System settings table
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id)
);

-- Insert default loan products
INSERT INTO loan_products (product_name, product_code, description, min_amount, max_amount, interest_rate, min_term_months, max_term_months, processing_fee, requires_guarantor) VALUES
('Personal Loan', 'PL001', 'Quick personal loans for immediate needs', 1000.00, 50000.00, 20.00, 3, 24, 100.00, TRUE),
('Business Loan', 'BL001', 'Working capital loans for small businesses', 5000.00, 200000.00, 18.00, 6, 36, 200.00, TRUE),
('Emergency Loan', 'EL001', 'Fast emergency loans for urgent situations', 500.00, 10000.00, 25.00, 1, 12, 50.00, FALSE),
('Home Improvement Loan', 'HIL001', 'Loans for home renovations and improvements', 10000.00, 100000.00, 15.00, 12, 60, 300.00, TRUE),
('Education Loan', 'EDU001', 'Loans for educational expenses', 2000.00, 30000.00, 12.00, 6, 48, 150.00, TRUE);

-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value, setting_type, description) VALUES
('company_name', 'The Determiners', 'string', 'Company name for receipts and documents'),
('company_address', '232 Nii Kwashiefio Avenue, Abofu - Achimota, Ghana', 'string', 'Company address'),
('company_phone', '+233 302 123 456', 'string', 'Company phone number'),
('company_email', 'info@thedeterminers.com', 'string', 'Company email address'),
('default_susu_duration', '31', 'number', 'Default Susu cycle duration in days'),
('max_loan_amount_multiplier', '3', 'number', 'Maximum loan amount as multiple of monthly income'),
('late_payment_grace_days', '7', 'number', 'Grace period for late payments in days'),
('auto_approve_loans_under', '5000', 'number', 'Auto-approve loans under this amount'),
('maintenance_mode', 'false', 'boolean', 'System maintenance mode'),
('registration_enabled', 'true', 'boolean', 'Allow new customer registrations');

-- Create admin user (password: admin123 - change this!)
INSERT INTO users (email, password_hash, role, status) VALUES
('admin@thedeterminers.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');

-- Create indexes for better performance
CREATE INDEX idx_customers_status ON customers(status);
CREATE INDEX idx_customers_verification ON customers(verification_status);
CREATE INDEX idx_loans_next_payment ON loans(next_payment_date);
CREATE INDEX idx_loans_overdue ON loans(days_overdue);
CREATE INDEX idx_collections_status ON daily_collections(collection_status);
CREATE INDEX idx_applications_date ON loan_applications(application_date);
