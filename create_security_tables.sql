-- Security logs table for tracking login attempts and security events
CREATE TABLE IF NOT EXISTS security_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sl_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Add indexes for better performance
CREATE INDEX idx_security_logs_action ON security_logs(action);
CREATE INDEX idx_security_logs_created_at ON security_logs(created_at);
CREATE INDEX idx_security_logs_ip ON security_logs(ip_address);
CREATE INDEX idx_security_logs_user_id ON security_logs(user_id);

-- Insert default security settings if they don't exist
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('session_timeout', '30', 'Session timeout in minutes'),
('max_login_attempts', '5', 'Maximum failed login attempts before lockout'),
('lockout_duration', '30', 'Account lockout duration in minutes'),
('password_min_length', '8', 'Minimum password length'),
('require_2fa', '0', 'Require two-factor authentication (0=no, 1=yes)')
ON DUPLICATE KEY UPDATE 
    setting_value = VALUES(setting_value),
    description = VALUES(description);



