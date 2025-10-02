-- TechVent Database Migration
-- Add profile fields to users table (Updated - phone and department only)

USE techvent;

-- Add new columns for user profile information
ALTER TABLE users 
ADD COLUMN phone VARCHAR(20) DEFAULT NULL AFTER password,
ADD COLUMN department VARCHAR(100) DEFAULT NULL AFTER phone;

-- Show updated table structure
DESCRIBE users;

-- Update the admin user with sample profile data
UPDATE users 
SET 
    phone = '+1 (555) 123-0001',
    department = 'Administration'
WHERE email = 'admin@techvent.com';

-- Display confirmation
SELECT 'Profile fields migration completed successfully (phone and department only)' as status;