-- Create user_activity table to track user actions
USE `thedeterminers_susu-loan`;

CREATE TABLE IF NOT EXISTS user_activity (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    activity_type ENUM('login', 'logout', 'password_change', 'profile_update', 'payment_made', 'loan_application', 'loan_approval', 'loan_rejection', 'susu_collection', 'cycle_completion', 'agent_assignment', 'client_registration', 'agent_registration') NOT NULL,
    activity_description TEXT NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    reference_id INT NULL,
    reference_type VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_activity_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Create indexes for better performance
CREATE INDEX idx_user_activity_user_created ON user_activity(user_id, created_at);
CREATE INDEX idx_user_activity_type_created ON user_activity(activity_type, created_at);
CREATE INDEX idx_user_activity_created ON user_activity(created_at);

-- Insert some sample activities
INSERT INTO user_activity (user_id, activity_type, activity_description, ip_address, reference_id, reference_type) VALUES
(1, 'login', 'Admin logged into the system', '127.0.0.1', NULL, NULL),
(1, 'password_change', 'Admin changed their password', '127.0.0.1', NULL, NULL),
(1, 'loan_approval', 'Admin approved a loan application', '127.0.0.1', 1, 'loan_application'),
(1, 'agent_registration', 'Admin registered a new agent', '127.0.0.1', 1, 'agent');

SELECT 'User activity table created successfully!' as status;

