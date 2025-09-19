-- Create Account Types System
-- This script creates account types and savings wallets for clients

-- Account Types Table
CREATE TABLE IF NOT EXISTS account_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type_name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    interest_rate DECIMAL(5,2) DEFAULT 0.00,
    minimum_balance DECIMAL(10,2) DEFAULT 0.00,
    withdrawal_limit DECIMAL(10,2) DEFAULT NULL,
    daily_transaction_limit DECIMAL(10,2) DEFAULT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Client Accounts Table
CREATE TABLE IF NOT EXISTS client_accounts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    account_type_id INT NOT NULL,
    account_number VARCHAR(20) UNIQUE NOT NULL,
    account_name VARCHAR(100) NOT NULL,
    current_balance DECIMAL(10,2) DEFAULT 0.00,
    available_balance DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('active', 'suspended', 'closed') DEFAULT 'active',
    opened_date DATE NOT NULL,
    closed_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ca_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    CONSTRAINT fk_ca_account_type FOREIGN KEY (account_type_id) REFERENCES account_types(id),
    UNIQUE KEY uk_client_account_type (client_id, account_type_id)
) ENGINE=InnoDB;

-- Account Transactions Table
CREATE TABLE IF NOT EXISTS account_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    account_id INT NOT NULL,
    transaction_type ENUM('deposit', 'withdrawal', 'transfer_in', 'transfer_out', 'interest', 'fee', 'loan_payment') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    balance_before DECIMAL(10,2) NOT NULL,
    balance_after DECIMAL(10,2) NOT NULL,
    description TEXT NOT NULL,
    reference_number VARCHAR(50) UNIQUE NOT NULL,
    related_transaction_id INT NULL,
    processed_by INT NULL,
    transaction_date DATE NOT NULL,
    transaction_time TIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_at_account FOREIGN KEY (account_id) REFERENCES client_accounts(id) ON DELETE CASCADE,
    CONSTRAINT fk_at_related FOREIGN KEY (related_transaction_id) REFERENCES account_transactions(id),
    CONSTRAINT fk_at_processed_by FOREIGN KEY (processed_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- Auto Transfer Settings Table
CREATE TABLE IF NOT EXISTS auto_transfer_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    susu_to_savings BOOLEAN DEFAULT TRUE,
    savings_to_loan BOOLEAN DEFAULT TRUE,
    minimum_savings_for_loan_repayment DECIMAL(10,2) DEFAULT 50.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ats_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Insert default account types
INSERT INTO account_types (type_name, description, interest_rate, minimum_balance, withdrawal_limit, daily_transaction_limit) VALUES
('Susu Account', 'Traditional Susu collection account', 0.00, 0.00, NULL, NULL),
('Savings Account', 'Personal savings account with interest', 2.50, 10.00, 1000.00, 500.00),
('Current Account', 'Daily transaction account', 0.50, 5.00, 2000.00, 1000.00),
('Investment Account', 'Long-term investment account', 5.00, 100.00, 5000.00, 2000.00)
ON DUPLICATE KEY UPDATE 
    description = VALUES(description),
    interest_rate = VALUES(interest_rate),
    minimum_balance = VALUES(minimum_balance),
    withdrawal_limit = VALUES(withdrawal_limit),
    daily_transaction_limit = VALUES(daily_transaction_limit);

-- Create savings accounts for all existing clients
INSERT INTO client_accounts (client_id, account_type_id, account_number, account_name, current_balance, available_balance, opened_date)
SELECT 
    c.id as client_id,
    at.id as account_type_id,
    CONCAT('SAV', LPAD(c.id, 6, '0')) as account_number,
    CONCAT('Savings Account - ', u.first_name, ' ', u.last_name) as account_name,
    0.00 as current_balance,
    0.00 as available_balance,
    CURDATE() as opened_date
FROM clients c
JOIN users u ON c.user_id = u.id
JOIN account_types at ON at.type_name = 'Savings Account'
WHERE NOT EXISTS (
    SELECT 1 FROM client_accounts ca 
    WHERE ca.client_id = c.id AND ca.account_type_id = at.id
);

-- Create auto transfer settings for all clients
INSERT INTO auto_transfer_settings (client_id, susu_to_savings, savings_to_loan, minimum_savings_for_loan_repayment)
SELECT 
    c.id as client_id,
    TRUE as susu_to_savings,
    TRUE as savings_to_loan,
    50.00 as minimum_savings_for_loan_repayment
FROM clients c
WHERE NOT EXISTS (
    SELECT 1 FROM auto_transfer_settings ats WHERE ats.client_id = c.id
);

-- Show results
SELECT 'Account Types Created:' as info;
SELECT * FROM account_types;

SELECT 'Client Savings Accounts Created:' as info;
SELECT ca.id, ca.account_number, ca.account_name, ca.current_balance, 
       CONCAT(c.first_name, ' ', c.last_name) as client_name
FROM client_accounts ca
JOIN clients cl ON ca.client_id = cl.id
JOIN users c ON cl.user_id = c.id
JOIN account_types at ON ca.account_type_id = at.id
WHERE at.type_name = 'Savings Account'
ORDER BY ca.id;

SELECT 'Auto Transfer Settings Created:' as info;
SELECT ats.id, CONCAT(c.first_name, ' ', c.last_name) as client_name,
       ats.susu_to_savings, ats.savings_to_loan, ats.minimum_savings_for_loan_repayment
FROM auto_transfer_settings ats
JOIN clients cl ON ats.client_id = cl.id
JOIN users c ON cl.user_id = c.id
ORDER BY ats.id;




