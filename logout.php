<?php
session_start(); // Start the session to access session variables

// Unset all session variables
// This removes all data stored in the current user's session
$_SESSION = array();

// Destroy the session
// This completely deletes the session from the server
session_destroy();

// Delete the remember me cookie if it exists
// By setting its expiration time to a past value, the browser will delete it.
if (isset($_COOKIE['remembered_email'])) {
    setcookie('remembered_email', '', time() - 3600, '/'); // Set expiration to 1 hour in the past
}

// Redirect the user to the login page (index.php)
// This ensures the user is sent back to the main entry point after logging out.
header("Location: index.php");
exit(); // Always call exit() after a header redirect to prevent further code execution
?>
