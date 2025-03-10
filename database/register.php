<?php
/**
 * Registration Page
 * 
 * Allows new users to register with the platform.
 */

// Start session
session_start();

// Include database connection
require_once 'db_connect.php';

// Initialize variables
$error = '';
$success = '';
$username = '';
$email = '';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect to dashboard
    header('Location: index.php');
    exit();
}

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validate form data
    if (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        try {
            // Check if username already exists
            $existingUser = db_select(
                "SELECT * FROM users WHERE username = ?", 
                [$username]
            );
            
            if (count($existingUser) > 0) {
                $error = 'Username already exists. Please choose another one.';
            } else {
                // Check if email already exists
                $existingEmail = db_select(
                    "SELECT * FROM users WHERE email = ?", 
                    [$email]
                );
                
                if (count($existingEmail) > 0) {
                    $error = 'Email already registered. Please use another email or login.';
                } else {
                    // Create new user
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    
                    $userData = [
                        'username' => $username,
                        'email' => $email,
                        'password' => $hashedPassword,
                        'subscription_tier' => 'Basic',
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    $userId = db_insert('users', $userData);
                    
                    if ($userId) {
                        $success = 'Registration successful! You can now log in.';
                        
                        // Clear form data
                        $username = '';
                        $email = '';
                    } else {
                        $error = 'Failed to create user. Please try again.';
                    }
                }
            }
        } catch (Exception $e) {
            $error = 'An error occurred. Please try again later.';
            error_log("Registration error: " . $e->getMessage());
        }
    }
}

// Set page title
$pageTitle = 'Register';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Text Processing Platform</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="dark-theme">
    <div class="container">
        <header>
            <h1>Text Processing Platform</h1>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="register.php" class="active">Register</a></li>
                    <li><a href="login.php">Log In</a></li>
                </ul>
            </nav>
        </header>
        
        <main>
            <section class="auth-form">
                <h2>Create an Account</h2>
                
                <?php if (!empty($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="success-message">
                        <?php echo $success; ?>
                        <p><a href="login.php" class="btn btn-primary">Log In Now</a></p>
                    </div>
                <?php else: ?>
                    <form method="post" action="register.php">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" required>
                            <small>Must be at least 8 characters long.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Register</button>
                        </div>
                        
                        <p>Already have an account? <a href="login.php">Log in</a></p>
                    </form>
                <?php endif; ?>
            </section>
        </main>
        
        <footer>
            <p>&copy; <?php echo date('Y'); ?> Text Processing Platform. All rights reserved.</p>
        </footer>
    </div>
    
    <script src="assets/js/scripts.js"></script>
</body>
</html>