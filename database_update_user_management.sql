-- Update users table to match the old user management design
-- Add phone, department, and is_active fields

ALTER TABLE users 
ADD COLUMN phone VARCHAR(20) DEFAULT NULL AFTER email,
ADD COLUMN department VARCHAR(100) DEFAULT NULL AFTER phone,
ADD COLUMN is_active TINYINT(1) DEFAULT 1 AFTER department;