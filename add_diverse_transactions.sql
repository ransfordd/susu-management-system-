-- Add diverse transaction data for different agents

-- First, assign clients to agents if not already assigned
UPDATE clients SET agent_id = (SELECT id FROM agents WHERE status = 'active' ORDER BY RAND() LIMIT 1) WHERE agent_id IS NULL;

-- Create diverse Susu cycles for each agent's clients
INSERT INTO susu_cycles (client_id, cycle_number, daily_amount, total_amount, payout_amount, agent_fee, start_date, completion_date, payout_date, status, created_at)
SELECT 
    c.id as client_id,
    COALESCE((SELECT MAX(sc2.cycle_number) FROM susu_cycles sc2 WHERE sc2.client_id = c.id), 0) + 1 as cycle_number,
    CASE 
        WHEN a.agent_code = 'AG001' THEN 50
        WHEN a.agent_code = 'AG002' THEN 75
        WHEN a.agent_code = 'AG003' THEN 100
        WHEN a.agent_code = 'AG004' THEN 60
        WHEN a.agent_code = 'AG005' THEN 80
        ELSE 65
    END as daily_amount,
    CASE 
        WHEN a.agent_code = 'AG001' THEN 1500
        WHEN a.agent_code = 'AG002' THEN 2250
        WHEN a.agent_code = 'AG003' THEN 3000
        WHEN a.agent_code = 'AG004' THEN 1800
        WHEN a.agent_code = 'AG005' THEN 2400
        ELSE 1950
    END as total_amount,
    CASE 
        WHEN a.agent_code = 'AG001' THEN 1450
        WHEN a.agent_code = 'AG002' THEN 2175
        WHEN a.agent_code = 'AG003' THEN 2900
        WHEN a.agent_code = 'AG004' THEN 1740
        WHEN a.agent_code = 'AG005' THEN 2320
        ELSE 1885
    END as payout_amount,
    CASE 
        WHEN a.agent_code = 'AG001' THEN 50
        WHEN a.agent_code = 'AG002' THEN 75
        WHEN a.agent_code = 'AG003' THEN 100
        WHEN a.agent_code = 'AG004' THEN 60
        WHEN a.agent_code = 'AG005' THEN 80
        ELSE 65
    END as agent_fee,
    DATE_SUB(CURDATE(), INTERVAL FLOOR(RAND() * 30) DAY) as start_date,
    DATE_SUB(CURDATE(), INTERVAL FLOOR(RAND() * 5) DAY) as completion_date,
    DATE_SUB(CURDATE(), INTERVAL FLOOR(RAND() * 3) DAY) as payout_date,
    'completed' as status,
    NOW() as created_at
FROM clients c
JOIN agents a ON c.agent_id = a.id
WHERE a.status = 'active'
AND NOT EXISTS (SELECT 1 FROM susu_cycles sc WHERE sc.client_id = c.id AND sc.status = 'completed')
LIMIT 20;

-- Create daily collections for the new cycles
INSERT INTO daily_collections (susu_cycle_id, collection_date, day_number, expected_amount, collected_amount, collection_status, collection_time, collected_by, created_at)
SELECT 
    sc.id as susu_cycle_id,
    DATE_ADD(sc.start_date, INTERVAL day_num DAY) as collection_date,
    day_num + 1 as day_number,
    sc.daily_amount as expected_amount,
    sc.daily_amount as collected_amount,
    'collected' as collection_status,
    ADDTIME('08:00:00', SEC_TO_TIME(FLOOR(RAND() * 600) * 60)) as collection_time,
    (SELECT a.id FROM agents a JOIN clients c ON a.id = c.agent_id WHERE c.id = sc.client_id) as collected_by,
    NOW() as created_at
FROM susu_cycles sc
CROSS JOIN (
    SELECT 0 as day_num UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION
    SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION
    SELECT 10 UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION
    SELECT 15 UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION
    SELECT 20 UNION SELECT 21 UNION SELECT 22 UNION SELECT 23 UNION SELECT 24 UNION
    SELECT 25 UNION SELECT 26 UNION SELECT 27 UNION SELECT 28 UNION SELECT 29
) days
WHERE sc.status = 'completed'
AND DATE_ADD(sc.start_date, INTERVAL day_num DAY) <= sc.completion_date
AND NOT EXISTS (
    SELECT 1 FROM daily_collections dc 
    WHERE dc.susu_cycle_id = sc.id 
    AND dc.day_number = day_num + 1
);

-- Create diverse manual transactions for each agent
INSERT INTO manual_transactions (client_id, transaction_type, amount, description, reference, processed_by, created_at)
SELECT 
    c.id as client_id,
    CASE WHEN RAND() < 0.6 THEN 'deposit' ELSE 'withdrawal' END as transaction_type,
    CASE 
        WHEN a.agent_code = 'AG001' THEN FLOOR(RAND() * 200) + 50
        WHEN a.agent_code = 'AG002' THEN FLOOR(RAND() * 300) + 75
        WHEN a.agent_code = 'AG003' THEN FLOOR(RAND() * 400) + 100
        WHEN a.agent_code = 'AG004' THEN FLOOR(RAND() * 250) + 60
        WHEN a.agent_code = 'AG005' THEN FLOOR(RAND() * 350) + 80
        ELSE FLOOR(RAND() * 250) + 65
    END as amount,
    CONCAT('Manual ', CASE WHEN RAND() < 0.6 THEN 'Deposit' ELSE 'Withdrawal' END, ' - Agent ', a.agent_code) as description,
    CONCAT('MAN-', UPPER(SUBSTRING(a.agent_code, 1, 2)), '-', DATE_FORMAT(NOW(), '%Y%m%d'), '-', FLOOR(RAND() * 1000)) as reference,
    1 as processed_by,
    DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 30) DAY) as created_at
FROM clients c
JOIN agents a ON c.agent_id = a.id
CROSS JOIN (
    SELECT 1 as n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
) numbers
WHERE a.status = 'active'
AND numbers.n <= CASE 
    WHEN a.agent_code = 'AG001' THEN 3
    WHEN a.agent_code = 'AG002' THEN 5
    WHEN a.agent_code = 'AG003' THEN 7
    WHEN a.agent_code = 'AG004' THEN 4
    WHEN a.agent_code = 'AG005' THEN 6
    ELSE 4
END;

SELECT 'Diverse transaction data created successfully!' as status;
SELECT 'Each agent now has different transaction totals.' as message;
