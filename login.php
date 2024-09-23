<?php
session_start();
require 'config.php';  // Include database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $selected_role = $_POST['role'];  // Capture selected role from the form

    // Fetch user from the database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    // Verify the user exists and the password is correct
    if ($user && password_verify($password, $user['password'])) {
        // Check if the selected role matches the user's role in the database
        if ($user['role'] === $selected_role) {
            // Store user information in the session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];

            // Redirect based on user role
            if ($_SESSION['role'] === 'admin') {
                header('Location: admin_dashboard.php');
            } elseif ($_SESSION['role'] === 'driver') {
                header('Location: driver_dashboard.php');
            } else {
                header('Location: client_dashboard.php');
            }
            exit;
        } else {
            $error = "Incorrect role selected for this user.";
        }
    } else {
        $error = "Invalid email or password.";
    }
}
?>

<!-- HTML for login form -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <title>Login</title>

    <!-- Custom styles -->
    <style>
        body {
            background-image: url('images/image.png');  /* Use the correct path to your image */
            background-size: cover;
            background-position: center;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .login-container {
            padding: 40px;
            background-color: rgba(255, 255, 255, 0.9);  /* Semi-transparent background */
            border-radius: 10px;
            box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
        }
        .login-header {
            font-size: 24px;
            color: #333;
            text-align: center;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .btn-custom {
            background-color: #007BFF;
            color: white;
            border: none;
        }
        .btn-custom:hover {
            background-color: #0056b3;
        }
        .form-control {
            border: 2px solid #007BFF;
            border-radius: 5px;
        }
        .form-control:focus {
            border-color: #0056b3;
            box-shadow: none;
        }
        .error-message {
            color: red;
            text-align: center;
            margin-bottom: 20px;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #888;
        }
        .signup-link {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<div class="login-container">
    <h2 class="login-header">Rindra Delivery</h2>

    <?php if (!empty($error)): ?>
        <div class="error-message"><?= $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" name="password" class="form-control" required>
        </div>

        <!-- Role Selection -->
        <div class="form-group">
            <label>Select your role:</label><br>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="role" value="admin" required>
                <label class="form-check-label" for="admin">Admin</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="role" value="driver" required>
                <label class="form-check-label" for="driver">Driver</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="role" value="client" required>
                <label class="form-check-label" for="client">Client</label>
            </div>
        </div>

        <button type="submit" class="btn btn-custom btn-block">Login</button>
    </form>

    <div class="signup-link">
        <a href="register.php" class="btn btn-outline-primary btn-block">New Customer? Sign Up</a>
    </div>

    <div class="footer">
        &copy; 2024 Rindra Delivery. All rights reserved.
    </div>
</div>

</body>
</html>
