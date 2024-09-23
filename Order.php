<?php
class Order {
    private $orderId;
    private $client;
    private $address;
    private $status;
    private $driver;

    public function __construct($orderId, $client, $address, $status = 'pending') {
        $this->orderId = $orderId;
        $this->client = $client;
        $this->address = $address;
        $this->status = $status;
    }

    // Getters and setters
    public function getOrderId() {
        return $this->orderId;
    }

    public function getClient() {
        return $this->client;
    }

    public function getAddress() {
        return $this->address;
    }

    public function getStatus() {
        return $this->status;
    }

    public function setStatus($status) {
        $this->status = $status;
    }

    public function assignDriver($driver) {
        $this->driver = $driver;
    }

    public function getDriver() {
        return $this->driver;
    }
}
?>
