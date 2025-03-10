<?php
/**
 * Index Redirect
 * 
 * This file redirects to the main application or login page based on session status
 */

// Start session
session_start();

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    // Redirect to subscription page if logged in
    header('Location: my_subscription.php');
    exit();
} else {
    // Redirect to login page if not logged in
    header('Location: login.php');
    exit();
}