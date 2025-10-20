-- Fix savings_transactions table structure
-- Created: 2024-12-19

-- Check if client_id column exists, if not add it
ALTER TABLE savings_transactions 
ADD COLUMN IF NOT EXISTS client_id INT NOT NULL AFTER savings_account_id;

-- Add foreign key constraint if it doesn't exist
ALTER TABLE savings_transactions 
ADD CONSTRAINT IF NOT EXISTS fk_savings_transactions_client_id 
FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE;

-- Update existing records to have client_id (if any)
UPDATE savings_transactions st
JOIN savings_accounts sa ON st.savings_account_id = sa.id
SET st.client_id = sa.client_id
WHERE st.client_id IS NULL OR st.client_id = 0;
