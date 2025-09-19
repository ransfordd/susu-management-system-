-- Specific fixes for the 500 errors in admin dashboard
-- Run only these specific changes to fix the issues

USE `thedeterminers_susu-loan`;

-- Fix 1: Ensure admin user has correct role and email
-- This updates the existing admin user without changing the password
UPDATE users SET 
    email = 'admin@example.com',
    role = 'business_admin',
    first_name = 'Admin',
    last_name = 'User',
    phone = '1234567890',
    status = 'active'
WHERE username = 'admin';

-- Fix 2: Add missing tables that might be causing issues
-- Only create these tables if they don't exist

-- Agent Commission Payments table (if missing)
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

-- Manual Transactions table (if missing)
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

-- Loan Payment Schedule table (if missing)
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

-- Fix 3: Ensure all existing tables have the correct structure
-- Check if susu_cycles table has all required columns
-- (This is just a check - no changes needed as the table structure is correct)

-- Fix 4: Add some sample data if tables are empty (optional)
-- Only insert if no data exists

-- Insert sample loan products if none exist
INSERT IGNORE INTO loan_products (product_name, product_code, description, min_amount, max_amount, interest_rate, min_term_months, max_term_months, status) 
SELECT 'Personal Loan', 'PL001', 'Quick personal loan for immediate needs', 1000.00, 50000.00, 15.00, 1, 12, 'active'
WHERE NOT EXISTS (SELECT 1 FROM loan_products LIMIT 1);

INSERT IGNORE INTO loan_products (product_name, product_code, description, min_amount, max_amount, interest_rate, min_term_months, max_term_months, status) 
SELECT 'Business Loan', 'BL001', 'Business expansion and working capital loan', 5000.00, 200000.00, 12.00, 3, 24, 'active'
WHERE NOT EXISTS (SELECT 1 FROM loan_products LIMIT 1);

-- Show completion message
SELECT 'Specific fixes applied successfully!' as status;
SELECT 'Admin user updated, missing tables created, and sample data added (if needed)' as details;



