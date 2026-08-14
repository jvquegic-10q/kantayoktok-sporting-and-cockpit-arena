<?php
require 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username && $password) {
        $result = $conn->query("SELECT id, role, full_name FROM users WHERE username = '$username' AND password = '$password'");
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            
            // Redirect based on role
            if ($user['role'] === 'guard') {
                header('Location: guard_gate.php');
            } elseif ($user['role'] === 'cashier') {
                header('Location: cashier_finance.php');
            } else {
                header('Location: admin_dashboard.php');
            }
            exit;
        } else {
            $error = '❌ Invalid username or password';
        }
    }
}

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'guard') {
        header('Location: guard_gate.php');
    } elseif ($_SESSION['role'] === 'cashier') {
        header('Location: cashier_finance.php');
    } else {
        header('Location: admin_dashboard.php');
    }
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Kantayoktok Sporting and Cockpit Arena | Daily Monitoring System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="login-container">
        <h1>Kantayoktok Sporting and Cockpit Arena</h1>
        <p class="subtitle">Daily Monitoring System</p>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form id="loginForm" method="POST" class="login-form">
            <input type="text" name="username" placeholder="Username" required autofocus autocomplete="username">
            <input type="password" name="password" placeholder="Password" required autocomplete="current-password">
            <button id="loginSubmit" type="submit">Login</button>
        </form>
        
        <div class="demo-accounts">
            <h4>📋 Demo Accounts:</h4>
            <div class="demo-account">
                <span class="role-badge role-guard">GUARD</span>
                guard1 / guard123
            </div>
            <div class="demo-account">
                <span class="role-badge role-cashier">CASHIER</span>
                cashier1 / cashier123
            </div>
            <div class="demo-account">
                <span class="role-badge role-admin">ADMIN</span>
                admin / admin123
            </div>
        </div>
    </div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('loginForm');
        const submitButton = document.getElementById('loginSubmit');

        if (!form || !submitButton) return;

        form.addEventListener('submit', function () {
            if (form.dataset.submitting === 'true') return false;
            form.dataset.submitting = 'true';
            submitButton.disabled = true;
            submitButton.textContent = 'Logging in...';
            return true;
        });
    });
</script>
</body>
</html>