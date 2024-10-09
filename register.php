<?php
require 'config.php';  // Include database connection

$error = '';  // Initialize error variable

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);  // Hash the password

    // Get the role
    $role = $_POST['roles'];  // Single value ('client' or 'driver')

    // Check if the email already exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    if ($stmt->rowCount() > 0) {
        $error = "This email is already registered.";
    } else {
        // Insert new user into the database
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)");
        $stmt->execute([
            ':name' => $name, 
            ':email' => $email, 
            ':password' => $password, 
            ':role' => $role  // Save the role ('client' or 'driver')
        ]);

        // Get the user ID of the newly created user
        $user_id = $pdo->lastInsertId();

        // If the user is a driver, insert the driver information into the drivers table
        if ($role === 'driver') {
            $driver_name = $_POST['driver_name'];
            $license_number = $_POST['license_number'];
            $vehicle_info = $_POST['vehicle_info'];
            $availability = $_POST['availability'] ?? 'available';  // Default to 'available' if not provided

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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <title>Sign Up</title>

    <!-- Custom styles -->
    <style>
       /* Global Styles */

body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f0f4f8; /* Light blue background */
}

.container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 20px;
    background-color: #fff;
    border: 1px solid #ccc;
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.2); /* Darker shadow for contrast */
    border-radius: 10px;
}

h2 {
    margin-top: 0;
    color: #00264d; /* Dark blue for headings */
    font-weight: bold;
}

/* Header Styles */

.header {
    background-color: #00264d; /* Dark blue header */
    color: #fff;
    padding: 20px;
    text-align: center;
    border-bottom: 3px solid #001f3f; /* Slightly darker border */
}

.header h2 {
    margin: 0;
}

/* Table Styles */

table {
    border-collapse: collapse;
    width: 100%;
    margin-bottom: 20px;
    background-color: #f9f9f9; /* Light gray table background */
}

th, td {
    border: 1px solid #ccc;
    padding: 12px;
    text-align: left;
}

th {
    background-color: #00264d; /* Dark blue for table headers */
    color: #fff; /* White text for contrast */
    font-weight: bold;
}

/* Form Styles */

form {
    display: inline-block;
    margin-right: 20px;
}

select {
    width: 150px;
    height: 30px;
    margin-bottom: 10px;
    padding: 5px;
    border: 1px solid #00264d; /* Dark blue border */
    border-radius: 5px;
    background-color: #e6f0ff; /* Light blue form elements */
}

button[type="submit"] {
    background-color: #003366; /* Darker blue for buttons */
    color: #fff;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

button[type="submit"]:hover {
    background-color: #001f3f; /* Even darker on hover */
}

/* Button Styles */

.btn-custom {
    background-color: #003366; /* Darker blue */
    color: #fff;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.btn-custom:hover {
    background-color: #001f3f;
}

/* Miscellaneous Styles */

.mb-3 {
    margin-bottom: 20px;
}

.mt-2 {
    margin-top: 20px;
}

/* Delivery Status Styles */

.status {
    font-size: 14px;
    font-weight: bold;
    color: #00264d; /* Dark blue for general status */
}

.status.pending {
    color: #ff9900; /* Orange for pending */
}

.status.picked_up {
    color: #33cc33; /* Green for picked up */
}

.status.delivered {
    color: #0066cc; /* Blue for delivered */
}

/* Driver Styles */

.driver {
    font-size: 14px;
    color: #333;
}

/* Admin Dashboard Styles */

.admin-dashboard {
    max-width: 900px; /* Limit the width of the dashboard */
    margin: 50px auto; /* Center the dashboard */
    padding: 30px;
    background-color: #f2f6fc; /* Light soft blue background */
    border: 1px solid #ccc;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.1); /* Subtle shadow */
    border-radius: 10px;
}

/* Header for Admin Dashboard */

.admin-dashboard h2 {
    text-align: center;
    color: #00264d; /* Dark blue heading to match the theme */
    margin-bottom: 30px;
    font-weight: bold;
}

/* Miscellaneous Styling for Content inside the Admin Dashboard */

.admin-dashboard .content {
    color: #333;
    font-size: 16px;
    text-align: center;
}

.admin-dashboard .btn {
    display: inline-block;
    background-color: #003366; /* Dark blue button */
    color: #fff;
    padding: 12px 25px;
    border-radius: 5px;
    text-decoration: none;
    cursor: pointer;
}

.admin-dashboard .btn:hover {
 background-color: #001f3f; /* Darker blue on hover */
}

.signup-container {
    padding: 40px;
    background-color: #fff;
    border-radius: 10px;
    box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.1);
}

.signup-header {
    font-size: 28px;
    color: #00264d; /* Dark blue for headings */
    text-align: center;
    margin-bottom: 20px;
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

.pagination-horizontal {
    display: flex;
    justify-content: center;
    align-items: center;
}

.pagination-horizontal .page-item {
    margin: 0 10px;
}

.pagination-horizontal .page-link {
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    background-color: #f0f0f0;
    color: #333;
    text-decoration: none;
}

.pagination-horizontal .page-link:hover {
    background-color: #ccc;
}

.pagination-horizontal .page-item.active .page-link {
    background-color: #337ab7;
    color: #fff;
}
    </style>

    <script>
        function toggleDriverDetails() {
            var driverDetails = document.getElementById('driver-details');
            var isDriver = document.getElementById('roleDriver').checked;
            driverDetails.style.display = isDriver ? 'block' : 'none';
        }
    </script>
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
                    <input class="form-check-input" type="radio" name="roles" value="client" id="roleClient" checked onclick="toggleDriverDetails()">
                    <label class="form-check-label" for="roleClient">Client</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="roles" value="driver" id="roleDriver" onclick="toggleDriverDetails()">
                    <label class="form-check-label" for="roleDriver">Driver</label>
                </div>
            </div>
            <div id="driver-details">
                <div class="form-group">
                    <label for="driver_name">Driver Name:</label>
                    <input type="text" name="driver_name" class="form-control">
                </div>
                <div class="form-group">
                    <label for="license_number">License Number:</label>
                    <input type="text" name="license_number" class="form-control">
                </div>
                <div class="form-group">
                    <label for="vehicle_info">Vehicle Info:</label>
                    <input type="text" name="vehicle_info" class="form-control">
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
