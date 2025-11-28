<?php
class Database {
    public $conn;

    public function getConnection() {
        $this->conn = null;

        // Ambil variable dari Settingan Vercel
        $host = getenv('DB_HOST') ?: 'localhost';
        $port = getenv('DB_PORT') ?: '3306';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: '';
        $name = getenv('DB_NAME') ?: 'test';
        $ssl  = getenv('DB_SSL') ?: 'false';

        try {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // Opsi ini mencegah error timeout di serverless
                PDO::ATTR_PERSISTENT => false 
            ];

            // Konfigurasi SSL Khusus TiDB/Vercel
            if ($ssl === 'true') {
                $options[PDO::MYSQL_ATTR_SSL_CA] = '/etc/pki/tls/certs/ca-bundle.crt';
            }

            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
            
            $this->conn = new PDO($dsn, $user, $pass, $options);
            
        } catch(PDOException $exception) {
            // Kita echo errornya biar tau kalau gagal connect
            die("Database Error: " . $exception->getMessage());
        }

        return $this->conn;
    }
}
?>
