<?php
session_start();

class Database {
    private static $instance = null;
    private $conn;
    private function __construct() {
        $host = 'localhost';
        $dbname = 'mycampus';
        $user = 'root';
        $pass = 'root1234';
        try {
            $this->conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
    public static function getInstance() {
        if (self::$instance === null) self::$instance = new Database();
        return self::$instance;
    }
    public function getConnection() {
        return $this->conn;
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirect($url) {
    header("Location: $url");
    exit;
}
?>