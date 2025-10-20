-- Add flexible deposit system to existing Susu system
-- This migration adds support for clients who want to deposit any amount daily

-- 1. Add deposit type to clients table
ALTER TABLE clients 
ADD COLUMN deposit_type ENUM('fixed_amount', 'flexible_amount') DEFAULT 'fixed_amount' AFTER daily_deposit_amount;

-- 2. Add flexible amount support to susu_cycles table
ALTER TABLE susu_cycles 
ADD COLUMN is_flexible BOOLEAN DEFAULT FALSE AFTER status,
ADD COLUMN average_daily_amount DECIMAL(10,2) DEFAULT NULL AFTER is_flexible;

-- 3. Add index for better performance on flexible cycles
CREATE INDEX idx_susu_cycles_flexible ON susu_cycles(is_flexible);

-- 4. Update existing cycles to be fixed_amount type (backward compatibility)
UPDATE susu_cycles SET is_flexible = FALSE WHERE is_flexible IS NULL;

-- 5. Add comment to document the new system
ALTER TABLE clients COMMENT = 'Clients table with support for both fixed and flexible daily deposit amounts';
ALTER TABLE susu_cycles COMMENT = 'Susu cycles table with support for flexible daily amounts';

-- 6. Create view for flexible cycle statistics (optional)
CREATE VIEW flexible_cycle_stats AS
SELECT 
    sc.id,
    sc.client_id,
    c.client_code,
    CONCAT(u.first_name, ' ', u.last_name) as client_name,
    sc.total_amount,
    COUNT(dc.id) as days_collected,
    sc.average_daily_amount,
    CASE 
        WHEN COUNT(dc.id) > 0 THEN sc.total_amount / COUNT(dc.id)
        ELSE 0 
    END as calculated_commission,
    sc.payout_amount,
    sc.status,
    sc.created_at
FROM susu_cycles sc
JOIN clients c ON sc.client_id = c.id
JOIN users u ON c.user_id = u.id
LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id AND dc.collection_status = 'collected'
WHERE sc.is_flexible = TRUE
GROUP BY sc.id, sc.client_id, c.client_code, u.first_name, u.last_name, 
         sc.total_amount, sc.average_daily_amount, sc.payout_amount, sc.status, sc.created_at;

-- 7. Add constraint to ensure flexible cycles have average_daily_amount calculated
-- This will be enforced in application logic
