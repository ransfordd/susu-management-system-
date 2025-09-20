-- Apply database schema fixes
USE `thedeterminers_susu-loan`;

-- Fix susu_cycles table - add missing columns
ALTER TABLE susu_cycles 
ADD COLUMN IF NOT EXISTS day_number INT DEFAULT 1 AFTER daily_amount,
ADD COLUMN IF NOT EXISTS cycle_status ENUM('active', 'completed', 'suspended') DEFAULT 'active' AFTER day_number;

-- Fix notifications table - add missing columns
ALTER TABLE notifications 
ADD COLUMN IF NOT EXISTS reference_id INT NULL AFTER message,
ADD COLUMN IF NOT EXISTS reference_type VARCHAR(50) NULL AFTER reference_id;

-- Update existing susu_cycles to have proper values
UPDATE susu_cycles 
SET day_number = 1 
WHERE day_number IS NULL OR day_number = 0;

UPDATE susu_cycles 
SET cycle_status = 'active' 
WHERE cycle_status IS NULL;

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_susu_cycles_client_status ON susu_cycles(client_id, cycle_status);
CREATE INDEX IF NOT EXISTS idx_notifications_reference ON notifications(reference_id, reference_type);
CREATE INDEX IF NOT EXISTS idx_daily_collections_cycle ON daily_collections(susu_cycle_id);

SELECT 'Database schema fixes applied successfully!' as status;

