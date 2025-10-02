/**
 * TechVent - Common JavaScript Functions
 * Shared functionality across all pages
 */

// DOM Content Loaded Event
document.addEventListener('DOMContentLoaded', function() {
    initializeCommonFeatures();
});

// Initialize common features
function initializeCommonFeatures() {
    initializeFadeInAnimations();
    initializeNavbarScrollEffect();
    initializeSmoothScrolling();
}

// Fade-in animations on scroll
function initializeFadeInAnimations() {
    const fadeElements = document.querySelectorAll('.fade-in');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });

    fadeElements.forEach(element => {
        observer.observe(element);
    });
}

// Navbar background change on scroll
function initializeNavbarScrollEffect() {
    const navbar = document.querySelector('.navbar-custom');
    if (!navbar) return;

    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) {
            navbar.style.background = 'rgba(26, 32, 44, 0.98)';
            navbar.style.boxShadow = '0 2px 20px rgba(49, 130, 206, 0.2)';
        } else {
            navbar.style.background = 'rgba(26, 32, 44, 0.95)';
            navbar.style.boxShadow = '0 2px 20px rgba(49, 130, 206, 0.1)';
        }
    });
}

// Smooth scrolling for navigation links
function initializeSmoothScrolling() {
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
}

// Form validation utilities
const FormValidation = {
    // Validate email format
    validateEmail: function(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },

    // Validate password strength
    validatePassword: function(password) {
        return password.length >= 8;
    },

    // Add validation classes to form fields
    addValidationClass: function(field, isValid) {
        field.classList.remove('is-valid', 'is-invalid');
        field.classList.add(isValid ? 'is-valid' : 'is-invalid');
    },

    // Show validation feedback
    showFeedback: function(field, message, isValid = false) {
        // Remove existing feedback
        const existingFeedback = field.parentNode.querySelector('.invalid-feedback, .valid-feedback');
        if (existingFeedback) {
            existingFeedback.remove();
        }

        // Create new feedback element
        const feedback = document.createElement('div');
        feedback.className = isValid ? 'valid-feedback' : 'invalid-feedback';
        feedback.textContent = message;
        
        // Insert after the field
        field.parentNode.insertBefore(feedback, field.nextSibling);
    }
};

