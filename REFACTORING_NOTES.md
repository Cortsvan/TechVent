# TechVent - Asset Organization Update

## Overview
This update consolidates the CSS and JavaScript code across all PHP files into external, reusable assets to improve maintainability and reduce code duplication.

## Changes Made

### 1. Created Unified CSS File
- **Location**: `assets/css/main.css`
- **Purpose**: Contains all common styles shared across pages
- **Includes**:
  - CSS Variables (color scheme, spacing)
  - Global styles (reset, typography)
  - Navbar styles
  - Form styles
  - Button styles
  - Card styles
  - Alert styles
  - Animation classes
  - Responsive design rules

### 2. Created Common JavaScript File
- **Location**: `assets/js/main.js`
- **Purpose**: Contains reusable JavaScript functionality
- **Features**:
  - Form validation utilities
  - Alert management
  - Loading states
  - Animation handling
  - Smooth scrolling
  - Navbar scroll effects
  - Utility functions

### 3. Updated PHP Files
The following files have been updated to use external assets:

#### login.php
- Removed inline CSS (reduced from ~700 lines to ~350 lines)
- Added link to `assets/css/main.css`
- Replaced inline JavaScript with `assets/js/main.js`
- Kept only login-specific JavaScript functionality

#### register.php
- Removed inline CSS (reduced from ~800 lines to ~400 lines)
- Added link to `assets/css/main.css`
- Replaced inline JavaScript with `assets/js/main.js`
- Kept only registration-specific JavaScript functionality

#### user-profile.php
- Removed redundant CSS (reduced from ~1000+ lines to ~550 lines)
- Added link to `assets/css/main.css`
- Added page-specific override for main-content alignment
- Replaced inline JavaScript with `assets/js/main.js`
- Kept only profile-specific JavaScript functionality

## Benefits

### 1. Code Reduction
- **login.php**: ~50% reduction in file size
- **register.php**: ~50% reduction in file size
- **user-profile.php**: ~45% reduction in file size

### 2. Maintainability
- Single source of truth for common styles
- Easier to make site-wide design changes
- Consistent styling across all pages
- Modular JavaScript functions

### 3. Performance
- CSS and JS files can be cached by browsers
- Reduced page load times after initial visit
- Better code organization

### 4. Developer Experience
- Easier to debug and modify styles
- Cleaner PHP files focus on logic, not presentation
- Reusable JavaScript utilities
- Better separation of concerns

## File Structure
```
TechVent/
├── assets/
│   ├── css/
│   │   └── main.css        # Unified stylesheet
│   └── js/
│       └── main.js         # Common JavaScript functions
├── login.php               # Updated to use external assets
├── register.php            # Updated to use external assets
├── user-profile.php        # Updated to use external assets
└── [other files...]
```

## Usage

### Adding New Pages
When creating new pages, simply include:
```html
<!-- In the <head> section -->
<link rel="stylesheet" href="assets/css/main.css">

<!-- Before closing </body> -->
<script src="assets/js/main.js"></script>
```

### Adding Page-Specific Styles
For page-specific styles, add them after the main.css include:
```html
<link rel="stylesheet" href="assets/css/main.css">
<style>
/* Page-specific styles here */
</style>
```

### Using JavaScript Utilities
The main.js file provides several utility objects:
- `FormValidation` - Email, password validation functions
- `AlertUtils` - Success/error message display
- `LoadingUtils` - Button loading states
- `Utils` - General utility functions

Example usage:
```javascript
// Validate email
if (FormValidation.validateEmail(email)) {
    AlertUtils.showSuccess('Email is valid!');
}

// Show loading state
LoadingUtils.showButtonLoading(submitButton, 'Submitting...');
```

## Backwards Compatibility
All existing functionality remains intact. The visual appearance and behavior of all pages should be identical to before the refactoring.

## Future Improvements
- Consider adding CSS preprocessing (SASS/LESS)
- Implement CSS modules for component-specific styles
- Add JavaScript bundling and minification
- Consider implementing a design system with documented components