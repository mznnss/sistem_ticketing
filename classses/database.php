<?php
class Database {
    // Properti database
    public $conn;

    public function getConnection() {
        $this->conn = null;

        // Ambil kredensial dari Vercel Environment Variables
        // Kalau kosong (lagi di localhost), pakai default setting localhost
        $host = getenv('DB_HOST') ?: 'localhost';
        $port = getenv('DB_PORT') ?: '3306'; 
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: '';
        $name = getenv('DB_NAME') ?: 'test'; // Default database name
        $ssl  = getenv('DB_SSL') ?: 'false';

        try {
            // Opsi untuk SSL (TiDB Cloud wajib SSL di Vercel)
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ];

            if ($ssl === 'true') {
                $options[PDO::MYSQL_ATTR_SSL_CA] = '/etc/pki/tls/certs/ca-bundle.crt';
            }

            $dsn = "mysql:host=" . $host . ";port=" . $port . ";dbname=" . $name;
            
            $this->conn = new PDO($dsn, $user, $pass, $options);
            
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
?>
