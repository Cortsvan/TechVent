<?php
/**
 * Login Page - TechVent
 * Handles user authentication with role-based redirects
 */

// Start session
session_start();

// Include database connection and session helpers
require_once 'config/db.php';
require_once 'includes/session.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_type'] === 'admin') {
        header('Location: admin-dashboard.php');
    } else {
        header('Location: user-dashboard.php');
    }
    exit();
}

// Initialize variables
$errors = [];
$email = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Validation
    if (empty($email)) {
        $errors[] = "Email address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please provide a valid email address.";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required.";
    }
    
    // If no errors, authenticate user
    if (empty($errors)) {
        try {
            $sql = "SELECT id, first_name, last_name, email, password, user_type, is_active FROM users WHERE email = ?";
            $user = fetchOne($sql, [$email]);
            
            if ($user && $password === $user['password']) {
                // Check if account is active
                if ($user['is_active'] == 0) {
                    $errors[] = "Your account has been deactivated. Please contact the administrator for assistance.";
                } else {
                    // Login successful - create session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                    $_SESSION['user_type'] = $user['user_type'];
                    
                    // Redirect based on user type
                    if ($user['user_type'] === 'admin') {
                        header('Location: admin-dashboard.php');
                    } else {
                        header('Location: user-dashboard.php');
                    }
                    exit();
                }
            } else {
                $errors[] = "Invalid email or password. Please try again.";
            }
        } catch (Exception $e) {
            $errors[] = "Login failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TechVent</title>
    
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
                        <a class="nav-link" href="index.html#about">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">Register</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-xl-5">
                    <div class="login-card fade-in">
                        <div class="login-header">
                            <div class="login-icon">
                                <i class="fas fa-sign-in-alt"></i>
                            </div>
                            <h1 class="login-title">Welcome Back</h1>
                            <p class="login-subtitle">Sign in to your TechVent account</p>
                        </div>

                        <!-- Display success messages -->
                        <?php
                        $message = getSessionMessage();
                        if ($message): ?>
                            <div class="alert-custom" style="background: rgba(40, 167, 69, 0.1); border: 1px solid rgba(40, 167, 69, 0.3); color: #28a745;">
                                <i class="fas fa-info-circle me-2"></i>
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>

                        <!-- Display errors -->
                        <?php if (!empty($errors)): ?>
                            <div class="alert-custom">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
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
                                           value="<?php echo htmlspecialchars($email); ?>" required>
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
                                           placeholder="Enter your password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback">
                                    Please enter your password.
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="rememberMe" name="rememberMe">
                                        <label class="form-check-label" for="rememberMe">
                                            Remember me
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <a href="forgot.html" class="forgot-password">
                                        <i class="fas fa-question-circle me-1"></i>Forgot Password?
                                    </a>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-login mb-3">
                                <i class="fas fa-sign-in-alt me-2"></i>Sign In
                            </button>
                        </form>

                        <div class="register-link">
                            Don't have an account? <a href="register.php">Create one here</a>
                        </div>

                        <!-- Demo credentials info -->
                        <div class="mt-4 p-3" style="background: rgba(49, 130, 206, 0.1); border-radius: 10px; border: 1px solid rgba(49, 130, 206, 0.2);">
                            <small style="color: #fff;">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Demo Accounts:</strong><br>
                                <strong>Admin:</strong> admin@techvent.com / admin123<br>
                                <strong>User:</strong> Register a new account or use existing user credentials
                            </small>
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
    
    <!-- Login Page Specific JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordField = document.getElementById('password');
            const togglePassword = document.getElementById('togglePassword');
            const emailField = document.getElementById('email');

            // Password visibility toggle
            if (togglePassword && passwordField) {
                togglePassword.addEventListener('click', function() {
                    const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordField.setAttribute('type', type);
                    this.querySelector('i').classList.toggle('fa-eye');
                    this.querySelector('i').classList.toggle('fa-eye-slash');
                });
            }

            // Real-time form validation
            if (emailField) {
                emailField.addEventListener('input', function() {
                    FormValidation.addValidationClass(this, FormValidation.validateEmail(this.value));
                });
            }

            if (passwordField) {
                passwordField.addEventListener('input', function() {
                    FormValidation.addValidationClass(this, this.value.trim().length > 0);
                });
            }

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