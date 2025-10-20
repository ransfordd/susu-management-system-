-- Emergency withdrawal requests table
CREATE TABLE emergency_withdrawal_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    susu_cycle_id INT NOT NULL,
    requested_amount DECIMAL(10,2) NOT NULL,
    available_amount DECIMAL(10,2) NOT NULL,
    days_collected INT NOT NULL,
    commission_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
    requested_by INT NOT NULL, -- user_id who requested
    approved_by INT NULL, -- user_id who approved
    rejection_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (client_id) REFERENCES clients(id),
    FOREIGN KEY (susu_cycle_id) REFERENCES susu_cycles(id),
    FOREIGN KEY (requested_by) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
);

-- Emergency withdrawal transactions table
CREATE TABLE emergency_withdrawal_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    request_id INT NOT NULL,
    client_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    commission_deducted DECIMAL(10,2) NOT NULL,
    net_amount DECIMAL(10,2) NOT NULL,
    transaction_type ENUM('emergency_withdrawal') DEFAULT 'emergency_withdrawal',
    reference VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES emergency_withdrawal_requests(id),
    FOREIGN KEY (client_id) REFERENCES clients(id)
);

-- Add emergency_withdrawal to manual_transactions transaction_type enum
ALTER TABLE manual_transactions 
MODIFY COLUMN transaction_type ENUM('deposit', 'withdrawal', 'savings_withdrawal', 'emergency_withdrawal') NOT NULL;

