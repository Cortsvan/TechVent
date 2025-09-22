# TechVent PHP Authentication System Setup Guide

## Prerequisites
- XAMPP installed and running (Apache and MySQL)
- Web browser to test the application

## Step-by-Step Setup Instructions

### 1. Database Setup
1. **Start XAMPP** and ensure both **Apache** and **MySQL** services are running
2. **Open phpMyAdmin** by going to `http://localhost/phpmyadmin/` in your browser
3. **Create the database:**
   - Click "New" in the left sidebar
   - Enter database name: `techvent`
   - Click "Create"

4. **Run the SQL script:**
   - Select the `techvent` database from the left sidebar
   - Click the "SQL" tab
   - Copy and paste the contents of `database_setup.sql` into the SQL text area
   - Click "Go" to execute the script

### 2. File Structure Check
Make sure your project has the following structure:
```
TechVent/
├── config/
│   └── db.php
├── includes/
│   └── session.php
├── admin-dashboard.html
├── user-dashboard.html
├── index.html
├── login.php
├── register.php
├── logout.php
├── database_setup.sql
└── (other HTML files...)
```

### 3. Test the Database Connection
1. Create a test file temporarily to verify database connection
2. Access `http://localhost/TechVent/config/db.php` (you should see no errors if connection is successful)
3. Remove or comment out any test echo statements in db.php

### 4. Default Admin Account
The system automatically creates a default admin account:
- **Email:** `admin@techvent.com`
- **Password:** `admin123`
- **Role:** Admin

### 5. Testing the System

#### Test Registration:
1. Go to `http://localhost/TechVent/register.php`
2. Fill out the registration form with sample data
3. Submit and verify the user is added to the database

#### Test Login:
1. Go to `http://localhost/TechVent/login.php`
2. **Test Admin Login:**
   - Email: `admin@techvent.com`
   - Password: `admin123`
   - Should redirect to `admin-dashboard.html`

3. **Test Regular User Login:**
   - Use the credentials from a registered user
   - Should redirect to `user-dashboard.html`

#### Test Logout:
1. While logged in, go to `http://localhost/TechVent/logout.php`
2. Should clear session and redirect to login page

### 6. Security Notes for Beginners

**Current Setup (Beginner-Friendly):**
- Passwords are stored in plain text (NOT recommended for production)
- No password hashing implemented yet
- Basic form validation
- Simple session management

**For Production Use (Future Improvements):**
- Implement password hashing with `password_hash()` and `password_verify()`
- Add CSRF protection
- Implement proper input sanitization
- Add rate limiting for login attempts
- Use HTTPS in production

### 7. Common Troubleshooting

**Database Connection Issues:**
- Verify XAMPP MySQL is running
- Check if database `techvent` exists
- Ensure database credentials in `config/db.php` are correct

**Permission Issues:**
- Make sure XAMPP has proper permissions to access the project folder
- Try accessing files directly via localhost URL

**Session Issues:**
- Clear browser cookies and cache
- Restart XAMPP services if needed

### 8. File Extensions and Web Server
- Make sure you're accessing `.php` files through localhost (not opening directly in browser)
- All authentication files must be accessed via HTTP (e.g., `http://localhost/TechVent/login.php`)

### 9. Next Steps
Once basic functionality is working:
- Implement password hashing for security
- Add email verification for registration
- Implement "Remember Me" functionality
- Add user profile management
- Implement admin user management features

## Demo Credentials
- **Admin:** admin@techvent.com / admin123
- **Regular User:** Create through registration form or use any registered user

## Support
If you encounter issues:
1. Check XAMPP error logs
2. Enable error reporting in PHP
3. Verify database table structure matches the SQL script
4. Test each component individually