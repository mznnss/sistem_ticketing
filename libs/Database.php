<?php
class Database {
    public $conn;

    public function getConnection() {
        $this->conn = null;

        // Coba ambil dari $_ENV dulu, baru getenv() (Lebih kuat di Vercel)
        $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST');
        $port = $_ENV['DB_PORT'] ?? getenv('DB_PORT');
        $user = $_ENV['DB_USER'] ?? getenv('DB_USER');
        $pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS');
        $name = $_ENV['DB_NAME'] ?? getenv('DB_NAME');
        $ssl  = $_ENV['DB_SSL']  ?? getenv('DB_SSL');

        // DEBUG: Cek apakah variable terbaca?
        // Kalau HOST kosong, matikan program dan kasih pesan jelas.
        if (empty($host)) {
            die("ERROR FATAL: DB_HOST tidak terbaca dari Vercel! Pastikan Environment Variables sudah dicentang untuk 'Production'.");
        }

        try {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_PERSISTENT => false 
            ];

            if ($ssl === 'true' || $ssl === true) {
                $options[PDO::MYSQL_ATTR_SSL_CA] = '/etc/pki/tls/certs/ca-bundle.crt';
            }

            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
            
            $this->conn = new PDO($dsn, $user, $pass, $options);
            
        } catch(PDOException $exception) {
            die("Gagal Konek Database: " . $exception->getMessage());
        }

        return $this->conn;
    }
}
?>
