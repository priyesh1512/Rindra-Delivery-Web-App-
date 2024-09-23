<?php
session_start();
require 'config.php';  // Include database connection

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'driver') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch driver information
$stmt = $pdo->prepare("SELECT * FROM drivers WHERE user_id = :user_id");
$stmt->execute([':user_id' => $user_id]);
$driver_info = $stmt->fetch();

if (!$driver_info) {
    die('Driver information not found.');
}

// Fetch active orders assigned to the driver
$stmt = $pdo->prepare("SELECT * FROM orders WHERE driver_id = :driver_id AND status != 'delivered'");
$stmt->execute([':driver_id' => $driver_info['id']]);
$active_orders = $stmt->fetchAll();

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];

    // Update the order status
    $stmt = $pdo->prepare("UPDATE orders SET status = :status WHERE id = :order_id");
    $stmt->execute([':status' => $new_status, ':order_id' => $order_id]);

    header("Location: driver_dashboard.php");
    exit;
}

// Fetch order history for the driver
$stmt = $pdo->prepare("SELECT * FROM orders WHERE driver_id = :driver_id AND status = 'delivered'");
$stmt->execute([':driver_id' => $driver_info['id']]);
$order_history = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <title>Driver Dashboard</title>
    <style>
        body {
            background-color: #F4F4F9;
            font-family: 'Arial', sans-serif;
        }
        .dashboard-container {
            padding: 40px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.1);
        }
        .dashboard-header {
            font-size: 28px;
            color: #BE6DB7;
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="container mt-5">
    <div class="dashboard-container">
        <h2 class="dashboard-header">Welcome to Your Driver Dashboard</h2>
        
        <h4 class="text-center mb-4">Driver Information</h4>
        <p><strong>Name:</strong> <?= htmlspecialchars($driver_info['driver_name'] ?? 'N/A'); ?></p>
        <p><strong>License Number:</strong> <?= htmlspecialchars($driver_info['license_number'] ?? 'N/A'); ?></p>
        <p><strong>Vehicle Info:</strong> <?= htmlspecialchars($driver_info['vehicle_info'] ?? 'N/A'); ?></p>
        <p><strong>Availability:</strong> <?= htmlspecialchars($driver_info['availability'] ?? 'N/A'); ?></p>

        <h4 class="text-center mb-4 mt-5">Active Orders</h4>

        <?php if (!empty($active_orders)): ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Client ID</th>
                        <th>Order Status</th>
                        <th>Delivery Address</th>
                        <th>Client Contact</th>
                        <th>Update Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($active_orders as $order): ?>
                        <tr>
                            <td><?= $order['id']; ?></td>
                            <td><?= $order['client_id']; ?></td>
                            <td><?= ucfirst($order['status']); ?></td>
                            <td><?= $order['address']; ?></td>
                            <td><?= $order['contact_info']; ?></td>
                            <td>
                                <form method="POST" action="">
                                    <input type="hidden" name="order_id" value="<?= $order['id']; ?>">
                                    <select name="status" class="form-control" required>
                                        <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="picked_up" <?= $order['status'] === 'picked_up' ? 'selected' : ''; ?>>Picked Up</option>
                                        <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                    </select>
                                    <button type="submit" name="update_status" class="btn btn-primary mt-2">Update</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-center">No active orders assigned to you.</p>
        <?php endif; ?>

        <h4 class="text-center mb-4 mt-5">Order History</h4>

        <?php if (!empty($order_history)): ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Client ID</th>
                        <th>Order Status</th>
                        <th>Delivery Address</th>
                        <th>Client Contact</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_history as $order): ?>
                        <tr>
                            <td><?= $order['id']; ?></td>
                            <td><?= $order['client_id']; ?></td>
                            <td><?= ucfirst($order['status']); ?></td>
                            <td><?= $order['address']; ?></td>
                            <td><?= $order['contact_info']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-center">No order history available.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
