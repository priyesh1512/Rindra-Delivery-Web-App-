<?php
session_start();
require 'config.php';  // Include database connection

// Fetch orders
$orders = $pdo->query("SELECT * FROM orders")->fetchAll(PDO::FETCH_ASSOC);

// Fetch users (clients)
$users = $pdo->query("SELECT id, name FROM users WHERE role = 'client'")->fetchAll(PDO::FETCH_KEY_PAIR);

// Fetch drivers
$drivers = $pdo->query("SELECT id, driver_name FROM drivers")->fetchAll(PDO::FETCH_KEY_PAIR);

// Handle order status and driver assignment update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    $driver_id = $_POST['driver_id'];

    // Validate status
    if (in_array($new_status, ['pending', 'picked_up', 'delivered'])) {
        // Update the order status and driver ID based on the order ID
        $stmt = $pdo->prepare("UPDATE orders SET status = :status, driver_id = :driver_id WHERE id = :order_id");
        $stmt->execute([':status' => $new_status, ':driver_id' => $driver_id, ':order_id' => $order_id]);
    }

    header("Location: admin_dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Admin Dashboard</title>
</head>
<body>
<div class="container">
    <h2>Admin Dashboard</h2>
    <a href="create_order.php" class="btn btn-primary mb-3">Create New Order</a>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Client Name</th>
                <th>Address</th>
                <th>Contact Info</th>
                <th>Current Status</th>
                <th>Driver</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?= $order['id']; ?></td>
                    <td><?= $users[$order['client_id']] ?? 'Unknown'; ?></td>
                    <td><?= $order['address']; ?></td>
                    <td><?= $order['contact_info']; ?></td>
                    <td><?= ucfirst($order['status']); ?></td>
                    <td><?= isset($drivers[$order['driver_id']]) ? $drivers[$order['driver_id']] : 'Not Assigned'; ?></td>
                    <td>
                        <form method="POST" action="" style="display:inline;">
                            <input type="hidden" name="order_id" value="<?= $order['id']; ?>">
                            <select name="status" class="form-control" required>
                                <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="picked_up" <?= $order['status'] === 'picked_up' ? 'selected' : ''; ?>>Picked Up</option>
                                <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            </select>
                            <select name="driver_id" class="form-control mt-2" required>
                                <option value="">Select Driver</option>
                                <?php foreach ($drivers as $id => $name): ?>
                                    <option value="<?= $id; ?>" <?= $order['driver_id'] == $id ? 'selected' : ''; ?>><?= $name; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-primary mt-2">Update</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>