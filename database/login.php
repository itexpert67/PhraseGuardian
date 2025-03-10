<?php
/**
 * User Login Page
 * 
 * This file handles user authentication
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

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        // Get user by username
        $user = db_select("SELECT * FROM users WHERE username = ?", [$username])[0] ?? null;
        
        // Verify password
        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            // Redirect to subscription page
            header('Location: my_subscription.php');
            exit();
        } else {
            $error = 'Invalid username or password. Please try again.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Text Processing Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-900 text-white min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-auto p-6">
        <div class="bg-gray-800 rounded-lg p-8 shadow-lg">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-purple-400">Text Processing Platform</h1>
                <p class="text-gray-400 mt-2">Sign in to your account</p>
            </div>
            
            <?php if ($error): ?>
                <div class="bg-red-900 text-white p-4 rounded mb-6">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <div class="mb-6">
                    <label for="username" class="block text-gray-300 mb-2">Username</label>
                    <input type="text" id="username" name="username" required
                        class="w-full px-4 py-2 rounded bg-gray-700 border border-gray-600 text-white focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        placeholder="Enter your username">
                </div>
                
                <div class="mb-6">
                    <label for="password" class="block text-gray-300 mb-2">Password</label>
                    <input type="password" id="password" name="password" required
                        class="w-full px-4 py-2 rounded bg-gray-700 border border-gray-600 text-white focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        placeholder="Enter your password">
                </div>
                
                <div class="mb-6">
                    <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white py-2 px-4 rounded transition duration-200">
                        Sign In
                    </button>
                </div>
                
                <div class="text-center text-gray-400">
                    <p>Don't have an account? <a href="register.php" class="text-purple-400 hover:text-purple-300">Sign up</a></p>
                </div>
            </form>
        </div>
        
        <div class="mt-8 text-center text-gray-500 text-sm">
            <p>© <?php echo date('Y'); ?> Text Processing Platform. All rights reserved.</p>
        </div>
    </div>
</body>
</html>