-- Fix password hashes for existing users
-- Run this to update the database with correct hashes

USE garage_db;

-- Update staff passwords (all use 'staffpass')
UPDATE staff 
SET password_hash = '$2y$10$n7g9sSWqnGqP6z89S4RZSuob7nIuH3dsccRXkzNw.C2qjB5AwOxCa'
WHERE staff_id IN (1000, 1001, 1002);

-- Update customer passwords (all use 'customer123')
UPDATE customers 
SET password_hash = '$2y$10$qeGgjIa6NSUWF/uZYC32we/sL4zMBGnkCx3WqmsnUIDQZuHDBaefK'
WHERE customer_id IN (2000, 2001);

-- Verify updates
SELECT staff_id, username, role, 
       password_hash 
FROM staff 
WHERE staff_id IN (1000, 1001, 1002);

SELECT customer_id, name, email, 
       password_hash 
FROM customers 
WHERE customer_id IN (2000, 2001);