// Alert utilities
const AlertUtils = {
    // Show success message
    showSuccess: function(message, container = null) {
        this.showAlert(message, 'success', container);
    },

    // Show error message
    showError: function(message, container = null) {
        this.showAlert(message, 'error', container);
    },

    // Show alert with specific type
    showAlert: function(message, type = 'error', container = null) {
        const alertClass = type === 'success' ? 'alert-custom-success' : 'alert-custom-error';
        
        const alertHtml = `
            <div class="${alertClass} alert-dismissible fade show" role="alert">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;

        if (container) {
            container.innerHTML = alertHtml + container.innerHTML;
        } else {
            // Insert at the beginning of the main content
            const mainContent = document.querySelector('.main-content .container, .main-content .container-fluid');
            if (mainContent) {
                mainContent.innerHTML = alertHtml + mainContent.innerHTML;
            }
        }

        // Auto-hide after 5 seconds
        setTimeout(() => {
            const alert = document.querySelector(`.${alertClass}`);
            if (alert) {
                alert.remove();
            }
        }, 5000);
    }
};

// Loading utilities
const LoadingUtils = {
    // Show loading spinner on button
    showButtonLoading: function(button, text = 'Loading...') {
        button.disabled = true;
        button.setAttribute('data-original-text', button.innerHTML);
        button.innerHTML = `
            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            ${text}
        `;
    },

    // Hide loading spinner from button
    hideButtonLoading: function(button) {
        button.disabled = false;
        const originalText = button.getAttribute('data-original-text');
        if (originalText) {
            button.innerHTML = originalText;
            button.removeAttribute('data-original-text');
        }
    }
};

// Utility functions
const Utils = {
    // Debounce function for performance
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    // Format date
    formatDate: function(date) {
        return new Date(date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    },

    // Escape HTML to prevent XSS
    escapeHtml: function(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// =========================
// PROFILE PAGE FUNCTIONS
// =========================

/**
 * Profile Management Functions
 */
const ProfileManager = {
    // Initialize profile features
    init: function() {
        this.setupProfileFeatures();
        this.enhanceFormValidation();
        this.setupOriginalValues();
        this.initializeFadeAnimations();
    },

    // Initialize fade-in animations for profile page
    initializeFadeAnimations: function() {
        setTimeout(() => {
            document.querySelectorAll('.fade-in').forEach(element => {
                element.classList.add('visible');
            });
        }, 300);
    },

    // Store original form values for reset functionality
    setupOriginalValues: function() {
        const form = document.querySelector('#editMode form');
        if (form) {
            const selects = form.querySelectorAll('select');
            selects.forEach(select => {
                select.setAttribute('data-original-value', select.value);
            });
            
            const inputs = form.querySelectorAll('input');
            inputs.forEach(input => {
                input.setAttribute('data-original-value', input.value);
            });
        }
    },

    // Setup profile edit/view toggle
    setupProfileFeatures: function() {
        const editBtn = document.getElementById('editProfileBtn');
        const cancelBtn = document.getElementById('cancelEditBtn');

        if (editBtn) {
            editBtn.addEventListener('click', () => {
                this.toggleEditMode(true);
            });
        }

        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => {
                this.toggleEditMode(false);
            });
        }
    },

    // Toggle between edit and view modes
    toggleEditMode: function(isEditing) {
        const viewMode = document.getElementById('viewMode');
        const editMode = document.getElementById('editMode');
        const editBtn = document.getElementById('editProfileBtn');

        if (!viewMode || !editMode || !editBtn) return;

        if (isEditing) {
            viewMode.classList.add('editing');
            editMode.classList.add('active');
            editBtn.innerHTML = '<i class="fas fa-eye me-2"></i>View Mode';
            
            // Update click handler
            editBtn.replaceWith(editBtn.cloneNode(true));
            document.getElementById('editProfileBtn').addEventListener('click', () => {
                this.toggleEditMode(false);
            });
            
            // Scroll to form
            editMode.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            
        } else {
            viewMode.classList.remove('editing');
            editMode.classList.remove('active');
            editBtn.innerHTML = '<i class="fas fa-edit me-2"></i>Edit Profile';
            
            // Update click handler
            editBtn.replaceWith(editBtn.cloneNode(true));
            document.getElementById('editProfileBtn').addEventListener('click', () => {
                this.toggleEditMode(true);
            });
            
            // Reset form to original values
            this.resetFormToOriginalValues();
        }
    },

    // Reset form fields to original values
    resetFormToOriginalValues: function() {
        const form = document.querySelector('#editMode form');
        if (!form) return;
        
        const inputs = form.querySelectorAll('input, select');
        inputs.forEach(input => {
            if (input.type !== 'submit' && input.type !== 'button') {
                if (input.tagName === 'SELECT') {
                    // Reset select to original value
                    const originalValue = input.getAttribute('data-original-value') || '';
                    input.value = originalValue;
                } else {
                    // Reset input to default value
                    input.value = input.defaultValue;
                }
            }
        });
    },

    // Enhanced form validation
    enhanceFormValidation: function() {
        const form = document.querySelector('#editMode form');
        if (!form) return;
        
        // Add real-time validation
        const requiredFields = form.querySelectorAll('input[required]');
        requiredFields.forEach(field => {
            field.addEventListener('blur', () => this.validateField(field));
            field.addEventListener('input', () => this.clearFieldError(field));
        });
        
        // Email validation
        const emailField = form.querySelector('input[type="email"]');
        if (emailField) {
            emailField.addEventListener('blur', () => this.validateEmail(emailField));
        }
        
        // Phone validation and formatting
        const phoneField = form.querySelector('input[type="tel"]');
        if (phoneField) {
            phoneField.addEventListener('input', () => this.formatPhoneNumber(phoneField));
        }
        
        // Form submission validation
        form.addEventListener('submit', (e) => {
            if (!this.validateForm(form)) {
                e.preventDefault();
                return false;
            }
            
            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
                submitBtn.disabled = true;
            }
        });
    },

    // Validate individual field
    validateField: function(field) {
        const value = field.value.trim();
        
        if (field.hasAttribute('required') && !value) {
            this.showFieldError(field, 'This field is required');
            return false;
        }
        
        this.clearFieldError(field);
        return true;
    },

    // Validate email field
    validateEmail: function(field) {
        const email = field.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (email && !emailRegex.test(email)) {
            this.showFieldError(field, 'Please enter a valid email address');
            return false;
        }
        
        this.clearFieldError(field);
        return true;
    },

    // Format phone number as user types
    formatPhoneNumber: function(field) {
        let value = field.value.replace(/\D/g, ''); // Remove non-digits
        
        if (value.length >= 6) {
            if (value.length <= 10) {
                value = value.replace(/(\d{3})(\d{3})(\d{0,4})/, '($1) $2-$3');
            } else {
                value = value.replace(/(\d{1})(\d{3})(\d{3})(\d{0,4})/, '+$1 ($2) $3-$4');
            }
        }
        
        field.value = value;
    },

    // Show field error
    showFieldError: function(field, message) {
        this.clearFieldError(field);
        
        field.classList.add('is-invalid');
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback d-block';
        errorDiv.textContent = message;
        
        field.parentNode.appendChild(errorDiv);
    },

    // Clear field error
    clearFieldError: function(field) {
        field.classList.remove('is-invalid');
        
        const errorDiv = field.parentNode.querySelector('.invalid-feedback');
        if (errorDiv) {
            errorDiv.remove();
        }
    },

    // Validate entire form
    validateForm: function(form) {
        let isValid = true;
        
        // Clear all previous errors
        form.querySelectorAll('.is-invalid').forEach(field => {
            this.clearFieldError(field);
        });
        
        // Validate required fields
        const requiredFields = form.querySelectorAll('input[required]');
        requiredFields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });
        
        // Validate email
        const emailField = form.querySelector('input[type="email"]');
        if (emailField && !this.validateEmail(emailField)) {
            isValid = false;
        }
        
        return isValid;
    }
};

// =========================
// USER MANAGEMENT UTILITIES
// =========================

const UserManager = {
    // Initialize user management functionality
    init: function() {
        this.initializeTable();
        this.initializeForms();
        this.initializeModals();
        this.initializeSearch();
        this.bindEvents();
    },

    // Initialize table enhancements
    initializeTable: function() {
        const table = document.querySelector('.table');
        if (table) {
            // Apply custom table class
            table.classList.add('table-custom');
            
            // Remove any conflicting Bootstrap classes
            table.classList.remove('table-striped', 'table-hover');
            
            // Ensure proper styling is applied
            table.style.backgroundColor = 'transparent';
            table.style.color = 'var(--text-light)';
            
            // Fix table container
            const tableContainer = table.closest('.table-responsive');
            if (tableContainer) {
                tableContainer.style.backgroundColor = 'transparent';
                tableContainer.style.borderRadius = '15px';
                tableContainer.style.overflow = 'hidden';
                tableContainer.style.boxShadow = '0 10px 30px rgba(0, 0, 0, 0.3)';
                tableContainer.style.border = '1px solid rgba(49, 130, 206, 0.15)';
            }
            
            // Style table headers
            const headers = table.querySelectorAll('thead th');
            headers.forEach(header => {
                header.style.backgroundColor = 'transparent';
                header.style.color = 'var(--text-light)';
                header.style.borderBottom = 'none';
            });
            
            // Style table cells
            const cells = table.querySelectorAll('tbody td');
            cells.forEach(cell => {
                cell.style.backgroundColor = 'transparent';
                cell.style.color = 'var(--text-light)';
                cell.style.borderBottom = 'none';
            });

            // Enhanced hover effects for user avatars
            const avatars = table.querySelectorAll('.user-avatar-table');
            avatars.forEach(avatar => {
                avatar.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.05)';
                    this.style.borderColor = 'rgba(49, 130, 206, 0.5)';
                });
                
                avatar.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                    this.style.borderColor = 'rgba(49, 130, 206, 0.3)';
                });
            });

            // Enhanced hover effects for action buttons
            const actionButtons = table.querySelectorAll('.btn-action');
            actionButtons.forEach(button => {
                button.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-1px)';
                });
                
                button.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        }
    },

    // Initialize form enhancements
    initializeForms: function() {
        // Replace regular selects with custom styled ones
        const selects = document.querySelectorAll('select.form-control-custom');
        selects.forEach(select => {
            select.classList.add('form-select-custom');
            // Remove browser default styling
            select.style.appearance = 'none';
            select.style.webkitAppearance = 'none';
            select.style.mozAppearance = 'none';
        });

        // Enhance search and filter section
        const searchForm = document.querySelector('form[method="GET"]');
        if (searchForm && !searchForm.closest('.search-filter-section')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'search-filter-section';
            
            searchForm.parentNode.insertBefore(wrapper, searchForm);
            wrapper.appendChild(searchForm);
            
            // Update form structure
            const formRow = searchForm.querySelector('.row');
            if (formRow) {
                formRow.className = 'filter-row';
                
                const cols = formRow.querySelectorAll('[class*="col-"]');
                cols.forEach((col, index) => {
                    col.className = 'filter-col';
                    
                    // Add labels if missing
                    const input = col.querySelector('input, select');
                    if (input && !col.querySelector('label')) {
                        const label = document.createElement('label');
                        if (input.type === 'text') {
                            label.textContent = 'Search Users';
                        } else if (input.name === 'type') {
                            label.textContent = 'User Type';
                        } else if (input.name === 'department') {
                            label.textContent = 'Department';
                        } else if (input.name === 'status') {
                            label.textContent = 'Status';
                        }
                        col.insertBefore(label, input);
                    }
                });
                
                // Style search button
                const searchBtn = formRow.querySelector('button[type="submit"]');
                if (searchBtn) {
                    searchBtn.className = 'search-btn';
                    if (!searchBtn.innerHTML.includes('Search')) {
                        searchBtn.innerHTML = '<i class="fas fa-search me-2"></i>Search';
                    }
                }
            }
        }
    },

    // Initialize modal enhancements
    initializeModals: function() {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            if (!modal.classList.contains('modal-custom')) {
                modal.classList.add('modal-custom');
            }
        });
    },

    // Initialize search functionality
    initializeSearch: function() {
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            // Add real-time search feedback
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const searchIcon = this.closest('.filter-col').querySelector('.fas');
                
                if (searchIcon) {
                    searchIcon.className = 'fas fa-spinner fa-spin';
                }
                
                searchTimeout = setTimeout(() => {
                    if (searchIcon) {
                        searchIcon.className = 'fas fa-search';
                    }
                }, 500);
            });
        }
    },

    // Bind events
    bindEvents: function() {
        // Auto-hide alerts
        this.autoHideAlerts();
        
        // Form submission loading states
        this.handleFormSubmissions();
        
        // User actions
        this.bindUserActions();
    },

    // Auto-hide success/error messages
    autoHideAlerts: function() {
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert-custom-success, .alert-custom-error');
            alerts.forEach(alert => {
                alert.style.transition = 'all 0.5s ease';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    alert.remove();
                }, 500);
            });
        }, 5000);
    },

    // Handle form submissions
    handleFormSubmissions: function() {
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    LoadingUtils.showButtonLoading(submitBtn, 'Processing...');
                }
            });
        });
    },

    // Bind user action events
    bindUserActions: function() {
        // Edit user buttons
        document.addEventListener('click', function(e) {
            if (e.target.closest('[onclick*="editUser"]')) {
                e.preventDefault();
                const button = e.target.closest('button');
                const userData = JSON.parse(button.getAttribute('data-user'));
                UserManager.showEditModal(userData);
            }
            
            if (e.target.closest('[onclick*="deleteUser"]')) {
                e.preventDefault();
                const button = e.target.closest('button');
                const onclick = button.getAttribute('onclick');
                const matches = onclick.match(/deleteUser\((\d+),\s*'([^']+)'\)/);
                if (matches) {
                    UserManager.showDeleteModal(matches[1], matches[2]);
                }
            }
        });
    },

    // Show edit user modal
    showEditModal: function(userData) {
        document.getElementById('edit_user_id').value = userData.id;
        document.getElementById('edit_first_name').value = userData.first_name;
        document.getElementById('edit_middle_name').value = userData.middle_name || '';
        document.getElementById('edit_last_name').value = userData.last_name;
        document.getElementById('edit_suffix').value = userData.suffix || '';
        document.getElementById('edit_email').value = userData.email;
        document.getElementById('edit_user_type').value = userData.user_type;
        
        const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
        modal.show();
    },

    // Show delete confirmation modal
    showDeleteModal: function(userId, userName) {
        document.getElementById('delete_user_id').value = userId;
        document.getElementById('delete_user_name').textContent = userName;
        
        const modal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
        modal.show();
    },

    // Enhance status badges
    enhanceStatusBadges: function() {
        const statusElements = document.querySelectorAll('.status-badge');
        statusElements.forEach(badge => {
            const text = badge.textContent.toLowerCase();
            if (text.includes('admin')) {
                badge.classList.add('status-admin');
            } else if (text.includes('active')) {
                badge.classList.add('status-active');
            } else {
                badge.classList.add('status-inactive');
            }
        });
    },

    // Update user avatars
    updateUserAvatars: function() {
        const avatars = document.querySelectorAll('.user-avatar');
        avatars.forEach(avatar => {
            // Add hover effect
            avatar.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.1)';
            });
            
            avatar.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
            });
        });
    }
};

// Export for modules (if using module system)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        FormValidation,
        AlertUtils,
        LoadingUtils,
        Utils,
        ProfileManager,
        UserManager
    };
}