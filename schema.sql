-- Enhanced Susu System with Loan Management - MySQL 8.0 Schema
SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE DATABASE IF NOT EXISTS `thedeterminers_susu-loan` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `thedeterminers_susu-loan`;

-- Users
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

-- Agents
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

-- Clients
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
    credit_score INT DEFAULT 0,
    risk_classification ENUM('low', 'medium', 'high') DEFAULT 'medium',
    registration_date DATE NOT NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_clients_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_clients_agent FOREIGN KEY (agent_id) REFERENCES agents(id)
) ENGINE=InnoDB;

-- Susu Cycles
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

-- Daily Collections
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

-- Loan Products
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

-- Loan Applications
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

-- Loans
CREATE TABLE IF NOT EXISTS loans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    loan_number VARCHAR(50) UNIQUE NOT NULL,
    application_id INT NOT NULL,
    client_id INT NOT NULL,
    loan_product_id INT NOT NULL,
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
    last_payment_date DATE,
    next_payment_date DATE,
    loan_status ENUM('active', 'paid_off', 'defaulted', 'written_off') DEFAULT 'active',
    disbursed_by INT NOT NULL,
    disbursement_method ENUM('mobile_money', 'bank_transfer', 'cash', 'susu_offset'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_loans_application FOREIGN KEY (application_id) REFERENCES loan_applications(id),
    CONSTRAINT fk_loans_client FOREIGN KEY (client_id) REFERENCES clients(id),
    CONSTRAINT fk_loans_product FOREIGN KEY (loan_product_id) REFERENCES loan_products(id),
    CONSTRAINT fk_loans_disbursed_by FOREIGN KEY (disbursed_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- Loan Payments
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
    payment_status ENUM('pending', 'paid', 'partial', 'overdue') DEFAULT 'pending',
    days_overdue INT DEFAULT 0,
    penalty_amount DECIMAL(10,2) DEFAULT 0,
    collected_by INT,
    payment_method ENUM('cash', 'mobile_money', 'bank_transfer', 'susu_deduction'),
    receipt_number VARCHAR(50),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_lp_loan FOREIGN KEY (loan_id) REFERENCES loans(id),
    CONSTRAINT fk_lp_agent FOREIGN KEY (collected_by) REFERENCES agents(id)
) ENGINE=InnoDB;

-- System Settings
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

-- Loan Payment Schedule
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

-- Holidays Calendar
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

-- Agent Commission Payments
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

-- Manual Transactions
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

-- Notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    notification_type ENUM('payment_due', 'payment_overdue', 'loan_approved', 'loan_rejected', 'cycle_completed', 'system_alert') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    related_id INT,
    related_type ENUM('susu_cycle', 'loan', 'payment', 'application'),
    is_read BOOLEAN DEFAULT FALSE,
    sent_via ENUM('system', 'sms', 'email') DEFAULT 'system',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- Helpful indexes
CREATE INDEX idx_clients_agent ON clients(agent_id);
CREATE INDEX idx_cycles_client_status ON susu_cycles(client_id, status);
CREATE INDEX idx_dc_cycle_status ON daily_collections(susu_cycle_id, collection_status);
CREATE INDEX idx_loans_client_status ON loans(client_id, loan_status);
CREATE INDEX idx_lp_loan_status ON loan_payments(loan_id, payment_status);

-- Seed minimal settings
INSERT INTO system_settings (setting_key, setting_value, setting_type, category, description)
VALUES
('default_timezone', 'Africa/Accra', 'string', 'system', 'Default timezone'),
('susu_days', '31', 'number', 'susu', 'Susu cycle days'),
('susu_payout_days', '30', 'number', 'susu', 'Payout days'),
('penalty_rate_percent_per_day', '0.5', 'number', 'loans', 'Daily penalty rate for overdue');