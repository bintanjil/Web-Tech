<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session (for potential use with success/error messages redirecting to index.php)
session_start();

// Database configuration
$host = 'localhost';
$dbname = 'air_quality_index';
$username = 'root';
$password = '';

$registration_successful = false;
$show_confirmation = false; // Flag to show confirmation dialog
$error_message = '';
$form_data = []; // To store form data for confirmation

// Establish MySQLi database connection
$conn = mysqli_connect($host, $username, $password, $dbname);

// Check connection
if (!$conn) {
    // If connection fails, log error and set a user-friendly message
    error_log("Database Connection Failed: " . mysqli_connect_error());
    $error_message = "Could not connect to the database. Please try again later.";
    // We can't proceed with database operations, so exit or show a general error page.
    // For this example, we'll let the HTML part handle displaying this error.
} else {
    // Process form submission only if connected to the database
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Check if this is the confirmation post
        if (isset($_POST['confirmed']) && $_POST['confirmed'] === 'true') {
            // This is the confirmed submission - proceed with database operation
            
            // Retrieve form data from session
            if (isset($_SESSION['registration_form_data'])) {
                $form_data = $_SESSION['registration_form_data'];
                
                // Sanitize and validate input again (for security)
                // Use mysqli_real_escape_string for string data before using in queries
                $fname = mysqli_real_escape_string($conn, $form_data["fname"] ?? '');
                $email = mysqli_real_escape_string($conn, $form_data["email"] ?? '');
                $password_raw = $form_data["password"] ?? ''; // Password will be hashed, so no direct escaping needed here
                $location = mysqli_real_escape_string($conn, $form_data["location"] ?? '');
                $zip = mysqli_real_escape_string($conn, $form_data["zip"] ?? '');
                $city = mysqli_real_escape_string($conn, $form_data["city"] ?? '');
                $favcolor = mysqli_real_escape_string($conn, $form_data["favcolor"] ?? '#0288d1');

                // Hash the password
                $hashed_password = password_hash($password_raw, PASSWORD_DEFAULT);

                // Prepare the INSERT statement using MySQLi Procedural prepared statements
                $stmt = mysqli_prepare($conn, "INSERT INTO user (f_name, email, password, location, zip_code, prefered_city) VALUES (?, ?, ?, ?, ?, ?)");

                if ($stmt) {
                    // Bind parameters
                    mysqli_stmt_bind_param($stmt, "ssssss", $fname, $email, $hashed_password, $location, $zip, $city);

                    // Execute the statement
                    if (mysqli_stmt_execute($stmt)) {
                        $registration_successful = true;

                        // Set the favorite color cookie
                        setcookie("fav_color", $favcolor, [
                            'expires' => time() + (86400 * 30),
                            'path' => '/',
                            'httponly' => true,
                            'samesite' => 'Lax'
                        ]);

                        // Set success message for index.php and redirect
                        $_SESSION['registration_success_message'] = "Registration successful! You can now log in.";
                        // Clear the session data after successful registration and before redirect
                        unset($_SESSION['registration_form_data']); 
                        mysqli_stmt_close($stmt); // Close statement before redirect
                        mysqli_close($conn); // Close connection before redirect
                        header("Location: index.php");
                        exit();
                    } else {
                        $error_message = "Error inserting data: " . mysqli_stmt_error($stmt);
                        error_log("MySQLi Insert Error: " . mysqli_stmt_error($stmt));
                    }
                    mysqli_stmt_close($stmt); // Close statement
                } else {
                    $error_message = "Database prepare error: " . mysqli_error($conn);
                    error_log("MySQLi Prepare Error: " . mysqli_error($conn));
                }
            } else {
                $error_message = "Form data expired. Please submit the form again.";
            }
        } elseif (isset($_POST['cancel'])) {
            // User clicked cancel - redirect to index.php
            unset($_SESSION['registration_form_data']);
            header("Location: index.php");
            exit();
        } else {
            // This is the initial form submission - show confirmation
            
            // Sanitize and validate input
            $form_data = [
                'fname' => htmlspecialchars($_POST["fname"] ?? ''),
                'email' => htmlspecialchars($_POST["email"] ?? ''),
                'password' => $_POST["password"] ?? '', // Keep raw for confirmation display, hash later
                'confirm_password' => $_POST["confirm_password"] ?? '', // Get confirm password for validation
                'location' => htmlspecialchars($_POST["location"] ?? ''),
                'zip' => htmlspecialchars($_POST["zip"] ?? ''),
                'city' => htmlspecialchars($_POST["city"] ?? ''),
                'favcolor' => htmlspecialchars($_POST["favcolor"] ?? '#0288d1')
            ];

            // Server-side Validation
            if (empty($form_data['fname']) || empty($form_data['email']) || empty($form_data['password']) || 
                empty($form_data['confirm_password']) || empty($form_data['location']) || empty($form_data['zip']) || empty($form_data['city'])) {
                $error_message = "All fields are required.";
            } elseif ($form_data['password'] !== $form_data['confirm_password']) {
                 $error_message = "Passwords do not match.";
            } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
                $error_message = "Invalid email format.";
            } elseif (!preg_match('/^\d{2}-\d{5}-[1-3]@student\.aiub\.edu$/', $form_data['email'])) {
                $error_message = "Email must be in AIUB student format (e.g., XX-XXXXX-X@student.aiub.edu).";
            } elseif (!preg_match('/^\d{8}$/', $form_data['password'])) {
                $error_message = "Password must be exactly 8 digits.";
            } elseif (!preg_match('/^\d{4}$/', $form_data['zip'])) {
                $error_message = "Zip Code must be exactly 4 digits.";
            }

            if (empty($error_message)) {
                // Check if email already exists using MySQLi Procedural prepared statements
                $checkStmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM user WHERE email = ?");
                if ($checkStmt) {
                    mysqli_stmt_bind_param($checkStmt, "s", $form_data['email']);
                    mysqli_stmt_execute($checkStmt);
                    mysqli_stmt_bind_result($checkStmt, $email_count);
                    mysqli_stmt_fetch($checkStmt);
                    mysqli_stmt_close($checkStmt);

                    if ($email_count > 0) {
                        $error_message = "This email is already registered. Please use a different email.";
                    } else {
                        // Store form data in session for confirmation
                        $_SESSION['registration_form_data'] = $form_data;
                        $show_confirmation = true;
                    }
                } else {
                    $error_message = "Database prepare error: " . mysqli_error($conn);
                    error_log("MySQLi Prepare Error (email check): " . mysqli_error($conn));
                }
            }
        }
    } else {
        // If the page is accessed directly without a POST request
        $error_message = "Access denied. Please submit the form from the registration page.";
    }
    // Close the connection if it was successfully opened and not closed by a redirect
    mysqli_close($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php 
        if ($registration_successful) echo "Registration Successful";
        elseif ($show_confirmation) echo "Confirm Registration";
        else echo "Registration Failed"; 
    ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Simplified CSS for process.php */
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            box-sizing: border-box;
            color: #333;
        }
        .container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        .success-icon, .error-icon, .confirm-icon {
            font-size: 3.5rem;
            margin-bottom: 25px;
        }
        .success-icon { color: #28a745; } /* Green for success */
        .error-icon { color: #dc3545; }   /* Red for error */
        .confirm-icon { color: #ffc107; } /* Yellow/Orange for confirmation */

        h2 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.8rem;
        }
        p {
            color: #555;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .button-container {
            margin-top: 30px;
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        .button {
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            color: white;
            font-weight: bold;
            font-size: 1rem;
            transition: background-color 0.3s ease, transform 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .button:hover {
            transform: translateY(-2px);
            opacity: 0.9;
        }
        .button-confirm {
            background-color: #28a745; /* Green */
        }
        .button-cancel {
            background-color: #dc3545; /* Red */
        }
        .button-home {
            background-color: #007bff; /* Blue */
        }
        .error-message {
            color: #dc3545;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: left;
            font-size: 0.9em;
            word-wrap: break-word; /* Ensure long error messages wrap */
        }
        .confirmation-details {
            text-align: left;
            margin: 20px 0;
            padding: 15px;
            background-color: #e9ecef;
            border-radius: 5px;
            border-left: 5px solid #ffc107;
        }
        .confirmation-details p {
            margin: 8px 0;
            color: #333;
            font-size: 1.05em;
        }
        .confirmation-details p strong {
            color: #222;
        }

        /* Responsive adjustments */
        @media (max-width: 600px) {
            .container {
                padding: 20px;
                margin: 0 10px;
            }
            .button-container {
                flex-direction: column;
                gap: 10px;
            }
            .button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($registration_successful): ?>
            <div class="success-icon"><i class="fas fa-check-circle"></i></div>
            <h2>Registration Successful!</h2>
            <p>Thank you for registering with our Air Quality Monitoring service. Your account has been created successfully.</p>
            <div class="button-container">
                <a href="index.php" class="button button-home"><i class="fas fa-home"></i> Go to Home</a>
            </div>
        
        <?php elseif ($show_confirmation): ?>
            <div class="confirm-icon"><i class="fas fa-question-circle"></i></div>
            <h2>Confirm Registration</h2>
            <p>Please review your information before submitting:</p>
            
            <div class="confirmation-details">
                <p><strong>Full Name:</strong> <?php echo $form_data['fname']; ?></p>
                <p><strong>Email:</strong> <?php echo $form_data['email']; ?></p>
                <p><strong>Location:</strong> <?php echo $form_data['location']; ?></p>
                <p><strong>Zip Code:</strong> <?php echo $form_data['zip']; ?></p>
                <p><strong>Preferred City:</strong> <?php echo $form_data['city']; ?></p>
            </div>
            
            <form method="post">
                <input type="hidden" name="confirmed" value="true">
                <div class="button-container">
                    <button type="submit" class="button button-confirm"><i class="fas fa-check"></i> Confirm</button>
                    <button type="submit" name="cancel" class="button button-cancel"><i class="fas fa-times"></i> Cancel</button>
                </div>
            </form>
        
        <?php elseif (!empty($error_message)): ?>
            <div class="error-icon"><i class="fas fa-times-circle"></i></div>
            <h2>Registration Failed!</h2>
            <p class="error-message"><?php echo $error_message; ?></p>
            <div class="button-container">
                <a href="index.php" class="button button-home"><i class="fas fa-arrow-left"></i> Try Again</a>
            </div>
        
        <?php else: ?>
            <div class="error-icon"><i class="fas fa-exclamation-circle"></i></div>
            <h2>Access Denied</h2>
            <p>Please submit the form from the registration page.</p>
            <div class="button-container">
                <a href="index.php" class="button button-home"><i class="fas fa-home"></i> Go to Home</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>