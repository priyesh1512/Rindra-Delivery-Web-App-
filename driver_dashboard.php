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

// Set the number of orders per page
$orders_per_page = 5;

// Get the current page number
$page = isset($_GET['page']) ? $_GET['page'] : 1;

// Calculate the offset for the database query
$offset = ($page - 1) * $orders_per_page;

// Fetch active orders assigned to the driver
$stmt = $pdo->prepare("SELECT * FROM orders WHERE driver_id = :driver_id AND status != 'delivered' ORDER BY id DESC LIMIT :limit OFFSET :offset");
$stmt->execute([':driver_id' => $driver_info['id'], ':limit' => $orders_per_page, ':offset' => $offset]);
$active_orders = $stmt->fetchAll();

// Fetch the total number of active orders
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE driver_id = :driver_id AND status != 'delivered'");
$stmt->execute([':driver_id' => $driver_info['id']]);
$total_active_orders = $stmt->fetchColumn();

// Calculate the number of pages for active orders
$num_pages_active_orders = ceil($total_active_orders / $orders_per_page);

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $csrf_token = $_POST['csrf_token'];
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];

    // Validate CSRF token
    if (!validate_csrf_token($csrf_token)) {
        die('Invalid CSRF token');
    }

    // Sanitize inputs
    $order_id = sanitize_input($order_id);
    $new_status = sanitize_input($new_status);

    // Update the order status
    $stmt = $pdo->prepare("UPDATE orders SET status = :status WHERE id = :order_id");
    $stmt->execute([':status' => $new_status, ':order_id' => $order_id]);

    header("Location: driver_dashboard.php");
    exit;
}

// Fetch order history for the driver
$stmt = $pdo->prepare("SELECT * FROM orders WHERE driver_id = :driver_id AND status = 'delivered' ORDER BY id DESC LIMIT :limit OFFSET :offset");
$stmt->execute([':driver_id' => $driver_info['id'], ':limit' => $orders_per_page, ':offset' => $offset]);
$order_history = $stmt->fetchAll();

// Fetch the total number of delivered orders
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE driver_id = :driver_id AND status = 'delivered'");
$stmt->execute([':driver_id' => $driver_info['id']]);
$total_delivered_orders = $stmt->fetchColumn();

// Calculate the number of pages for delivered orders
$num_pages_delivered_orders = ceil($total_delivered_orders / $orders_per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Driver Dashboard</title>
</head>
<body>

<div class="container mt-5">
    <div class="dashboard-container">
        <h2 class="dashboard-header">Welcome to Your Driver Dashboard</h2>
        <a href="logout.php" class="btn btn-primary mb-3">Logout</a>
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
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
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
    <p class="text-center">No order history available.</p>
<?php endif; ?>
    </div>
</div>

</body>
</html>