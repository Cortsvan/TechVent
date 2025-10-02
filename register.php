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
    
    <!-- TechVent Main Stylesheet -->
    <link rel="stylesheet" href="assets/css/main.css">
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
    
    <!-- TechVent Main JavaScript -->
    <script src="assets/js/main.js"></script>
    
    <!-- Register Page Specific JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordField = document.getElementById('password');
            const confirmPasswordField = document.getElementById('confirmPassword');
            const togglePassword = document.getElementById('togglePassword');
            const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
            const emailField = document.getElementById('email');

            // Password visibility toggles
            if (togglePassword && passwordField) {
                togglePassword.addEventListener('click', function() {
                    const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordField.setAttribute('type', type);
                    this.querySelector('i').classList.toggle('fa-eye');
                    this.querySelector('i').classList.toggle('fa-eye-slash');
                });
            }

            if (toggleConfirmPassword && confirmPasswordField) {
                toggleConfirmPassword.addEventListener('click', function() {
                    const type = confirmPasswordField.getAttribute('type') === 'password' ? 'text' : 'password';
                    confirmPasswordField.setAttribute('type', type);
                    this.querySelector('i').classList.toggle('fa-eye');
                    this.querySelector('i').classList.toggle('fa-eye-slash');
                });
            }

            // Form validation
            if (passwordField) {
                passwordField.addEventListener('input', function() {
                    FormValidation.addValidationClass(this, FormValidation.validatePassword(this.value));
                    
                    // Re-validate confirm password if it has value
                    if (confirmPasswordField && confirmPasswordField.value.length > 0) {
                        FormValidation.addValidationClass(confirmPasswordField, 
                            confirmPasswordField.value === this.value && this.value.length >= 8);
                    }
                });
            }

            if (confirmPasswordField) {
                confirmPasswordField.addEventListener('input', function() {
                    const isValid = this.value === passwordField.value && passwordField.value.length >= 8;
                    FormValidation.addValidationClass(this, isValid);
                });
            }

            if (emailField) {
                emailField.addEventListener('input', function() {
                    FormValidation.addValidationClass(this, FormValidation.validateEmail(this.value));
                });
            }

            // Validate required fields
            const requiredFields = ['firstName', 'lastName'];
            requiredFields.forEach(fieldName => {
                const field = document.getElementById(fieldName);
                if (field) {
                    field.addEventListener('input', function() {
                        FormValidation.addValidationClass(this, this.value.trim().length > 0);
                    });
                }
            });

            // Trigger fade-in animation
            setTimeout(() => {
                const fadeElement = document.querySelector('.fade-in');
                if (fadeElement) {
                    fadeElement.classList.add('visible');
                }
            }, 300);
        });
    </script>
</body>
</html>