-- TechVent Database Migration
-- Remove location and timezone fields from users table

USE techvent;

-- Remove location and timezone columns from users table
ALTER TABLE users 
DROP COLUMN location,
DROP COLUMN timezone;

-- Show updated table structure
DESCRIBE users;

-- Display confirmation
SELECT 'Location and timezone fields removed successfully' as status;