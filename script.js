// Helper function to display general error messages at the top of the form
function showError(message, fieldId = null) {
    // Remove any existing general error messages to prevent duplicates
    const existingError = document.querySelector('.form-error-message');
    if (existingError) {
        existingError.remove();
    }

    // Create the new error element
    const errorDiv = document.createElement('div');
    errorDiv.className = 'form-error-message';
    errorDiv.innerHTML = '<i class="fas fa-exclamation-circle" style="margin-right: 10px; font-size: 1.2rem;"></i>' + message;

    // Insert the error message into the registration form
    const registrationForm = document.querySelector('.form-container:not(.login-container) form');
    if (registrationForm) {
        registrationForm.insertBefore(errorDiv, registrationForm.firstChild);
    } else {
        console.error("Registration form not found for displaying general error message.");
        return;
    }

    // Automatically dismiss the error message after 5 seconds
    setTimeout(() => {
        errorDiv.style.opacity = '0'; // Start fade out
        errorDiv.style.transition = 'opacity 0.5s ease-out';
        setTimeout(() => {
            if (errorDiv.parentNode) {
                errorDiv.parentNode.removeChild(errorDiv);
            }
        }, 500); // Complete removal after transition
    }, 5000);

    // If a specific field ID is provided, highlight that field
    if (fieldId) {
        highlightField(fieldId);
    }
}

// Helper function to highlight invalid fields
function highlightField(fieldId) {
    const field = document.getElementById(fieldId);
    if (field) {
        field.classList.add('input-error'); // Apply error styling

        // Remove highlighting when the user starts typing or changes the value
        field.addEventListener('input', function() {
            this.classList.remove('input-error');
            // Also clear the specific field error message if it exists
            const fieldError = document.getElementById(fieldId + '-error');
            if (fieldError) {
                fieldError.textContent = '';
            }
        }, { once: true }); // Listener fires only once
    }
}

// Main form validation logic for the registration form
function validateForm() {
    let isValid = true; // Flag to track overall form validity

    // Clear all previous specific field error messages and input highlights
    document.querySelectorAll('.error-message').forEach(el => el.textContent = '');
    document.querySelectorAll('input, select').forEach(el => el.classList.remove('input-error'));

    // Remove any lingering general error message from previous attempts
    const existingGeneralError = document.querySelector('.form-error-message');
    if (existingGeneralError) {
        existingGeneralError.remove();
    }

    // Get form field values
    const fname = document.getElementById("fname");
    const email = document.getElementById("email");
    const password = document.getElementById("password");
    const confirmPassword = document.getElementById("confirm_password");
    const location = document.getElementById("location");
    const zip = document.getElementById("zip");
    const city = document.getElementById("city");
    const terms = document.getElementById("terms");

    // Regular expression patterns
    const namePattern = /^[A-Za-z\s.]+$/;
    const zipPattern = /^\d{4}$/; // Exactly 4 digits
    const mailPattern = /^\d{2}-\d{5}-[1-3]@student\.aiub\.edu$/; // Adjusted for student.aiub.edu
    const passPattern = /^\d{8}$/; // Exactly 8 digits

    // --- Validation Checks ---

    // Full Name validation
    if (fname.value.trim() === "" || !namePattern.test(fname.value.trim())) {
        document.getElementById("fname-error").textContent = "Full Name is required and can only contain letters, spaces, and periods.";
        highlightField("fname");
        isValid = false;
    }

    // Email validation
    if (email.value.trim() === "" || !mailPattern.test(email.value.trim())) {
        document.getElementById("email-error").textContent = "Invalid AIUB email format (e.g., XX-XXXXX-X@student.aiub.edu).";
        highlightField("email");
        isValid = false;
    }

    // Password validation
    if (password.value.trim() === "" || !passPattern.test(password.value.trim())) {
        document.getElementById("password-error").textContent = "Password must be exactly 8 digits (0-9).";
        highlightField("password");
        isValid = false;
    }

    // Confirm Password validation
    if (confirmPassword.value.trim() === "" || password.value !== confirmPassword.value) {
        document.getElementById("confirm-password-error").textContent = "Passwords do not match.";
        highlightField("confirm_password");
        isValid = false;
    }

    // Location validation
    if (location.value.trim() === "") {
        document.getElementById("location-error").textContent = "Location is required.";
        highlightField("location");
        isValid = false;
    }

    // Zip Code validation
    if (zip.value.trim() === "" || !zipPattern.test(zip.value.trim())) {
        document.getElementById("zip-error").textContent = "Zip Code must be exactly 4 digits.";
        highlightField("zip");
        isValid = false;
    }

    // City validation
    if (city.value === "") {
        document.getElementById("city-error").textContent = "Please select a preferred city.";
        highlightField("city");
        isValid = false;
    }

    // Terms and Conditions validation
    if (!terms.checked) {
        document.getElementById("terms-error").textContent = "You must agree to the terms and conditions.";
        highlightField("terms"); // Highlight the checkbox label visually
        isValid = false;
    }

    // If any validation failed, show a general error message at the top
    if (!isValid) {
        showError("Please correct the highlighted errors in the form.");
    }

    return isValid; // Return true if all checks pass, false otherwise
}

