<?php
session_start();
require 'config.php';  // Include database connection

// Generate a unique CSRF token for each user session
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Function to validate the CSRF token
function validate_csrf_token($token) {
    return $token === $_SESSION['csrf_token'];
}

// Function to sanitize inputs
function sanitize_input($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Variables for pagination
$perPage = 10;
$page_active = $_GET['page_active'] ?? 1;
$start_active = ($page_active - 1) * $perPage;

$page_history = $_GET['page_history'] ?? 1;
$start_history = ($page_history - 1) * $perPage;

// Variables for search and filter
$search = sanitize_input($_GET['search'] ?? '');
$filter_status = sanitize_input($_GET['filter_status'] ?? '');
$client_name = sanitize_input($_GET['client_name'] ?? '');
$order_id = sanitize_input($_GET['order_id'] ?? '');

// Base query for active orders
$query_active = "SELECT o.*, u.name as client_name FROM orders o 
                 INNER JOIN users u ON o.client_id = u.id 
                 WHERE o.status != 'delivered'";

$params_active = [];

// Modify query based on client name search
if (!empty($client_name)) {
    $query_active .= " AND u.name = :client_name";
    $params_active[':client_name'] = $client_name;
}

// Modify query based on order ID search
if (!empty($order_id)) {
    $query_active .= " AND o.id = :order_id";
    $params_active[':order_id'] = $order_id;
}

// Modify query based on status filter
if (!empty($filter_status)) {
    $query_active .= " AND o.status = :status";
    $params_active[':status'] = $filter_status;
}

// Count total active orders for pagination
$query_count = $query_active;
$stmt_count = $pdo->prepare($query_count);
$stmt_count->execute($params_active);
$total_active_orders = $stmt_count->rowCount();

// Apply pagination limit to the active orders query
$query_active .= " LIMIT :start, :perPage";
$stmt_active_paginated = $pdo->prepare($query_active);

// Bind parameters for pagination
$stmt_active_paginated->bindValue(':start', $start_active, PDO::PARAM_INT);
$stmt_active_paginated->bindValue(':perPage', $perPage, PDO::PARAM_INT);

// Bind dynamic parameters
foreach ($params_active as $key => $value) {
    $stmt_active_paginated->bindValue($key, $value);
}

$stmt_active_paginated->execute();
$active_orders = $stmt_active_paginated->fetchAll(PDO::FETCH_ASSOC);

// Fetch order history (delivered orders)
$query_history = "SELECT * FROM orders WHERE status = 'delivered' LIMIT :start, :perPage";
$stmt_history = $pdo->prepare($query_history);
$stmt_history->bindValue(':start', $start_history, PDO::PARAM_INT);
$stmt_history->bindValue(':perPage', $perPage, PDO::PARAM_INT);
$stmt_history->execute();
$order_history = $stmt_history->fetchAll(PDO::FETCH_ASSOC);

// Fetch users (clients)
$users = $pdo->query("SELECT id, name FROM users WHERE role = 'client'")->fetchAll(PDO::FETCH_KEY_PAIR);

// Fetch drivers
$drivers = $pdo->query("SELECT id, driver_name FROM drivers")->fetchAll(PDO::FETCH_KEY_PAIR);

// Calculate total delivered orders
$stmt_delivered_count = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'delivered'");
$total_delivered_orders = $stmt_delivered_count->fetchColumn();

// Handle order status and driver assignment update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'];
    $order_id = sanitize_input($_POST['order_id']);
    $new_status = sanitize_input($_POST['status']);
    $driver_id = sanitize_input($_POST['driver_id']);

    // Validate CSRF token
    if (!validate_csrf_token($csrf_token)) {
        die('Invalid CSRF token');
    }

    // Fetch the current status of the order to prevent changes if picked up
    $stmt = $pdo->prepare("SELECT status FROM orders WHERE id = :order_id");
    $stmt->execute([':order_id' => $order_id]);
    $current_status = $stmt->fetchColumn();

    // Restrict reassigning driver if the status is already 'picked_up'
    if ($current_status === 'picked_up') {
        die('Driver cannot be reassigned once the order is picked up.');
    }

    // Update the order status and driver ID based on the order ID
    $stmt_update = $pdo->prepare("UPDATE orders SET status = :status, driver_id = :driver_id, updated_at = NOW() WHERE id = :order_id");
    $stmt_update->bindParam(':status', $new_status);
    $stmt_update->bindParam(':driver_id', $driver_id);
    $stmt_update->bindParam(':order_id', $order_id);
    $stmt_update->execute();

    header("Location: admin_dashboard.php");
    exit;
}

