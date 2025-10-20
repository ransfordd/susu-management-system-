-- Migration: Create savings_accounts table
-- Created: 2024-12-19
-- Purpose: Store client savings account balances

CREATE TABLE IF NOT EXISTS savings_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    balance DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    UNIQUE KEY unique_client_savings (client_id)
);

-- Create savings_transactions table for transaction history
CREATE TABLE IF NOT EXISTS savings_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    savings_account_id INT NOT NULL,
    transaction_type ENUM('deposit', 'withdrawal', 'cycle_payment', 'loan_payment', 'auto_deduction') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    balance_after DECIMAL(10,2) NOT NULL,
    source ENUM('overpayment', 'manual_deposit', 'cycle_completion', 'loan_settlement', 'withdrawal_request') NOT NULL,
    purpose ENUM('savings_deposit', 'cycle_payment', 'loan_payment', 'withdrawal', 'auto_loan_deduction') NOT NULL,
    reference_transaction_id INT NULL,
    reference_type ENUM('daily_collection', 'manual_transaction', 'loan_payment', 'susu_cycle') NULL,
    description TEXT,
    processed_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (savings_account_id) REFERENCES savings_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_savings_account (savings_account_id),
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_created_at (created_at)
);

-- Create loan_deduction_notifications table for auto-deduction notifications
CREATE TABLE IF NOT EXISTS loan_deduction_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    loan_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    notification_sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    auto_deduction_at TIMESTAMP NULL,
    status ENUM('pending', 'approved', 'auto_deducted', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
    INDEX idx_client (client_id),
    INDEX idx_status (status),
    INDEX idx_auto_deduction_at (auto_deduction_at)
);
