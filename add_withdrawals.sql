-- Add withdrawal amounts to database
-- This script creates completed Susu cycles and manual withdrawal transactions

-- First, let's complete some existing Susu cycles
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
        HAVING collections_count >= 10
        LIMIT 3
    ) sc
);

-- Create some additional completed cycles for testing
-- First, get the first 3 client IDs
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
    '2024-11-01' as start_date,
    '2024-12-02' as end_date,
    '2024-12-03' as payout_date,
    '2024-12-03 10:30:00' as completion_date,
    'completed' as status,
    NOW() as created_at
FROM clients c 
ORDER BY c.id 
LIMIT 1;

INSERT INTO susu_cycles (
    client_id, cycle_number, daily_amount, total_amount, 
    payout_amount, agent_fee, start_date, end_date, 
    payout_date, completion_date, status, created_at
) 
SELECT 
    c.id as client_id,
    2 as cycle_number,
    15.00 as daily_amount,
    465.00 as total_amount,
    450.00 as payout_amount,
    15.00 as agent_fee,
    '2024-11-05' as start_date,
    '2024-12-06' as end_date,
    '2024-12-07' as payout_date,
    '2024-12-07 14:15:00' as completion_date,
    'completed' as status,
    NOW() as created_at
FROM clients c 
ORDER BY c.id 
LIMIT 1 OFFSET 1;

INSERT INTO susu_cycles (
    client_id, cycle_number, daily_amount, total_amount, 
    payout_amount, agent_fee, start_date, end_date, 
    payout_date, completion_date, status, created_at
) 
SELECT 
    c.id as client_id,
    2 as cycle_number,
    20.00 as daily_amount,
    620.00 as total_amount,
    600.00 as payout_amount,
    20.00 as agent_fee,
    '2024-11-10' as start_date,
    '2024-12-11' as end_date,
    '2024-12-12' as payout_date,
    '2024-12-12 16:45:00' as completion_date,
    'completed' as status,
    NOW() as created_at
FROM clients c 
ORDER BY c.id 
LIMIT 1 OFFSET 2;

-- Add some manual withdrawal transactions
INSERT INTO manual_transactions (
    client_id, transaction_type, amount, description, 
    reference, processed_by, created_at
) 
SELECT 
    c.id as client_id,
    'withdrawal' as transaction_type,
    75.00 as amount,
    'Emergency withdrawal processed by admin' as description,
    'MANUAL-WD-20241215-001' as reference,
    (SELECT id FROM users WHERE role = 'business_admin' LIMIT 1) as processed_by,
    NOW() as created_at
FROM clients c 
ORDER BY c.id 
LIMIT 1;

INSERT INTO manual_transactions (
    client_id, transaction_type, amount, description, 
    reference, processed_by, created_at
) 
SELECT 
    c.id as client_id,
    'withdrawal' as transaction_type,
    100.00 as amount,
    'Partial withdrawal for medical expenses' as description,
    'MANUAL-WD-20241215-002' as reference,
    (SELECT id FROM users WHERE role = 'business_admin' LIMIT 1) as processed_by,
    NOW() as created_at
FROM clients c 
ORDER BY c.id 
LIMIT 1 OFFSET 1;

INSERT INTO manual_transactions (
    client_id, transaction_type, amount, description, 
    reference, processed_by, created_at
) 
SELECT 
    c.id as client_id,
    'withdrawal' as transaction_type,
    50.00 as amount,
    'Small withdrawal for personal use' as description,
    'MANUAL-WD-20241215-003' as reference,
    (SELECT id FROM users WHERE role = 'business_admin' LIMIT 1) as processed_by,
    NOW() as created_at
FROM clients c 
ORDER BY c.id 
LIMIT 1 OFFSET 2;

-- Update the AdminReportController to also include manual withdrawals
-- We need to modify the withdrawal query to include manual transactions

-- Let's also add some loan payments that could be considered withdrawals
INSERT INTO loan_payments (
    loan_id, payment_number, due_date, principal_amount,
    interest_amount, total_due, amount_paid, payment_date,
    payment_status, receipt_number, created_at
) VALUES 
(1, 1, '2024-12-01', 50.00, 25.00, 75.00, 75.00, '2024-12-01', 'paid', 'LP-20241201-001', NOW()),
(2, 1, '2024-12-05', 60.00, 30.00, 90.00, 90.00, '2024-12-05', 'paid', 'LP-20241205-001', NOW()),
(3, 1, '2024-12-10', 40.00, 20.00, 60.00, 60.00, '2024-12-10', 'paid', 'LP-20241210-001', NOW());

-- Show the results
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