// Calculate total pages for pagination
$totalPagesActive = ceil($total_active_orders / $perPage);
$totalPagesHistory = ceil($pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'delivered'")->fetchColumn() / $perPage);
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
    <a href="logout.php" class="btn btn-primary mb-3">Logout</a>
    <section class="order-stats mb-4">
        <h5>Total Active Orders: <?= $total_active_orders; ?></h5>
        <h5>Total Delivered Orders: <?= $total_delivered_orders; ?></h5>
    </section>

    <!-- Search and Filter Form -->
    <form method="GET" action="admin_dashboard.php" class="form-inline mb-3">
        <input type="text" name="client_name" class="form-control mr-2" placeholder="Search by Client Name" value="<?= htmlspecialchars($client_name); ?>">
        <input type="text" name="order_id" class="form-control mr-2" placeholder="Search by Order ID" value="<?= htmlspecialchars($order_id); ?>">
        <select name="filter_status" class="form-control mr-2">
            <option value="">All Statuses</option>
            <option value="pending" <?= $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="picked_up" <?= $filter_status === 'picked_up' ? 'selected' : ''; ?>>Picked Up</option>
            <option value="delivered" <?= $filter_status === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
        </select>
        <button type="submit" class="btn btn-primary">Search</button>
    </form>

    <!-- Active Orders Table -->
    <h4>Active Orders</h4>
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
            <?php foreach ($active_orders as $order): ?>
                <tr>
                    <td><?= $order['id']; ?></td>
                    <td><?= $users[$order['client_id']] ?? 'Unknown'; ?></td>
                    <td><?= $order['address']; ?></td>
                    <td><?= $order['contact_info']; ?></td>
                    <td><?= ucfirst($order['status']); ?></td>
                    <td><?= isset($drivers[$order['driver_id']]) ? $drivers[$order['driver_id']] : 'Not Assigned'; ?></td>
                    <td>
                        <form method="POST" action="" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="order_id" value="<?= $order['id']; ?>">
                            <select name="status" class="form-control" required>
                                <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="picked_up" <?= $order['status'] === 'picked_up' ? 'selected' : ''; ?>>Picked Up</option>
                                <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            </select>
                            <select name="driver_id" class="form-control mt-2" <?= $order['status'] === 'picked_up' ? 'disabled' : ''; ?> required>
                                <option value="">Select Driver</option>
                                <?php foreach ($drivers as $id => $name): ?>
                                    <option value="<?= $id; ?>" <?= $order['driver_id'] == $id ? 'selected' : ''; ?>><?= $name; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-success mt-2">Update</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Pagination for Active Orders -->
    <nav>
        <ul class="pagination pagination-horizontal">
            <?php for ($i = 1; $i <= $totalPagesActive; $i++): ?>
                <li class="page-item <?= $i == $page_active ? 'active' : ''; ?>">
                    <a class="page-link" href="admin_dashboard.php?page_active=<?= $i; ?>&client_name=<?= htmlspecialchars($client_name); ?>&order_id=<?= htmlspecialchars($order_id); ?>&filter_status=<?= htmlspecialchars($filter_status); ?>"><?= $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>

    <!-- Delivered Orders Table -->
    <h4>Order History (Delivered Orders)</h4>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Client Name</th>
                <th>Address</th>
                <th>Contact Info</th>
                <th>Status</th>
                <th>Driver</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($order_history as $order): ?>
                <tr>
                    <td><?= $order['id']; ?></td>
                    <td><?= $users[$order['client_id']] ?? 'Unknown'; ?></td>
                    <td><?= $order['address']; ?></td>
                    <td><?= $order['contact_info']; ?></td>
                    <td><?= ucfirst($order['status']); ?></td>
                    <td><?= isset($drivers[$order['driver_id']]) ? $drivers[$order['driver_id']] : 'Not Assigned'; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Pagination for Delivered Orders -->
    <nav>
        <ul class="pagination pagination-horizontal">
            <?php for ($i = 1; $i <= $totalPagesHistory; $i++): ?>
                <li class="page-item <?= $i == $page_history ? 'active' : ''; ?>">
                    <a class="page-link" href="admin_dashboard.php?page_history=<?= $i; ?>"><?= $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>
</body>
</html>
