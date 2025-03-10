<?php
/**
 * Login Page
 * 
 * Handles user authentication. Users can log in with their username/email and password.
 */

// Start session
session_start();

// Include database connection
require_once 'db_connect.php';

// Initialize variables
$error = '';
$username = '';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect to dashboard
    header('Location: index.php');
    exit();
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validate form data
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            // Check if username exists (can be username or email)
            $user = db_select(
                "SELECT * FROM users WHERE username = ? OR email = ?", 
                [$username, $username]
            );
            
            if (count($user) === 1) {
                $user = $user[0];
                
                // Verify password
                if (password_verify($password, $user['password'])) {
                    // Password is correct, set session and redirect
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    
                    // Update last login time
                    db_update(
                        'users',
                        ['last_login' => date('Y-m-d H:i:s')],
                        'id = ?',
                        [$user['id']]
                    );
                    
                    // Redirect to dashboard
                    header('Location: index.php');
                    exit();
                } else {
                    $error = 'Invalid username or password.';
                }
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (Exception $e) {
            $error = 'An error occurred. Please try again later.';
            error_log("Login error: " . $e->getMessage());
        }
    }
}

// Set page title
$pageTitle = 'Log In';
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
                    <li><a href="register.php">Register</a></li>
                    <li><a href="login.php" class="active">Log In</a></li>
                </ul>
            </nav>
        </header>
        
        <main>
            <section class="auth-form">
                <h2>Log In</h2>
                
                <?php if (!empty($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="post" action="login.php">
                    <div class="form-group">
                        <label for="username">Username or Email</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Log In</button>
                    </div>
                    
                    <p>Don't have an account? <a href="register.php">Register</a></p>
                    <p><a href="reset_password.php">Forgot your password?</a></p>
                </form>
            </section>
        </main>
        
        <footer>
            <p>&copy; <?php echo date('Y'); ?> Text Processing Platform. All rights reserved.</p>
        </footer>
    </div>
    
    <script src="assets/js/scripts.js"></script>
</body>
</html>