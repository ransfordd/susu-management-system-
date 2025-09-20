-- Check available clients
SELECT id, user_id FROM clients ORDER BY id;

-- Check users for clients
SELECT u.id, u.first_name, u.last_name, u.role 
FROM users u 
WHERE u.id IN (SELECT user_id FROM clients)
ORDER BY u.id;





