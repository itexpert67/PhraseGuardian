<?php
/**
 * User Registration Page
 * 
 * This file handles new user registration
 */

// Include database connection
require_once 'db_connect.php';

// Start session
session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: my_subscription.php');
    exit();
}

$error = '';
$success = false;

// Process registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Basic validation
    if (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = 'All fields are required.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check if username already exists
        $existingUser = db_select("SELECT id FROM users WHERE username = ?", [$username])[0] ?? null;
        if ($existingUser) {
            $error = 'Username already exists. Please choose a different one.';
        } else {
            // Check if email already exists
            $existingEmail = db_select("SELECT id FROM users WHERE email = ?", [$email])[0] ?? null;
            if ($existingEmail) {
                $error = 'Email already registered. Please use a different email address.';
            } else {
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $userId = db_insert('users', [
                    'username' => $username,
                    'email' => $email,
                    'password' => $hashedPassword,
                    'is_subscribed' => 0,
                    'subscription_tier' => 'Basic',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                if ($userId) {
                    $success = true;
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Text Processing Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-900 text-white min-h-screen flex items-center justify-center py-12">
    <div class="max-w-md w-full mx-auto p-6">
        <div class="bg-gray-800 rounded-lg p-8 shadow-lg">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-purple-400">Text Processing Platform</h1>
                <p class="text-gray-400 mt-2">Create a new account</p>
            </div>
            
            <?php if ($error): ?>
                <div class="bg-red-900 text-white p-4 rounded mb-6">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-900 text-white p-4 rounded mb-6">
                    <p>Registration successful! Your account has been created.</p>
                    <p class="mt-2">
                        <a href="login.php" class="text-green-300 underline">Click here to log in</a> or 
                        <a href="checkout.php?plan=1&signup=success" class="text-green-300 underline">subscribe to a plan</a>.
                    </p>
                </div>
            <?php else: ?>
                <form method="post" action="">
                    <div class="mb-6">
                        <label for="username" class="block text-gray-300 mb-2">Username</label>
                        <input type="text" id="username" name="username" required
                            class="w-full px-4 py-2 rounded bg-gray-700 border border-gray-600 text-white focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            placeholder="Choose a username">
                    </div>
                    
                    <div class="mb-6">
                        <label for="email" class="block text-gray-300 mb-2">Email Address</label>
                        <input type="email" id="email" name="email" required
                            class="w-full px-4 py-2 rounded bg-gray-700 border border-gray-600 text-white focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            placeholder="Enter your email">
                    </div>
                    
                    <div class="mb-6">
                        <label for="password" class="block text-gray-300 mb-2">Password</label>
                        <input type="password" id="password" name="password" required
                            class="w-full px-4 py-2 rounded bg-gray-700 border border-gray-600 text-white focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            placeholder="Choose a password (min. 8 characters)">
                    </div>
                    
                    <div class="mb-6">
                        <label for="confirm_password" class="block text-gray-300 mb-2">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required
                            class="w-full px-4 py-2 rounded bg-gray-700 border border-gray-600 text-white focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            placeholder="Confirm your password">
                    </div>
                    
                    <div class="mb-6">
                        <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white py-2 px-4 rounded transition duration-200">
                            Create Account
                        </button>
                    </div>
                    
                    <div class="text-center text-gray-400">
                        <p>Already have an account? <a href="login.php" class="text-purple-400 hover:text-purple-300">Sign in</a></p>
                    </div>
                </form>
            <?php endif; ?>
        </div>
        
        <div class="mt-8 text-center text-gray-500 text-sm">
            <p>© <?php echo date('Y'); ?> Text Processing Platform. All rights reserved.</p>
        </div>
    </div>
</body>
</html>