<?php
require_once 'User.php';

class Client extends User {
    private $orderHistory = [];

    public function __construct($userId, $name, $email) {
        parent::__construct($userId, $name, $email, 'client');
    }

    public function addOrder($order) {
        $this->orderHistory[] = $order;
    }

    public function getOrderHistory() {
        return $this->orderHistory;
    }
}
?>
