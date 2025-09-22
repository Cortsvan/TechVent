-- TechVent Database Migration
-- Add profile fields to users table

USE techvent;

-- Add new columns for user profile information
ALTER TABLE users 
ADD COLUMN phone VARCHAR(20) DEFAULT NULL AFTER password,
ADD COLUMN department VARCHAR(100) DEFAULT NULL AFTER phone,
ADD COLUMN location VARCHAR(255) DEFAULT NULL AFTER department,
ADD COLUMN timezone VARCHAR(50) DEFAULT NULL AFTER location;

-- Show updated table structure
DESCRIBE users;

-- Update the admin user with sample profile data
UPDATE users 
SET 
    phone = '+1 (555) 123-0001',
    department = 'Administration',
    location = 'San Francisco, CA',
    timezone = 'PST (UTC-8)'
WHERE email = 'admin@techvent.com';

-- Display confirmation
SELECT 'Profile fields migration completed successfully' as status;