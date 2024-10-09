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
    $client_id = $_POST['client_id'];
    $new_status = $_POST['status'];

    // Fetch current order status
    $stmt = $pdo->prepare("SELECT status FROM orders WHERE client_id = :client_id AND driver_id = :driver_id");
    $stmt->execute([':client_id' => $client_id, ':driver_id' => $driver_info['id']]);
    $current_status = $stmt->fetchColumn();

    // Prevent updating if the order has been picked up
    if ($current_status === 'picked_up' && $new_status !== 'delivered') {
        die('The order is already picked up. You cannot change the driver or status unless it is delivered.');
    }

    // Update the order status based on the client ID
    $stmt = $pdo->prepare("UPDATE orders SET status = :status WHERE client_id = :client_id AND driver_id = :driver_id");
    $stmt->execute([':status' => $new_status, ':client_id' => $client_id, ':driver_id' => $driver_info['id']]);

    header("Location: driver_dashboard.php");
    exit;
}

// Fetch order history for the driver
$stmt = $pdo->prepare("SELECT * FROM orders WHERE driver_id = :driver_id AND status = 'delivered'");
$stmt->execute([':driver_id' => $driver_info['id']]);
$order_history = $stmt->fetchAll();
?>
