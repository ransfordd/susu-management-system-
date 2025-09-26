-- Update loan_applications table to include comprehensive fields
ALTER TABLE loan_applications 
ADD COLUMN IF NOT EXISTS guarantor_email VARCHAR(255),
ADD COLUMN IF NOT EXISTS guarantor_relationship VARCHAR(50),
ADD COLUMN IF NOT EXISTS guarantor_occupation VARCHAR(100),
ADD COLUMN IF NOT EXISTS guarantor_income DECIMAL(10,2),
ADD COLUMN IF NOT EXISTS guarantor_address TEXT,
ADD COLUMN IF NOT EXISTS employment_status VARCHAR(50),
ADD COLUMN IF NOT EXISTS employer_name VARCHAR(255),
ADD COLUMN IF NOT EXISTS monthly_income DECIMAL(10,2),
ADD COLUMN IF NOT EXISTS existing_loans VARCHAR(20),
ADD COLUMN IF NOT EXISTS credit_history VARCHAR(20),
ADD COLUMN IF NOT EXISTS additional_notes TEXT,
ADD COLUMN IF NOT EXISTS ghana_card_front VARCHAR(500),
ADD COLUMN IF NOT EXISTS ghana_card_back VARCHAR(500),
ADD COLUMN IF NOT EXISTS proof_of_income VARCHAR(500),
ADD COLUMN IF NOT EXISTS additional_documents VARCHAR(500);

-- Update application_status enum to include more statuses
ALTER TABLE loan_applications 
MODIFY COLUMN application_status ENUM('pending', 'under_review', 'approved', 'rejected', 'cancelled', 'requires_documents', 'incomplete') DEFAULT 'pending';

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_loan_applications_status ON loan_applications(application_status);
CREATE INDEX IF NOT EXISTS idx_loan_applications_client ON loan_applications(client_id);
CREATE INDEX IF NOT EXISTS idx_loan_applications_product ON loan_applications(loan_product_id);
CREATE INDEX IF NOT EXISTS idx_loan_applications_date ON loan_applications(applied_date);
