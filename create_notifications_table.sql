-- Create notifications table for proper notification system
USE `thedeterminers_susu-loan`;

CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    notification_type ENUM('loan_application', 'loan_approval', 'loan_rejection', 'agent_assignment', 'collection_reminder', 'payment_confirmation', 'cycle_completion', 'system_alert') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    reference_id INT NULL,
    reference_type VARCHAR(50) NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Note: Run the separate script 'add_notification_indexes_and_data.sql' 
-- to add indexes and sample data after creating the table

SELECT 'Notifications table created successfully! Run add_notification_indexes_and_data.sql next.' as status;
