-- TechVent Database Setup
-- Create database and user table for the authentication system

-- Create the database
CREATE DATABASE IF NOT EXISTS techvent;
USE techvent;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100) DEFAULT NULL,
    last_name VARCHAR(100) NOT NULL,
    suffix VARCHAR(10) DEFAULT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default admin user
INSERT INTO users (first_name, last_name, email, password, user_type) 
VALUES ('Admin', 'User', 'admin@techvent.com', 'admin123', 'admin')
ON DUPLICATE KEY UPDATE email=email;

-- Show the tables
SHOW TABLES;
DESCRIBE users;