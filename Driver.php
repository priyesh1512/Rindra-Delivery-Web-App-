<?php
require_once 'User.php';

class Driver extends User {
    private $assignedOrders = [];

    public function __construct($userId, $name, $email) {
        parent::__construct($userId, $name, $email, 'driver');
    }

    public function assignOrder($order) {
        $this->assignedOrders[] = $order;
        $order->assignDriver($this);
    }

    public function getAssignedOrders() {
        return $this->assignedOrders;
    }
}
?>
