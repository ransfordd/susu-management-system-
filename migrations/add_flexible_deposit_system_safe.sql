-- Add flexible deposit system to existing Susu system (SAFE VERSION)
-- This migration checks for existing columns before adding them

-- 1. Add deposit type to clients table (only if it doesn't exist)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'clients' 
     AND COLUMN_NAME = 'deposit_type') = 0,
    'ALTER TABLE clients ADD COLUMN deposit_type ENUM(''fixed_amount'', ''flexible_amount'') DEFAULT ''fixed_amount'' AFTER daily_deposit_amount',
    'SELECT "Column deposit_type already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Add flexible amount support to susu_cycles table (only if columns don't exist)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'susu_cycles' 
     AND COLUMN_NAME = 'is_flexible') = 0,
    'ALTER TABLE susu_cycles ADD COLUMN is_flexible BOOLEAN DEFAULT FALSE AFTER status',
    'SELECT "Column is_flexible already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'susu_cycles' 
     AND COLUMN_NAME = 'average_daily_amount') = 0,
    'ALTER TABLE susu_cycles ADD COLUMN average_daily_amount DECIMAL(10,2) DEFAULT NULL AFTER is_flexible',
    'SELECT "Column average_daily_amount already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Add index for better performance on flexible cycles (only if it doesn't exist)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'susu_cycles' 
     AND INDEX_NAME = 'idx_susu_cycles_flexible') = 0,
    'CREATE INDEX idx_susu_cycles_flexible ON susu_cycles(is_flexible)',
    'SELECT "Index idx_susu_cycles_flexible already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. Update existing cycles to be fixed_amount type (backward compatibility)
UPDATE susu_cycles SET is_flexible = FALSE WHERE is_flexible IS NULL;

-- 5. Add comment to document the new system
ALTER TABLE clients COMMENT = 'Clients table with support for both fixed and flexible daily deposit amounts';
ALTER TABLE susu_cycles COMMENT = 'Susu cycles table with support for flexible daily amounts';

-- 6. Create view for flexible cycle statistics (drop first if exists)
DROP VIEW IF EXISTS flexible_cycle_stats;
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

