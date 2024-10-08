<?php
session_start();
require 'config.php';  // Include database connection

// Generate a unique token for each user session
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Function to validate the CSRF token
function validate_csrf_token($token) {
    return $token === $_SESSION['csrf_token'];
}

// Function to sanitize inputs
function sanitize_input($input) {
    $input = trim($input);
    $input = htmlspecialchars($input);
    $input = filter_var($input, FILTER_SANITIZE_STRING);
    return $input;
}

// Ensure the user is logged in and is a client
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php');  // Redirect to login if not logged in or not a client
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch client name
$stmt = $pdo->prepare("SELECT name FROM users WHERE id = :user_id");
$stmt->execute([':user_id' => $user_id]);
$client_name = $stmt->fetchColumn();

// Set the number of orders per page
$orders_per_page = 5;

// Get the current page number
$page = isset($_GET['page']) ? $_GET['page'] : 1;

// Calculate the offset for the database query
$offset = ($page - 1) * $orders_per_page;

// Fetch active orders for the logged-in client
$stmt = $pdo->prepare("SELECT o.*, d.driver_name FROM orders o LEFT JOIN drivers d ON o.driver_id = d.id WHERE o.client_id = :client_id AND o.status != 'delivered' ORDER BY o.id DESC LIMIT :limit OFFSET :offset");
$stmt->execute([':client_id' => $user_id, ':limit' => $orders_per_page, ':offset' => $offset]);
$active_orders = $stmt->fetchAll();

// Fetch the total number of active orders
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE client_id = :client_id AND status != 'delivered'");
$stmt->execute([':client_id' => $user_id]);
$total_active_orders = $stmt->fetchColumn();

// Calculate the number of pages for active orders
$num_pages_active_orders = ceil($total_active_orders / $orders_per_page);

// Fetch order history (delivered orders)
$stmt = $pdo->prepare("SELECT o.*, d.driver_name FROM orders o LEFT JOIN drivers d ON o.driver_id = d.id WHERE o.client_id = :client_id AND o.status = 'delivered' ORDER BY o.id DESC LIMIT :limit OFFSET :offset");
$stmt->execute([':client_id' => $user_id, ':limit' => $orders_per_page, ':offset' => $offset]);
$order_history = $stmt->fetchAll();

// Fetch the total number of delivered orders
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE client_id = :client_id AND status = 'delivered'");
$stmt->execute([':client_id' => $user_id]);
$total_delivered_orders = $stmt->fetchColumn();

// Calculate the number of pages for delivered orders
$num_pages_delivered_orders = ceil($total_delivered_orders / $orders_per_page);
?>

<!-- HTML for Client Dashboard -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Client Portal</title>
</head>
<body>

<div class="container mt-5">
    <div class="dashboard-container">
        <h2 class="dashboard-header">Welcome to Your Dashboard, <?= htmlspecialchars($client_name); ?>!</h2>
        <a href="logout.php" class="btn btn-primary mb-3">Logout</a>

        <h4 class="text-center mb-4">Active Orders</h4>

        <!-- Active Orders Table -->
        <?php if (!empty($active_orders)): ?>
            <table class="table table-custom table-bordered">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Status</th>
                        <th>Driver Name</th>
                        <th>Delivery Address</th>
                        <th>Contact Info</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($active_orders as $order): ?>
                        <tr>
                            <td><?= $order['id']; ?></td>
                            <td><?= ucfirst($order['status']); ?></td>
                            <td><?= htmlspecialchars($order['driver_name'] ?: 'Not Assigned'); ?></td>
                            <td><?= htmlspecialchars($order['address']); ?></td>
                            <td><?= htmlspecialchars($order['contact_info']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination for Active Orders -->
            <?php if ($num_pages_active_orders > 1): ?>
                <nav aria-label="Active Orders Pagination">
                    <ul class="pagination pagination-horizontal">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo $page - 1; ?>">Previous</a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $num_pages_active_orders; $i++): ?>
                            <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $num_pages_active_orders): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo $page + 1; ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>

        <?php else: ?>
            <p class="text-center">You have no active orders.</p>
        <?php endif; ?>

        <h4 class="text-center mb-4 mt-5">Order History</h4>

        <!-- Order History Table -->
        <?php if (!empty($order_history)): ?>
            <table class="table table-custom table-bordered">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Status</th>
                        <th>Driver Name</th>
                        <th>Updated At</th>
                        <th>Delivery Address</th>
                        <th>Contact Info</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_history as $order): ?>
                        <tr>
                            <td><?= $order['id']; ?></td>
                            <td><?= ucfirst($order['status']); ?></td>
                            <td><?= htmlspecialchars($order['driver_name']) ?: 'Not Assigned'; ?></td>
                            <td><?= $order['updated_at']; ?></td>
                            <td><?= htmlspecialchars($order['address']); ?></td>
                            <td><?= htmlspecialchars($order['contact_info']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination for Order History -->
            <?php if ($num_pages_delivered_orders > 1): ?>
                <nav aria-label="Order History Pagination">
                    <ul class="pagination pagination-horizontal">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo $page - 1; ?>">Previous</a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $num_pages_delivered_orders; $i++): ?>
                            <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $num_pages_delivered_orders): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo $page + 1; ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>

        <?php else: ?>
            <p class="text-center">You have no past orders.</p>
        <?php endif; ?>

        <div class="footer">
            &copy; 2024 Rindra Delivery. All rights reserved.
        </div>
    </div>
</div>
</body>
</html>