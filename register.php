<?php
require 'config.php';  // Include database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);  // Hash the password
    $driver_name = $_POST['driver_name']; // New driver name field

    // Check if the role is selected
    $roles = isset($_POST['roles']) ? $_POST['roles'] : ['client'];  // Default to client if no role is selected

    // Convert roles array into a comma-separated string
    $roleString = implode(',', $roles);

    // Check if the email already exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    if ($stmt->rowCount() > 0) {
        $error = "This email is already registered.";
    } else {
        // Insert new user into the database with selected role(s)
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)");
        $stmt->execute([
            ':name' => $name, 
            ':email' => $email, 
            ':password' => $password, 
            ':role' => $roleString  // Save the roles as a comma-separated string
        ]);

        // Get the user ID of the newly created user
        $user_id = $pdo->lastInsertId();

        // If the user is a driver, insert the driver information into the drivers table
        if (in_array('driver', $roles)) {
            $license_number = $_POST['license_number']; // Assuming you have this input
            $vehicle_info = $_POST['vehicle_info']; // Assuming you have this input
            $availability = $_POST['availability'] ?? 'available'; // Default to available

            $stmt = $pdo->prepare("INSERT INTO drivers (user_id, license_number, vehicle_info, availability, driver_name) VALUES (:user_id, :license_number, :vehicle_info, :availability, :driver_name)");
            $stmt->execute([
                ':user_id' => $user_id,
                ':license_number' => $license_number,
                ':vehicle_info' => $vehicle_info,
                ':availability' => $availability,
                ':driver_name' => $driver_name,
            ]);
        }

        // Redirect to login page after successful registration
        header('Location: login.php');
        exit;
    }
}
?>

<!-- HTML for Sign Up form -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <title>Sign Up - Care Delivery</title>

    <!-- Custom styles -->
    <style>
        body {
            background-color: #F4F4F9;
            font-family: 'Arial', sans-serif;
        }
        .signup-container {
            padding: 40px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.1);
        }
        .signup-header {
            font-size: 28px;
            color: #BE6DB7;
            text-align: center;
            margin-bottom: 20px;
        }
        .btn-custom {
            background-color: #BE6DB7;
            color: white;
            border: none;
        }
        .btn-custom:hover {
            background-color: #C04A82;
        }
        .form-control {
            border: 2px solid #BE6DB7;
            border-radius: 5px;
        }
        .form-control:focus {
            border-color: #DC8449;
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
        #driver-details {
            display: none;
        }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="signup-container">
        <h2 class="signup-header">Sign Up for Rindra Delivery</h2>

        <?php if (!empty($error)): ?>
            <div class="error-message"><?= $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="name">Full Name:</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" name="password" class="form-control" required id="password-input">
                <input type="checkbox" id="show-password" onclick="this.form.password.type = this.checked ? 'text' : 'password'">
                <label for="show-password">Show Password</label>
            </div>
            <div class="form-group">
                <label for="roles">Select Role:</label><br>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="roles" value="client" id="roleClient" checked>
                <label class="form-check-label" for="roleClient">Client</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="roles" value="driver" id="roleDriver" onclick="document.getElementById('driver-details').style.display = this.checked ? 'block' : 'none';">
                 <label class="form-check-label" for="roleDriver">Driver</label>
            </div>
            </div>
            <div id="driver-details">
                <div class="form-group">
                    <label for="driver_name">Driver Name:</label>
                    <input type="text" name="driver_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="license_number">License Number:</label>
                    <input type="text" name="license_number" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="vehicle_info">Vehicle Info:</label>
                    <input type="text" name="vehicle_info" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="availability">Availability:</label>
                    <select name="availability" class="form-control">
                        <option value="available">Available</option>
                        <option value="unavailable">Unavailable</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-custom btn-block">Sign Up</button>
        </form>

        <div class="footer">
            &copy; 2024 Rindra Delivery. All rights reserved.
        </div>
    </div>
</div>

</body>
</html>