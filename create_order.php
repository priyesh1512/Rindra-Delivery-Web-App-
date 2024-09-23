<?php
session_start();
require 'config.php';  // Include database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Fetch and sanitize input values
    $client_id = $_POST['client_id'] ?? null; // Use null coalescing
    $address = $_POST['address'] ?? null;
    $contact_info = $_POST['contact_info'] ?? null;  // Ensure the field matches the form

    // Validate inputs
    if ($client_id && $address && $contact_info) {
        $status = 'pending';  // Default status

        // Insert new order into the database
        $stmt = $pdo->prepare("INSERT INTO orders (client_id, address, contact_info, status) VALUES (:client_id, :address, :contact_info, :status)");
        $stmt->execute([
            ':client_id' => $client_id,
            ':address' => $address,
            ':contact_info' => $contact_info, // Use the correct field name
            ':status' => $status
        ]);

        // Redirect back to admin dashboard after successful creation
        header('Location: admin_dashboard.php');
        exit;
    } else {
        $error = "All fields are required.";
    }
}

// Fetch clients from the users table for the dropdown
$clients = $pdo->query("SELECT id, name FROM users WHERE role = 'client'")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Order</title>
    <link rel="stylesheet" href="style1.css">
</head>
<body>
<div class="container">
    <h2>Create New Order</h2>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="client_id">Client:</label>
            <select name="client_id" class="form-control" required>
                <option value="">Select Client</option> <!-- Placeholder option -->
                <?php foreach ($clients as $client): ?>
                    <option value="<?= htmlspecialchars($client['id']); ?>"><?= htmlspecialchars($client['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="address">Address:</label>
            <input type="text" name="address" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="contact_info">Contact:</label>
            <input type="text" name="contact_info" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Create Order</button>
    </form>
</div>
</body>
</html>
