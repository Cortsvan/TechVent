# TechVent Supplier-Inventory Management System - Setup Guide V2

## 🎯 Overview
TechVent is a comprehensive supplier-based inventory management system with user authentication, role-based access, and complete CRUD operations for suppliers, products, and inventory management.

## 📋 Prerequisites
- **XAMPP** (or WAMP/MAMP) installed and running
- **Apache** and **MySQL** services active
- **Web browser** for testing
- **Text editor** (VS Code recommended)

## 🚀 Complete Setup Instructions

### Step 1: Environment Setup
1. **Start XAMPP Control Panel**
   - Start **Apache** service
   - Start **MySQL** service
   - Verify both services show green "Running" status

2. **Place Project Files**
   - Copy the entire `TechVent` folder to your `htdocs` directory
   - Path should be: `C:\xampp\htdocs\TechVent\` (Windows) or `/Applications/XAMPP/htdocs/TechVent/` (Mac)

### Step 2: Database Setup & Migration

#### 2.1 Create Database
1. Open **phpMyAdmin**: `http://localhost/phpmyadmin/`
2. Click **"New"** in the left sidebar
3. Enter database name: `techvent`
4. Click **"Create"**

#### 2.2 Run Database Scripts (In Order)
Execute the following SQL files in **exact order**:

**Script 1: Basic Structure**
```sql
-- File: database_setup.sql
-- Creates: users table with basic authentication
```
- In phpMyAdmin, select `techvent` database
- Click **"SQL"** tab
- Copy and paste entire content of `database_setup.sql`
- Click **"Go"** to execute

**Script 2: User Management Fields**
```sql
-- File: database_update_user_management.sql
-- Adds: phone, department, is_active fields to users table
```
- Click **"SQL"** tab again
- Copy and paste content of `database_update_user_management.sql`
- Click **"Go"** to execute

**Script 3: Supplier-Inventory System**
```sql
-- File: database_supplier_system.sql
-- Creates: suppliers, products, inventory, inventory_transactions tables
-- Includes: Sample data for testing
```
- Click **"SQL"** tab again
- Copy and paste content of `database_supplier_system.sql`
- Click **"Go"** to execute

#### 2.3 Verify Database Structure
After running all scripts, your database should have these tables:
- ✅ `users` (with phone, department, is_active fields)
- ✅ `suppliers` 
- ✅ `products`
- ✅ `inventory`
- ✅ `inventory_transactions`

### Step 3: Configuration Check

#### 3.1 Database Connection
Verify `config/db.php` has correct settings:
```php
$host = 'localhost';
$dbname = 'techvent';
$username = 'root';
$password = ''; // Usually empty for XAMPP
```

#### 3.2 Test Database Connection
1. Navigate to: `http://localhost/TechVent/test.php`
2. Should show green success messages for all database tables
3. **Important**: Delete `test.php` after testing (security)

### Step 4: System Testing

#### 4.1 Default Admin Account
The system includes a default admin account:
- **Email**: `admin@techvent.com`
- **Password**: `admin123`
- **Role**: Admin
- **Status**: Active

#### 4.2 Test User Authentication
1. **Login Test**: `http://localhost/TechVent/login.php`
   - Use admin credentials above
   - Should redirect to admin dashboard

2. **Registration Test**: `http://localhost/TechVent/register.php`
   - Create a regular user account
   - New users default to "user" role

#### 4.3 Test Admin Features
Login as admin and verify access to:
- ✅ **Dashboard**: `http://localhost/TechVent/admin-dashboard.php`
- ✅ **Suppliers**: `http://localhost/TechVent/admin-suppliers.php`
- ✅ **Products**: `http://localhost/TechVent/admin-products.php`
- ✅ **Inventory**: `http://localhost/TechVent/admin-inventory.php`
- ✅ **User Management**: `http://localhost/TechVent/admin-user-management.php`
- ✅ **Profile**: `http://localhost/TechVent/user-profile.php`

#### 4.4 Test User Features
Login as regular user and verify access to:
- ✅ **Dashboard**: `http://localhost/TechVent/user-dashboard.php`
- ✅ **Products**: `http://localhost/TechVent/admin-products.php` (view-only)
- ✅ **Inventory**: `http://localhost/TechVent/admin-inventory.php` (view-only)
- ✅ **Profile**: `http://localhost/TechVent/user-profile.php`
- ❌ **No Access**: Suppliers, User Management (admin-only)

### Step 5: Sample Data & Workflow Testing

#### 5.1 Sample Data Included
The `database_supplier_system.sql` includes:
- **3 Sample Suppliers**: TechnoMax Solutions, Digital Components Inc, GadgetPro Supply
- **9 Sample Products**: Various tech items with different categories
- **Inventory Records**: Stock levels for all products
- **Transaction History**: Sample inventory movements

#### 5.2 Test Complete Workflow
1. **As Admin - Manage Suppliers**:
   - Add new supplier
   - Edit supplier information
   - View supplier product counts

2. **As Admin/User - Browse Products**:
   - Filter by supplier
   - Filter by category
   - Search products
   - View stock status

