<?php

class Admin {
    private $pdo;
    private $csrfToken;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->csrfToken = $_SESSION['csrf_token'];
    }

    public function getActiveOrders($clientName = '', $orderId = '', $filterStatus = '', $page = 1) {
        $perPage = 10;
        $start = ($page - 1) * $perPage;

        $query = "SELECT o.* FROM orders o INNER JOIN users u ON o.client_id = u.id WHERE o.status != 'delivered'";
        $params = [];

        if (!empty($clientName)) {
            $query .= " AND u.name = :client_name";
            $params[':client_name'] = $clientName;
        }

        if (!empty($orderId)) {
            $query .= " AND o.id = :order_id";
            $params[':order_id'] = $orderId;
        }

        if (!empty($filterStatus)) {
            $query .= " AND o.status = :status";
            $params[':status'] = $filterStatus;
        }

        $query .= " LIMIT :start, :perPage";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':start', $start);
        $stmt->bindParam(':perPage', $perPage);

        foreach ($params as $key => $value) {
            $stmt->bindParam($key, $value);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOrderHistory($page = 1) {
        $perPage = 10;
        $start = ($page - 1) * $perPage;

        $query = "SELECT * FROM orders WHERE status = 'delivered' LIMIT :start, :perPage";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':start', $start);
        $stmt->bindParam(':perPage', $perPage);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateOrderStatus($orderId, $newStatus, $driverId) {
        if (!validate_csrf_token($this->csrfToken)) {
            die('Invalid CSRF token');
        }

        $orderId = sanitize_input($orderId);
        $newStatus = sanitize_input($newStatus);
        $driverId = sanitize_input($driverId);

        if (in_array($newStatus, ['pending', 'picked_up', 'delivered'])) {
            $stmt = $this->pdo->prepare("UPDATE orders SET status = :status, driver_id = :driver_id, updated_at = NOW() WHERE id = :order_id");
            $stmt->bindParam(':status', $newStatus);
            $stmt->bindParam(':driver_id', $driverId);
            $stmt->bindParam(':order_id', $orderId);
            $stmt->execute();
        }
    }

    public function getTotalActiveOrders($clientName = '', $orderId = '', $filterStatus = '') {
        $query = "SELECT COUNT(*) FROM orders o INNER JOIN users u ON o.client_id = u.id WHERE o.status != 'delivered'";
        $params = [];

        if (!empty($clientName)) {
            $query .= " AND u.name = :client_name";
            $params[':client_name'] = $clientName;
        }

        if (!empty($orderId)) {
            $query .= " AND o.id = :order_id";
            $params[':order_id'] = $orderId;
        }

        if (!empty($filterStatus)) {
            $query .= " AND o.status = :status";
            $params[':status'] = $filterStatus;
        }

        $stmt = $this->pdo->prepare($query);

        foreach ($params as $key => $value) {
            $stmt->bindParam($key, $value);
        }

        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function getTotalDeliveredOrders() {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM orders WHERE status = 'delivered'");
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function getUsers() {
        $stmt = $this->pdo->query("SELECT id, name FROM users WHERE role = 'client'");
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public function getDrivers() {
        $stmt = $this->pdo->query("SELECT id, driver_name FROM drivers");
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}

?>