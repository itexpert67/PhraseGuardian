<?php
/**
 * Logout Script
 * 
 * Logs the user out by destroying the session and redirecting to the homepage.
 */

// Start session
session_start();

// Clear all session variables
$_SESSION = [];

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to homepage
header('Location: index.php');
exit();