<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    try {
        $conn = getDBConnection();
        
        // Debug: Print the email being checked
        error_log("Checking login for email: " . $email);
        
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            // Debug: Check if password matches
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['account_type'] = $user['account_type'];
                header("Location: dashboard.php");
                exit();
            } else {
                error_log("Password verification failed for user: " . $email);
            }
        } else {
            error_log("No user found with email: " . $email);
        }
        
        $_SESSION['error_message'] = "Invalid email or password";
        
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        $_SESSION['error_message'] = "Login failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BudgetPal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .login-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            background: white;
        }
        .ai-assistant {
            margin-top: 20px;
            padding: 15px;
            border-radius: 10px;
            background: #f8f9fa;
        }
        .ai-assistant-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        .ai-icon {
            width: 24px;
            height: 24px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="login-container">
        <h2 class="text-center mb-4">Welcome Back!</h2>
        
        <div class="ai-assistant">
            <div class="ai-assistant-header">
                <img src="assets/ai-bot.svg" class="ai-icon" alt="AI Assistant">
                <h5 class="mb-0">AI Budget Assistant</h5>
            </div>
            <p class="text-muted mb-0">Welcome! I'm here to help you manage your finances better.</p>
            <div class="d-flex gap-2 mt-2">
                <button class="btn btn-sm btn-outline-primary">Need Help?</button>
                <button class="btn btn-sm btn-outline-primary">Show Features</button>
                <button class="btn btn-sm btn-outline-primary">Budget Tips</button>
            </div>
        </div>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger mt-3">
                <?php 
                    echo $_SESSION['error_message'];
                    unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
        
        <div class="text-center mt-3">
            <a href="forgot-password.php">Forgot Password?</a>
        </div>
        <div class="text-center mt-3">
            Don't have an account? <a href="signup.php">Sign up here</a>
        </div>
    </div>
</body>
</html>