3. **As Admin/User - Manage Inventory**:
   - Update stock levels
   - View inventory transactions
   - Check low stock alerts

4. **As Admin - User Management**:
   - View all users
   - Activate/deactivate users
   - Change user roles

### Step 6: File Structure Reference

```
TechVent/
├── 📁 config/
│   └── db.php                           # Database connection
├── 📁 includes/
│   └── session.php                      # Session management & helpers
├── 📁 assets/
│   ├── 📁 css/
│   │   └── main.css                     # Unified CSS styles
│   └── 📁 js/
│       └── main.js                      # JavaScript functions
├── 📄 admin-dashboard.php               # Admin main dashboard
├── 📄 admin-suppliers.php               # Supplier management (admin-only)
├── 📄 admin-products.php                # Product catalog (admin+user)
├── 📄 admin-inventory.php               # Inventory management (admin+user)
├── 📄 admin-user-management.php         # User management (admin-only)
├── 📄 user-dashboard.php                # User main dashboard
├── 📄 user-profile.php                  # Profile management (both roles)
├── 📄 login.php                         # Authentication
├── 📄 register.php                      # User registration
├── 📄 logout.php                        # Session termination
├── 📄 admin_protection.php              # Admin access control
├── 📄 user_protection.php               # User access control
├── 📄 index.html                        # Landing page
├── 📄 forgot.html                       # Password recovery (future)
├── 📊 database_setup.sql                # Initial database structure
├── 📊 database_update_user_management.sql # User table updates
├── 📊 database_supplier_system.sql      # Complete inventory system
└── 📄 README.md                         # Project documentation
```

## 🔧 Development Tools (Optional)

### Database Migration Scripts
For development purposes, these PHP scripts can help with database setup:
- `migrate.php` - Admin-controlled migration runner
- `simple-migrate.php` - Basic field migration
- `migrate_user_management.php` - User table updates

**⚠️ Security Note**: Remove these from production servers!

## 🐛 Troubleshooting Guide

### Common Issues & Solutions

#### Database Connection Failed
```
Error: SQLSTATE[HY000] [1049] Unknown database 'techvent'
```
**Solution**: Create database first, then run SQL scripts

#### Missing Tables
```
Error: Table 'techvent.suppliers' doesn't exist
```
**Solution**: Run `database_supplier_system.sql` script

#### Access Denied Errors
```
Error: Access denied for user 'root'@'localhost'
```
**Solution**: Check MySQL credentials in `config/db.php`

#### Navigation Items Missing
```
Issue: Menu items disappear when clicking between pages
```
**Solution**: Already fixed in current version - ensure all files are updated

#### Undefined Variables
```
Error: Undefined variable: $dashboardStats
```
**Solution**: Fixed in current version - variables properly defined

### Performance Optimization
1. **Enable PHP OPcache** in XAMPP
2. **Add database indexes** for frequently queried fields
3. **Implement pagination** for large datasets
4. **Use CSS/JS minification** for production

## 🔐 Security Considerations

### Current Security Features
- ✅ **Password Hashing**: Uses PHP `password_hash()`
- ✅ **Session Management**: Secure session handling
- ✅ **SQL Injection Protection**: Prepared statements
- ✅ **Role-Based Access**: Admin/User permissions
- ✅ **Input Validation**: Form data sanitization
- ✅ **CSRF Protection**: Basic token validation

### Production Recommendations
- 🔒 **Use HTTPS** in production
- 🔒 **Environment variables** for database credentials
- 🔒 **Rate limiting** for login attempts
- 🔒 **Regular security updates**
- 🔒 **Database backups**

## 👥 Team Collaboration

### For Your Colleagues
1. **Clone/Download** the project
2. **Follow Steps 1-4** exactly
3. **Test with provided credentials**
4. **Create their own user accounts**
5. **Use admin account for system management**

### Development Workflow
1. **Always backup database** before making changes
2. **Test changes locally** before sharing
3. **Document any new features** or modifications
4. **Use version control** (Git) for code changes

## 📞 Support & Next Steps

### If You Encounter Issues
1. **Check XAMPP logs**: Control Panel → Logs
2. **Enable PHP error reporting**: Add to top of PHP files:
   ```php
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```
3. **Verify each step** was completed correctly
4. **Test components individually**

### Future Enhancements
- 📧 **Email notifications** for low stock
- 📊 **Advanced reporting** and analytics  
- 🔄 **Automatic reorder points**
- 📱 **Mobile-responsive design** improvements
- 🔌 **API endpoints** for integrations

---

## 🎉 Success Confirmation

If setup is successful, you should be able to:
- ✅ Login as admin (`admin@techvent.com` / `admin123`)
- ✅ Navigate all admin pages without errors
- ✅ View sample suppliers and products
- ✅ Update inventory levels
- ✅ Manage user accounts
- ✅ Access system as regular user with appropriate restrictions

**Happy coding! 🚀**

---
*TechVent Team: Jovannie Cortes, Shane Gamboa, Rodz Gabriel Velayo & Franz Anthony Tomas*