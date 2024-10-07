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
