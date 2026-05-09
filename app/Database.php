<?php
class Database {
    public static function getConnection() {
        $host = 'db'; $db = 'customer_db'; $user = 'root'; $pass = 'root';
        $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            self::initialize($pdo);
            return $pdo;
        } catch (Exception $e) { die("DB Error: " . $e->getMessage()); }
    }

    private static function initialize($pdo) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS valid_customers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(100), last_name VARCHAR(100),
            email VARCHAR(150), phone VARCHAR(50), ip VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_email (email)
        ) ENGINE=InnoDB;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS invalid_customers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(100), last_name VARCHAR(100),
            email VARCHAR(150), phone VARCHAR(50), 
            error_message TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;");
    }
}