// Function to handle showing/hiding login/register forms or scrolling
function setupFormToggle() {
    const registerFormContainer = document.querySelector('.form-container:not(.login-container)');
    const showRegisterLink = document.getElementById('show-register');

    if (showRegisterLink && registerFormContainer) {
        showRegisterLink.addEventListener('click', function(event) {
            event.preventDefault(); // Prevent the default link behavior
            // Scroll to the registration form section smoothly
            registerFormContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }
}

// Dynamically apply colors to AQI table cells based on AQI value
function colorAqiTable() {
    const rows = document.querySelectorAll('table tr:not(:first-child)'); // Select all table rows except the header

    rows.forEach(row => {
        const aqiCell = row.cells[1]; // AQI value is in the second cell (index 1)
        const aqiValue = parseInt(aqiCell.textContent);

        let bgColor;
        let textColor = '#333'; // Default text color

        // Determine background and text color based on AQI range
        if (aqiValue >= 0 && aqiValue <= 50) {
            bgColor = '#d4edda'; // Good (light green)
            textColor = '#155724';
        } else if (aqiValue >= 51 && aqiValue <= 100) {
            bgColor = '#fff3cd'; // Moderate (light yellow)
            textColor = '#856404';
        } else if (aqiValue >= 101 && aqiValue <= 150) {
            bgColor = '#fce4ec'; // Unhealthy for Sensitive Groups (light pink/red)
            textColor = '#880e4f';
        } else if (aqiValue >= 151 && aqiValue <= 200) {
            bgColor = '#f8d7da'; // Unhealthy (light red)
            textColor = '#721c24';
        } else if (aqiValue >= 201 && aqiValue <= 300) {
            bgColor = '#f5c6cb'; // Very Unhealthy (darker red)
            textColor = '#721c24';
        } else { // 301+
            bgColor = '#dc3545'; // Hazardous (strong red)
            textColor = '#ffffff'; // White text for contrast
        }

        aqiCell.style.backgroundColor = bgColor;
        aqiCell.style.color = textColor;
        aqiCell.style.fontWeight = 'bold'; // Make AQI values stand out
    });
}

// Setup input field focus/blur animations
function setupInputAnimations() {
    const inputs = document.querySelectorAll('.form-group input, .form-group select');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.closest('.form-group').classList.add('focused');
        });

        input.addEventListener('blur', function() {
            this.closest('.form-group').classList.remove('focused');
        });
    });
}

// Setup the color picker preview
function setupColorPicker() {
    const favColorInput = document.getElementById('favcolor');
    const colorPreview = document.getElementById('colorPreview');

    if (favColorInput && colorPreview) {
        // Set initial preview color
        colorPreview.style.backgroundColor = favColorInput.value;

        // Update preview color when input changes
        favColorInput.addEventListener('input', function() {
            colorPreview.style.backgroundColor = this.value;
        });
    }
}

// Run all setup functions when the DOM is fully loaded
document.addEventListener('DOMContentLoaded', function() {
    colorAqiTable(); // Color the AQI table
    setupInputAnimations(); // Add input focus/blur effects
    setupColorPicker(); // Initialize and update color picker preview
    setupFormToggle(); // Handle scrolling to the registration form
});