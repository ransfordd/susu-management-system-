-- Simple script to add withdrawal data
-- This will work regardless of client IDs

-- First, complete any existing active cycles
UPDATE susu_cycles 
SET status = 'completed', 
    payout_date = CURDATE(), 
    completion_date = NOW(),
    payout_amount = CASE 
        WHEN total_amount > agent_fee THEN total_amount - agent_fee
        ELSE total_amount * 0.97
    END
WHERE status = 'active' 
AND id IN (
    SELECT sc.id FROM (
        SELECT sc.id, COUNT(dc.id) as collections_count
        FROM susu_cycles sc
        LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id
        WHERE sc.status = 'active'
        GROUP BY sc.id
        HAVING collections_count >= 5
        LIMIT 3
    ) sc
);

-- Create completed cycles using any available clients
INSERT INTO susu_cycles (
    client_id, cycle_number, daily_amount, total_amount, 
    payout_amount, agent_fee, start_date, end_date, 
    payout_date, completion_date, status, created_at
) 
SELECT 
    c.id as client_id,
    2 as cycle_number,
    10.00 as daily_amount,
    310.00 as total_amount,
    300.00 as payout_amount,
    10.00 as agent_fee,
    DATE_SUB(CURDATE(), INTERVAL 35 DAY) as start_date,
    DATE_SUB(CURDATE(), INTERVAL 4 DAY) as end_date,
    DATE_SUB(CURDATE(), INTERVAL 3 DAY) as payout_date,
    DATE_SUB(CURDATE(), INTERVAL 3 DAY) as completion_date,
    'completed' as status,
    NOW() as created_at
FROM clients c 
ORDER BY c.id 
LIMIT 1;

-- Add manual withdrawal transactions
INSERT INTO manual_transactions (
    client_id, transaction_type, amount, description, 
    reference, processed_by, created_at
) 
SELECT 
    c.id as client_id,
    'withdrawal' as transaction_type,
    75.00 as amount,
    'Emergency withdrawal processed by admin' as description,
    CONCAT('MANUAL-WD-', DATE_FORMAT(NOW(), '%Y%m%d'), '-001') as reference,
    (SELECT id FROM users WHERE role = 'business_admin' LIMIT 1) as processed_by,
    NOW() as created_at
FROM clients c 
ORDER BY c.id 
LIMIT 1;

-- Show results
SELECT 'Completed Susu Cycles:' as info;
SELECT sc.id, CONCAT(c.first_name, ' ', c.last_name) as client_name, 
       sc.payout_amount, sc.payout_date, sc.status
FROM susu_cycles sc
JOIN clients cl ON sc.client_id = cl.id
JOIN users c ON cl.user_id = c.id
WHERE sc.status = 'completed'
ORDER BY sc.payout_date DESC;

SELECT 'Manual Withdrawals:' as info;
SELECT mt.id, CONCAT(c.first_name, ' ', c.last_name) as client_name,
       mt.amount, mt.description, mt.created_at
FROM manual_transactions mt
JOIN clients cl ON mt.client_id = cl.id
JOIN users c ON cl.user_id = c.id
WHERE mt.transaction_type = 'withdrawal'
ORDER BY mt.created_at DESC;





