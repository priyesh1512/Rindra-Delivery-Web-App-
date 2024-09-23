<?php
namespace User;
 
require_once 'config.php'; // Path to Database.php
 
use Database;
use PDO;
use PDOException;
use Exception;
 
class User {
    private $ID;
    private $Name;
    private $Age;
    private $Username;
    private $Email;
    private $Password;
    private $Permission;
 
    // Constructor
    public function __construct($Name, $Age, $Username, $Email, $Password) {
        $this->Name = $Name;
        $this->Age = $Age;
        $this->Username = $Username;
        $this->Email = $Email;
        $this->Password = $Password;
    }
 
    // Static method to create a user
    public static function CreateUser($Name, $Age, $Username, $Email, $Password) {
        $conn = new Database();
        try {
            $query = "INSERT INTO user (Name, Age, Username, Email, Password) VALUES (:Name, :Age, :Username, :Email, :Password)";
            $statement = $conn->getStarted()->prepare($query);
            $statement->bindParam(":Name", $Name);
            $statement->bindParam(":Age", $Age);
            $statement->bindParam(":Username", $Username);
            $statement->bindParam(":Email", $Email);
            $statement->bindParam(":Password", $Password);
            $statement->execute();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                echo "Error: Username or Email already exists. Please choose another.";
            } else {
                echo "An error occurred: " . $e->getMessage();
            }
        }
    }
 
    // Static method to handle user login
    public static function Login($email, $password) {
        $conn = new Database();
        try {
            $query = "SELECT * FROM user WHERE Email = :email";
            $statement = $conn->getStarted()->prepare($query);
            $statement->bindParam(':email', $email);
            $statement->execute();
            $user = $statement->fetch(PDO::FETCH_ASSOC);
   
            if ($user && password_verify($password, $user['Password'])) {
                return $user; // Return user data
            } else {
                return false; // Invalid credentials
            }
        } catch (PDOException $e) {
            throw new Exception("Database error: " . $e->getMessage());
        }
    }
   
}
?>