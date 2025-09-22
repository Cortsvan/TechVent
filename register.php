<?php
/**
 * Registration Page - TechVent
 * Handles user registration with database storage
 */

// Start session
session_start();

// Include database connection
require_once 'config/db.php';
require_once 'includes/session.php';

// Initialize variables
$errors = [];
$success = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $firstName = trim($_POST['firstName']);
    $middleName = trim($_POST['middleName']);
    $lastName = trim($_POST['lastName']);
    $suffix = trim($_POST['suffix']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    
    // Validation
    if (empty($firstName)) {
        $errors[] = "First name is required.";
    }
    
    if (empty($lastName)) {
        $errors[] = "Last name is required.";
    }
    
    if (empty($email)) {
        $errors[] = "Email address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please provide a valid email address.";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
    }
    
    if (!isset($_POST['agreeTerms'])) {
        $errors[] = "You must agree to the terms and conditions.";
    }
    
    // Check if email already exists
    if (empty($errors)) {
        try {
            $stmt = executeQuery("SELECT id FROM users WHERE email = ?", [$email]);
            if ($stmt->fetch()) {
                $errors[] = "An account with this email address already exists.";
            }
        } catch (Exception $e) {
            $errors[] = "Database error occurred.";
        }
    }
    
    // If no errors, register the user
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO users (first_name, middle_name, last_name, suffix, email, password, user_type) 
                    VALUES (?, ?, ?, ?, ?, ?, 'user')";
            $params = [
                $firstName, 
                $middleName ?: null, 
                $lastName, 
                $suffix ?: null, 
                $email, 
                $password  // Note: In production, you should hash this password
            ];
            
            executeQuery($sql, $params);
            
            // Set success message in session and redirect to login
            setSessionMessage("Registration successful! You can now login with your credentials.");
            header('Location: login.php');
            exit();
            
        } catch (Exception $e) {
            $errors[] = "Registration failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - TechVent</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --navy: #1a202c;
            --cyan: #3182ce;
            --light-cyan: #63b3ed;
            --dark-bg: #0f1419;
            --card-bg: #2d3748;
            --text-light: #e2e8f0;
            --text-muted: #a0aec0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--dark-bg) 0%, var(--navy) 100%);
            color: var(--text-light);
            line-height: 1.6;
            min-height: 100vh;
        }

        /* Navbar Styles */
        .navbar-custom {
            background: rgba(26, 32, 44, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(49, 130, 206, 0.1);
            transition: all 0.3s ease;
        }

        .navbar-brand {
            font-size: 1.8rem;
            font-weight: bold;
            background: linear-gradient(45deg, var(--cyan), var(--light-cyan));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .navbar-nav .nav-link {
            color: var(--text-light) !important;
            font-weight: 500;
            margin: 0 10px;
            position: relative;
            transition: all 0.3s ease;
        }

        .navbar-nav .nav-link:hover {
            color: var(--cyan) !important;
            transform: translateY(-2px);
        }

        .navbar-nav .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -5px;
            left: 50%;
            background: linear-gradient(45deg, var(--cyan), var(--light-cyan));
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }

        .navbar-nav .nav-link:hover::after {
            width: 100%;
        }

        /* Main Content */
        .main-content {
            min-height: calc(100vh - 200px);
            display: flex;
            align-items: center;
            padding: 120px 0 60px;
            position: relative;
            overflow: hidden;
        }

        .main-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='60' viewBox='0 0 60 60'%3E%3Cg fill-rule='evenodd'%3E%3Cg fill='%233182ce' fill-opacity='0.1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
            opacity: 0.1;
        }

        /* Registration Form */
        .register-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(49, 130, 206, 0.1);
            position: relative;
            z-index: 2;
            backdrop-filter: blur(10px);
        }

        .register-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .register-title {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(45deg, var(--text-light), var(--cyan));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .register-subtitle {
            color: var(--text-muted);
            font-size: 1.1rem;
        }

        .register-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(45deg, var(--cyan), var(--light-cyan));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: white;
            margin: 0 auto 25px;
            box-shadow: 0 8px 25px rgba(49, 130, 206, 0.3);
        }

        /* Form Styles */
        .form-label {
            color: var(--text-light);
            font-weight: 600;
            margin-bottom: 8px;
        }

        .form-control {
            background: rgba(45, 55, 72, 0.8);
            border: 1px solid rgba(49, 130, 206, 0.2);
            border-radius: 10px;
            color: var(--text-light);
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background: rgba(45, 55, 72, 0.9);
            border-color: var(--cyan);
            box-shadow: 0 0 0 0.2rem rgba(49, 130, 206, 0.25);
            color: var(--text-light);
        }

        .form-control::placeholder {
            color: var(--text-muted);
        }

        .input-group {
            position: relative;
        }

        .input-group-text {
            background: rgba(45, 55, 72, 0.8);
            border: 1px solid rgba(49, 130, 206, 0.2);
            border-right: none;
            color: var(--cyan);
            border-radius: 10px 0 0 10px;
        }

        .input-group .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }

        .btn-register {
            background: linear-gradient(45deg, var(--cyan), var(--light-cyan));
            border: none;
            padding: 15px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(49, 130, 206, 0.3);
            width: 100%;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(49, 130, 206, 0.4);
        }

        .login-link {
            text-align: center;
            margin-top: 25px;
            color: var(--text-muted);
        }

        .login-link a {
            color: var(--cyan);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .login-link a:hover {
            color: var(--light-cyan);
            text-decoration: underline;
        }

        /* Alert styles */
        .alert-custom-error {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.3);
            border-radius: 10px;
            color: #dc3545;
            padding: 12px 15px;
            margin-bottom: 20px;
        }

        .alert-custom-success {
            background: rgba(40, 167, 69, 0.1);
            border: 1px solid rgba(40, 167, 69, 0.3);
            border-radius: 10px;
            color: #28a745;
            padding: 12px 15px;
            margin-bottom: 20px;
        }

        /* Footer */
        .footer {
            background: var(--navy);
            padding: 60px 0 30px;
            border-top: 1px solid rgba(49, 130, 206, 0.2);
        }

        .footer-title {
            color: var(--cyan);
            font-weight: 700;
            margin-bottom: 20px;
        }

        .footer-link {
            color: var(--text-muted);
            text-decoration: none;
            transition: all 0.3s ease;
            display: block;
            margin-bottom: 10px;
        }

        .footer-link:hover {
            color: var(--cyan);
            transform: translateX(5px);
        }

        .social-icon {
            width: 45px;
            height: 45px;
            background: var(--card-bg);
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--text-light);
            margin-right: 15px;
            transition: all 0.3s ease;
            border: 1px solid rgba(49, 130, 206, 0.2);
        }

        .social-icon:hover {
            background: var(--cyan);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(49, 130, 206, 0.3);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .register-card {
                padding: 30px 20px;
            }
            
            .register-title {
                font-size: 2rem;
            }
            
            .main-content {
                padding: 100px 0 40px;
            }
        }

        /* Animation */
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s ease;
        }

        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* Form validation styles */
        .is-invalid {
            border-color: #dc3545 !important;
        }

        .is-valid {
            border-color: #28a745 !important;
        }

        .invalid-feedback {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 5px;
        }

        .valid-feedback {
            color: #28a745;
            font-size: 0.875rem;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-custom fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.html">
                <i class="fas fa-microchip me-2"></i>TechVent
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.html#about">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="register.php">Register</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-xl-6">
                    <div class="register-card fade-in">
                        <div class="register-header">
                            <div class="register-icon">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <h1 class="register-title">Join TechVent</h1>
                            <p class="register-subtitle">Create your account and start managing your tech inventory</p>
                        </div>

                        <!-- Display errors -->
                        <?php if (!empty($errors)): ?>
                            <div class="alert-custom-error">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <!-- Display success message -->
                        <?php if (!empty($success)): ?>
                            <div class="alert-custom-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo htmlspecialchars($success); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="firstName" class="form-label">
                                        <i class="fas fa-user me-2"></i>First Name
                                    </label>
                                    <input type="text" class="form-control" id="firstName" name="firstName" 
                                           placeholder="Enter your first name" 
                                           value="<?php echo htmlspecialchars($firstName ?? ''); ?>" required>
                                    <div class="invalid-feedback">
                                        Please provide a valid first name.
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="middleName" class="form-label">
                                        <i class="fas fa-user me-2"></i>Middle Name
                                    </label>
                                    <input type="text" class="form-control" id="middleName" name="middleName" 
                                           placeholder="Enter your middle name (optional)"
                                           value="<?php echo htmlspecialchars($middleName ?? ''); ?>">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label for="lastName" class="form-label">
                                        <i class="fas fa-user me-2"></i>Last Name
                                    </label>
                                    <input type="text" class="form-control" id="lastName" name="lastName" 
                                           placeholder="Enter your last name" 
                                           value="<?php echo htmlspecialchars($lastName ?? ''); ?>" required>
                                    <div class="invalid-feedback">
                                        Please provide a valid last name.
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="suffix" class="form-label">
                                        <i class="fas fa-tag me-2"></i>Suffix
                                    </label>
                                    <select class="form-control" id="suffix" name="suffix">
                                        <option value="">None</option>
                                        <option value="Jr." <?php echo (isset($suffix) && $suffix == 'Jr.') ? 'selected' : ''; ?>>Jr.</option>
                                        <option value="Sr." <?php echo (isset($suffix) && $suffix == 'Sr.') ? 'selected' : ''; ?>>Sr.</option>
                                        <option value="II" <?php echo (isset($suffix) && $suffix == 'II') ? 'selected' : ''; ?>>II</option>
                                        <option value="III" <?php echo (isset($suffix) && $suffix == 'III') ? 'selected' : ''; ?>>III</option>
                                        <option value="IV" <?php echo (isset($suffix) && $suffix == 'IV') ? 'selected' : ''; ?>>IV</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope me-2"></i>Email Address
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-at"></i>
                                    </span>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           placeholder="Enter your email address" 
                                           value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                                </div>
                                <div class="invalid-feedback">
                                    Please provide a valid email address.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-2"></i>Password
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-key"></i>
                                    </span>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           placeholder="Create a strong password" required minlength="8">
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback">
                                    Password must be at least 8 characters long.
                                </div>
                                <small class="text-muted">
                                    <small style="color: #FFD600;">Password should contain at least 8 characters with a mix of letters, numbers, and symbols.</small>
                                </small>
                            </div>

                            <div class="mb-4">
                                <label for="confirmPassword" class="form-label">
                                    <i class="fas fa-lock me-2"></i>Confirm Password
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-check"></i>
                                    </span>
                                    <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" 
                                           placeholder="Confirm your password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback">
                                    Passwords do not match.
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="agreeTerms" name="agreeTerms" required>
                                    <label class="form-check-label" for="agreeTerms">
                                        I agree to the <a href="#" class="text-decoration-none">Terms of Service</a> and 
                                        <a href="#" class="text-decoration-none">Privacy Policy</a>
                                    </label>
                                    <div class="invalid-feedback">
                                        You must agree to the terms and conditions.
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-register">
                                <i class="fas fa-rocket me-2"></i>Create Account
                            </button>
                        </form>

                        <div class="login-link">
                            Already have an account? <a href="login.php">Sign in here</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5 class="footer-title">
                        <i class="fas fa-microchip me-2"></i>TechVent
                    </h5>
                    <p class="footer-link">
                        Smart inventory management for tech retailers. Streamline your operations and grow your business with intelligent automation.
                    </p>
                    <div class="mt-4">
                        <a href="#" class="social-icon">
                            <i class="fab fa-github"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="footer-title">Company</h6>
                    <a href="index.html#about" class="footer-link">About Us</a>
                    <a href="#" class="footer-link">Our Team</a>
                    <a href="#" class="footer-link">Careers</a>
                    <a href="#" class="footer-link">Contact</a>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="footer-title">Features</h6>
                    <a href="#" class="footer-link">Restock Alerts</a>
                    <a href="#" class="footer-link">Product Management</a>
                    <a href="#" class="footer-link">Supplier Network</a>
                    <a href="#" class="footer-link">Reports</a>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="footer-title">Legal</h6>
                    <a href="#" class="footer-link">Privacy Policy</a>
                    <a href="#" class="footer-link">Terms of Service</a>
                </div>
            </div>
            
            <hr class="my-4" style="border-color: rgba(49, 130, 206, 0.2);">
            
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="footer-link mb-0">
                        &copy; 2025 TechVent. All rights reserved.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="footer-link mb-0">
                        Built with <i class="fas fa-heart text-danger"></i> for tech retailers
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Form validation and functionality
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const passwordField = document.getElementById('password');
            const confirmPasswordField = document.getElementById('confirmPassword');
            const togglePassword = document.getElementById('togglePassword');
            const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');

            // Password visibility toggle
            togglePassword.addEventListener('click', function() {
                const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordField.setAttribute('type', type);
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });

            toggleConfirmPassword.addEventListener('click', function() {
                const type = confirmPasswordField.getAttribute('type') === 'password' ? 'text' : 'password';
                confirmPasswordField.setAttribute('type', type);
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });

            // Real-time password confirmation validation
            confirmPasswordField.addEventListener('input', function() {
                if (this.value !== passwordField.value && this.value.length > 0) {
                    this.classList.add('is-invalid');
                    this.classList.remove('is-valid');
                } else if (this.value.length > 0 && this.value === passwordField.value) {
                    this.classList.add('is-valid');
                    this.classList.remove('is-invalid');
                }
            });

            // Password strength validation
            passwordField.addEventListener('input', function() {
                if (this.value.length >= 8) {
                    this.classList.add('is-valid');
                    this.classList.remove('is-invalid');
                } else if (this.value.length > 0) {
                    this.classList.add('is-invalid');
                    this.classList.remove('is-valid');
                }
                
                // Re-validate confirm password
                if (confirmPasswordField.value.length > 0) {
                    if (confirmPasswordField.value !== this.value) {
                        confirmPasswordField.classList.add('is-invalid');
                        confirmPasswordField.classList.remove('is-valid');
                    } else {
                        confirmPasswordField.classList.add('is-valid');
                        confirmPasswordField.classList.remove('is-invalid');
                    }
                }
            });

            // Real-time validation for required fields
            const requiredFields = ['firstName', 'lastName', 'email'];
            requiredFields.forEach(fieldName => {
                const field = document.getElementById(fieldName);
                field.addEventListener('input', function() {
                    if (this.value.trim()) {
                        this.classList.remove('is-invalid');
                        this.classList.add('is-valid');
                    } else {
                        this.classList.remove('is-valid');
                        this.classList.add('is-invalid');
                    }
                });
            });

            // Email validation
            const emailField = document.getElementById('email');
            emailField.addEventListener('input', function() {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (emailRegex.test(this.value)) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } else if (this.value.length > 0) {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                }
            });

            // Animation trigger
            setTimeout(() => {
                document.querySelector('.fade-in').classList.add('visible');
            }, 300);
        });

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>