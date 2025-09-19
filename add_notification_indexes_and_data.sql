-- Add indexes and sample data to notifications table
-- Run this only if the table already exists

-- Check if indexes exist and create them if they don't
-- For idx_notifications_user_read
SELECT COUNT(*) INTO @index_exists FROM information_schema.statistics 
WHERE table_schema = DATABASE() 
AND table_name = 'notifications' 
AND index_name = 'idx_notifications_user_read';

SET @sql = IF(@index_exists = 0, 
    'CREATE INDEX idx_notifications_user_read ON notifications(user_id, is_read)', 
    'SELECT "Index idx_notifications_user_read already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- For idx_notifications_created
SELECT COUNT(*) INTO @index_exists FROM information_schema.statistics 
WHERE table_schema = DATABASE() 
AND table_name = 'notifications' 
AND index_name = 'idx_notifications_created';

SET @sql = IF(@index_exists = 0, 
    'CREATE INDEX idx_notifications_created ON notifications(created_at)', 
    'SELECT "Index idx_notifications_created already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Insert sample notifications (only if they don't exist)
INSERT IGNORE INTO notifications (user_id, notification_type, title, message, reference_id, reference_type) VALUES
(1, 'system_alert', 'System Update', 'Welcome to the enhanced Susu system!', NULL, NULL),
(1, 'loan_application', 'New Loan Application', 'A new loan application has been submitted and requires review.', NULL, 'loan_application'),
(1, 'agent_assignment', 'Agent Assignment', 'New agent has been registered and requires approval.', NULL, 'agent');

SELECT 'Notifications setup completed successfully!' as status;
