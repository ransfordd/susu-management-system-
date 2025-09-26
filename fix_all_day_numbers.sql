-- Fix day numbers for Susu collections
-- This script will update day_number field to be sequential (1-31) for all collections
-- It handles the full 31-day Susu cycle

-- First, let's see what we're working with - show all collections
SELECT 
    dc.id, 
    dc.day_number, 
    dc.collection_date, 
    dc.collected_amount, 
    dc.collection_status,
    sc.client_id,
    c.client_code
FROM daily_collections dc
JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
JOIN clients c ON sc.client_id = c.id
WHERE dc.collection_status = 'collected'
ORDER BY sc.client_id, dc.collection_date ASC;

-- Update day numbers to be sequential based on collection date for each client's cycle
-- This will reset all day numbers and reassign them correctly

-- For each Susu cycle, update day numbers to be sequential (1, 2, 3, ...)
-- We'll use a more dynamic approach that works for any number of collections

-- Method 1: Update day numbers for each client's cycle individually
-- This ensures each client's cycle starts from day 1

-- Get all unique client cycles
SELECT DISTINCT 
    sc.id as cycle_id,
    sc.client_id,
    c.client_code,
    COUNT(dc.id) as total_collections
FROM susu_cycles sc
JOIN clients c ON sc.client_id = c.id
LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id AND dc.collection_status = 'collected'
GROUP BY sc.id, sc.client_id, c.client_code
ORDER BY sc.client_id;

-- Update day numbers for each cycle
-- We'll use a subquery to assign sequential numbers based on collection date

-- For Cycle 1 (Client ID 1 - Akua Boateng)
UPDATE daily_collections dc
JOIN (
    SELECT 
        id,
        ROW_NUMBER() OVER (ORDER BY collection_date ASC) as new_day_number
    FROM daily_collections 
    WHERE susu_cycle_id = (
        SELECT id FROM susu_cycles WHERE client_id = 1 ORDER BY created_at DESC LIMIT 1
    )
    AND collection_status = 'collected'
) ranked ON dc.id = ranked.id
SET dc.day_number = ranked.new_day_number;

-- For any other cycles, we can add similar updates here
-- For now, let's also create a general solution that works for all cycles

-- Alternative approach: Update all collections at once using a more general method
-- This will work for any number of collections per cycle

-- Reset all day numbers to NULL first
UPDATE daily_collections SET day_number = NULL WHERE collection_status = 'collected';

-- Then reassign day numbers sequentially for each cycle
UPDATE daily_collections dc
JOIN (
    SELECT 
        dc.id,
        ROW_NUMBER() OVER (PARTITION BY dc.susu_cycle_id ORDER BY dc.collection_date ASC) as new_day_number
    FROM daily_collections dc
    WHERE dc.collection_status = 'collected'
) ranked ON dc.id = ranked.id
SET dc.day_number = ranked.new_day_number;

-- Verify the changes - show all collections with corrected day numbers
SELECT 
    dc.id, 
    dc.day_number, 
    dc.collection_date, 
    dc.collected_amount, 
    dc.collection_status,
    sc.client_id,
    c.client_code
FROM daily_collections dc
JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
JOIN clients c ON sc.client_id = c.id
WHERE dc.collection_status = 'collected'
ORDER BY sc.client_id, dc.day_number ASC;
