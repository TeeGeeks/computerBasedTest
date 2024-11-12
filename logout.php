<?php
session_start();

// Destroy the session and unset session variables
session_unset();
session_destroy();

// Remove the remember me cookie if it exists
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, '/');
}

// Redirect to the login page
header("Location: index.php");
exit;
