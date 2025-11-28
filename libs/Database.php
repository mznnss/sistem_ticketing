<?php
// classes/Database.php - Database Configuration

class Database {
    private $host = "localhost";
    private $db_name = "hospital_ticketing";
    private $username = "root";
    private $password = ""; // Ganti dengan password database Anda jika ada

    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Set error mode to exception
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage()); // Log error
            echo "Koneksi database gagal. Silakan coba lagi nanti."; // Friendly error message for user
            exit(); // Terminate script execution
        }
        return $this->conn;
    }
}
?>