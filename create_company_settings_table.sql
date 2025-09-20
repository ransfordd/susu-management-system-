-- Create company_settings table
CREATE TABLE IF NOT EXISTS company_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default company settings
INSERT INTO company_settings (setting_key, setting_value) VALUES
('company_name', 'Susu Financial Services'),
('company_address', '123 Main Street'),
('company_city', 'Accra'),
('company_region', 'greater_accra'),
('company_postal_code', 'GA-123-4567'),
('company_country', 'Ghana'),
('company_phone', '+233 24 123 4567'),
('company_email', 'info@susufinancial.com'),
('company_website', 'https://www.susufinancial.com'),
('company_registration_number', 'RC123456789'),
('company_tax_id', 'C0001234567'),
('company_bank_name', 'gcb_bank'),
('company_account_number', '1234567890'),
('company_branch_code', '001'),
('company_swift_code', 'GCBAGHAC'),
('company_currency', 'GHS'),
('company_timezone', 'Africa/Accra'),
('company_logo', '/assets/images/company/company-logo.png'),
('company_footer_text', 'Thank you for choosing Susu Financial Services. For inquiries, call +233 24 123 4567'),
('company_terms_conditions', 'Terms and conditions apply. Please read our terms of service for more information.'),
('company_privacy_policy', 'We respect your privacy and protect your personal information in accordance with our privacy policy.')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- Create index for better performance
CREATE INDEX idx_company_settings_key ON company_settings(setting_key);

SELECT 'Company settings table created successfully!' as status;
