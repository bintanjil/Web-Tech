<?php
// index.php - Login and Registration Page
session_start();

// Database configuration (MySQLi Procedural)
$host = 'localhost';
$dbname = 'air_quality_index';
$db_username = 'root';
$db_password = '';

$rememberedEmail = '';
$loginError = '';
$registrationSuccess = '';

// Establish database connection
$conn = mysqli_connect($host, $db_username, $db_password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if there's a remembered email in cookies
if (isset($_COOKIE['remembered_email'])) {
    $rememberedEmail = htmlspecialchars($_COOKIE['remembered_email']);
}

// Check for registration success message from process.php
if (isset($_SESSION['registration_success_message'])) {
    $registrationSuccess = $_SESSION['registration_success_message'];
    unset($_SESSION['registration_success_message']);
}

// Process login form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    if (empty($_POST['login_email']) || empty($_POST['login_password'])) {
        $loginError = "Please enter both email and password.";
    } else {
        $email = mysqli_real_escape_string($conn, $_POST['login_email']);
        $submittedPassword = $_POST['login_password'];
        $remember = isset($_POST['remember']);

        // Prepare and execute query using MySQLi Procedural
        $stmt = mysqli_prepare($conn, "SELECT id, f_name, email, password FROM user WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email); // "s" for string, for the email parameter
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if ($user && password_verify($submittedPassword, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_fname'] = $user['f_name'];

            session_regenerate_id(true);

            if ($remember) {
                setcookie('remembered_email', $email, [
                    'expires' => time() + (30 * 24 * 60 * 60),
                    'path' => '/',
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
            } else {
                if (isset($_COOKIE['remembered_email'])) {
                    setcookie('remembered_email', '', [
                        'expires' => time() - 3600,
                        'path' => '/',
                        'httponly' => true,
                        'samesite' => 'Lax'
                    ]);
                }
            }

            // Close statement
            mysqli_stmt_close($stmt);
            // Close connection before redirecting
            mysqli_close($conn);

            header("Location: request.php");
            exit();
        } else {
            $loginError = "Invalid email or password.";
        }
        // Close statement if user not found or password incorrect
        mysqli_stmt_close($stmt);
    }
}

// Close the connection at the end of the script if it hasn't been closed already (e.g., due to redirect)
if (isset($conn) && $conn) {
    mysqli_close($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Air Quality Monitoring Dashboard</title>
    <style>
        /* Simplified CSS for better readability and basic styling */
        body {
            font-family: Arial, sans-serif;
            background-color: #e0f7fa; /* Light blue background */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
            color: #263238; /* Dark grayish blue text */
        }

        .main-container {
            background-color: #eceff1; /* Slightly lighter blue-gray */
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 1000px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* --- Header Styles --- */
        header {
            background: linear-gradient(to right, #1a237e, #3f51b5); /* Deep navy blue gradient */
            color: white;
            padding: 25px;
            text-align: center;
            border-radius: 8px;
            margin-bottom: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            position: relative;
            overflow: hidden;
        }

        .header-logo {
             width: 70px; /* Slightly larger logo */
            height: 70px;
            object-fit: contain;
            margin-bottom: 15px;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3)); /* Subtle shadow for the logo */
        }

        header h1 {
            font-size: 2.2rem;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
            font-weight: bold;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
        }

        header p {
            font-size: 1.05rem;
            opacity: 0.95;
            margin-top: 5px;
        }
        /* --- End Header Styles --- */

        .container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .left-panel, .right-section {
            flex: 1;
            min-width: 300px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .box, .form-container {
            background-color: #f5f5f5; /* Lighter blue-gray */
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #c5cae9; /* Light purple-blue border */
            flex-grow: 1;
        }

        .box h3, .form-container h2 {
            color: #283593; /* Darker blue for headings */
            margin-bottom: 15px;
            font-size: 1.3rem;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #303f9f; /* Darker blue for labels */
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            width: calc(100% - 22px);
            padding: 10px;
            border: 1px solid #9fa8da; /* Light blue-purple border */
            border-radius: 4px;
            font-size: 1rem;
            background-color: #fff; /* White input background */
            color: #37474f; /* Darker grayish blue input text */
        }

        button {
            background-color: #303f9f; /* Darker blue for buttons */
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #283593; /* Slightly darker on hover */
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            border: 1px solid #c5cae9;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #e8eaf6; /* Very light blue-purple */
            color: #303f9f;
        }

        /* AQI Color Coding */
        .aqi-good { background-color: #d4edda; color: #155724; }
        .aqi-moderate { background-color: #fff3cd; color: #856404; }
        .aqi-unhealthy-sensitive { background-color: #ffeeba; color: #8a6000; }
        .aqi-unhealthy { background-color: #f8d7da; color: #721c24; }
        .aqi-very-unhealthy { background-color: #e2d4ed; color: #4b0082; }
        .aqi-hazardous { background-color: #d8c3e8; color: #6a0572; }

        .error-message {
            color: #d32f2f; /* Red error message */
            font-size: 0.8rem;
            margin-top: 5px;
            display: none;
        }

        .success-message {
            color: #1b5e20; /* Dark green success message */
            font-size: 0.9rem;
            text-align: center;
            margin-top: 15px;
            padding: 10px;
            background-color: #c8e6c9; /* Light green */
            border: 1px solid #1b5e20;
            border-radius: 4px;
        }

        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 15px;
        }

        .checkbox-container input[type="checkbox"] {
            width: auto;
        }

        .checkbox-container label {
            margin-bottom: 0;
            font-weight: normal;
        }

        .checkbox-container a {
            color: #303f9f;
            text-decoration: none;
        }

        .checkbox-container a:hover {
            text-decoration: underline;
        }

        .color-picker-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .color-preview {
            width: 25px;
            height: 25px;
            border-radius: 50%;
            border: 1px solid #7986cb; /* Light blue-purple border */
        }

        .form-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
        }

        .form-footer a {
            color: #303f9f;
            text-decoration: none;
        }

        .form-footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            .left-panel, .right-section {
                min-width: unset;
                width: 100%;
            }
            .main-container {
                padding: 15px;
            }
            header {
                padding: 15px;
            }
            header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <header>
            <img src="images.png" alt="AQI Logo" class="header-logo">
            <h1>Air Quality Monitoring Dashboard</h1>
            <p>Real-time air quality data for major cities in Bangladesh</p>
        </header>

        <div class="container">
            <div class="left-panel">
                <div class="box">
                    <h3>Current Air Quality Index</h3>
                    <p>Latest AQI measurements for major cities:</p>
                    <table>
                        <thead>
                            <tr>
                                <th>City</th>
                                <th>AQI Value</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="aqi-unhealthy"><td>Dhaka</td><td>150</td><td>Unhealthy</td></tr>
                            <tr class="aqi-unhealthy-sensitive"><td>Tangail</td><td>120</td><td>Unhealthy for Sensitive Groups</td></tr>
                            <tr class="aqi-unhealthy-sensitive"><td>Rajshahi</td><td>130</td><td>Unhealthy for Sensitive Groups</td></tr>
                            <tr class="aqi-unhealthy-sensitive"><td>Khulna</td><td>140</td><td>Unhealthy for Sensitive Groups</td></tr>
                            <tr class="aqi-unhealthy-sensitive"><td>Sylhet</td><td>110</td><td>Unhealthy for Sensitive Groups</td></tr>
                            <tr class="aqi-unhealthy"><td>Barishal</td><td>160</td><td>Unhealthy</td></tr>
                            <tr class="aqi-unhealthy"><td>Rangpur</td><td>170</td><td>Unhealthy</td></tr>
                            <tr class="aqi-unhealthy"><td>Mymenshing</td><td>180</td><td>Unhealthy</td></tr>
                            <tr class="aqi-unhealthy"><td>Gazipur</td><td>190</td><td>Unhealthy</td></tr>
                            <tr class="aqi-very-unhealthy"><td>Cox's Bazar</td><td>200</td><td>Very Unhealthy</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="box">
                    <h3>AQI Scale Reference</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>AQI Range</th>
                                <th>Health Concern</th>
                                <th>Color Code</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="aqi-good"><td>0 - 50</td><td>Good</td><td>Green</td></tr>
                            <tr class="aqi-moderate"><td>51 - 100</td><td>Moderate</td><td>Yellow</td></tr>
                            <tr class="aqi-unhealthy-sensitive"><td>101 - 150</td><td>Unhealthy for Sensitive Groups</td><td>Orange</td></tr>
                            <tr class="aqi-unhealthy"><td>151 - 200</td><td>Unhealthy</td><td>Red</td></tr>
                            <tr class="aqi-very-unhealthy"><td>201 - 300</td><td>Very Unhealthy</td><td>Purple</td></tr>
                            <tr class="aqi-hazardous"><td>301+</td><td>Hazardous</td><td>Maroon</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="right-section">
                <div class="form-container">
                    <h2>Create Account</h2>
                    <form action="process.php" method="POST" onsubmit="return validateForm()">
                        <div class="form-group">
                            <label for="fname">Full Name</label>
                            <input type="text" id="fname" name="fname" placeholder="Enter your full name">
                            <div id="fname-error" class="error-message"></div>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" placeholder="xx-xxxxx-x@student.aiub.edu">
                            <div id="email-error" class="error-message"></div>
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" placeholder="exactly 8 digits (0-9)">
                            <div id="password-error" class="error-message"></div>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password">
                            <div id="confirm-password-error" class="error-message"></div>
                        </div>
                        <div class="form-group">
                            <label for="location">Location</label>
                            <input type="text" id="location" name="location" placeholder="Your current location">
                            <div id="location-error" class="error-message"></div>
                        </div>
                        <div class="form-group">
                            <label for="zip">Zip Code</label>
                            <input type="text" id="zip" name="zip" placeholder="4-digit zip code">
                            <div id="zip-error" class="error-message"></div>
                        </div>
                        <div class="form-group">
                            <label for="city">Preferred City</label>
                            <select id="city" name="city">
                                <option value="">Select City</option>
                                <option value="Dhaka">Dhaka</option>
                                <option value="Chittagong">Chittagong</option>
                                <option value="Khulna">Khulna</option>
                                <option value="Rangpur">Rangpur</option>
                                <option value="Rajshahi">Rajshahi</option>
                                <option value="Barishal">Barishal</option>
                                <option value="Comilla">Comilla</option>
                                <option value="Tangail">Tangail</option>
                                <option value="Sylhet">Sylhet</option>
                                <option value="Mymenshing">Mymenshing</option>
                                <option value="Gazipur">Gazipur</option>
                                <option value="Cox's Bazar">Cox's Bazar</option>
                            </select>
                            <div id="city-error" class="error-message"></div>
                        </div>
                        <div class="form-group">
                            <label>Favorite Color</label>
                            <div class="color-picker-container">
                                <input type="color" id="favcolor" name="favcolor" value="#303f9f">
                                <span class="color-preview" id="colorPreview" style="background-color: #303f9f;"></span>
                            </div>
                        </div>
                        <div class="checkbox-container">
                            <input type="checkbox" id="terms" name="terms">
                            <label for="terms">I agree to the <a href="#">terms and conditions</a></label>
                            <div id="terms-error" class="error-message"></div>
                        </div>
                        <button type="submit">Register</button>
                        <?php if (!empty($registrationSuccess)): ?>
                            <div id="form-submission-message" class="success-message"><?php echo $registrationSuccess; ?></div>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="form-container login-container">
                    <h2>Log In</h2>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                        <div class="form-group">
                            <label for="login_email">Email</label>
                            <input type="email" id="login_email" name="login_email" placeholder="Enter your email" value="<?php echo $rememberedEmail; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="login_password">Password</label>
                            <input type="password" id="login_password" name="login_password" placeholder="Enter your password" required>
                        </div>
                        <div class="checkbox-container">
                            <input type="checkbox" id="remember" name="remember" <?php echo !empty($rememberedEmail) ? 'checked' : ''; ?>>
                            <label for="remember">Remember me</label>
                        </div>
                        <button type="submit" name="login">Log In</button>
                        <?php if (!empty($loginError)): ?>
                            <div class="error-message" style="text-align: center; display: block;"><?php echo $loginError; ?></div>
                        <?php endif; ?>
                    </form>
                    <div class="form-footer">
                        <p>Don't have an account? <a href="#" id="show-register">Register Now</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const favColorInput = document.getElementById('favcolor');
            const colorPreview = document.getElementById('colorPreview');

            colorPreview.style.backgroundColor = favColorInput.value;

            favColorInput.addEventListener('input', function() {
                colorPreview.style.backgroundColor = this.value;
            });

            // Form validation error display
            const form = document.querySelector('form[onsubmit="return validateForm()"]');
            if (form) {
                form.addEventListener('submit', function(event) {
                    if (!validateForm()) {
                        event.preventDefault();
                    }
                });
            }

            // Show register form
            const showRegisterBtn = document.getElementById('show-register');
            if (showRegisterBtn) {
                showRegisterBtn.addEventListener('click', function(event) {
                    event.preventDefault();
                    document.querySelector('.form-container:first-of-type').scrollIntoView({ 
                        behavior: 'smooth' 
                    });
                });
            }
        });

        function validateForm() {
            let isValid = true;
            const errorMessages = document.querySelectorAll('.error-message');
            const inputs = document.querySelectorAll('input, select');

            // Clear previous errors
            errorMessages.forEach(el => el.textContent = '');
            inputs.forEach(el => el.classList.remove('input-error'));

            // Validation functions
            function showError(elementId, message) {
                const errorElement = document.getElementById(elementId);
                errorElement.textContent = message;
                errorElement.style.display = 'block';
                document.getElementById(elementId.split('-')[0]).classList.add('input-error');
                isValid = false;
            }

            // Full Name validation
            const fname = document.getElementById('fname');
            if (fname.value.trim() === '') {
                showError('fname-error', 'Full Name is required.');
            }

            // Email validation
            const email = document.getElementById('email');
            const emailPattern = /^\d{2}-\d{5}-\d@student\.aiub\.edu$/;
            if (email.value.trim() === '') {
                showError('email-error', 'Email is required.');
            } else if (!emailPattern.test(email.value.trim())) {
                showError('email-error', 'Please use a valid AIUB student email (e.g., xx-xxxxx-x@student.aiub.edu).');
            }

            // Password validation
            const password = document.getElementById('password');
            const passwordPattern = /^\d{8}$/;
            if (password.value.trim() === '') {
                showError('password-error', 'Password is required.');
            } else if (!passwordPattern.test(password.value.trim())) {
                showError('password-error', 'Password must be exactly 8 digits (0-9).');
            }

            // Confirm Password validation
            const confirmPassword = document.getElementById('confirm_password');
            if (confirmPassword.value.trim() === '') {
                showError('confirm-password-error', 'Confirm Password is required.');
            } else if (confirmPassword.value !== password.value) {
                showError('confirm-password-error', 'Passwords do not match.');
            }

            // Location validation
            const location = document.getElementById('location');
            if (location.value.trim() === '') {
                showError('location-error', 'Location is required.');
            }

            // Zip Code validation
            const zip = document.getElementById('zip');
            const zipPattern = /^\d{4}$/;
            if (zip.value.trim() === '') {
                showError('zip-error', 'Zip Code is required.');
            } else if (!zipPattern.test(zip.value.trim())) {
                showError('zip-error', 'Zip Code must be exactly 4 digits.');
            }

            // City validation
            const city = document.getElementById('city');
            if (city.value === '') {
                showError('city-error', 'Please select a preferred city.');
            }

            // Terms validation
            const terms = document.getElementById('terms');
            if (!terms.checked) {
                showError('terms-error', 'You must agree to the terms and conditions.');
            }

            return isValid;
        }
    </script>
</body>
</html>