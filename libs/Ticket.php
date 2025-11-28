<?php
// classes/Ticket.php - Ticket Class

class Ticket {
    private $conn;
    private $table_name = "tickets";

    public $id;
    public $user_id;
    public $judul;
    public $deskripsi;
    public $kategori;
    public $prioritas;
    public $status;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($user_id, $judul, $deskripsi, $kategori, $prioritas) {
        $query = "INSERT INTO " . $this->table_name . " SET user_id=:user_id, judul=:judul, deskripsi=:deskripsi, kategori=:kategori, prioritas=:prioritas, status='Open'";
        $stmt = $this->conn->prepare($query);

        $judul_clean = htmlspecialchars(strip_tags($judul));
        $deskripsi_clean = htmlspecialchars(strip_tags($deskripsi));
        $kategori_clean = htmlspecialchars(strip_tags($kategori));
        $prioritas_clean = htmlspecialchars(strip_tags($prioritas));

        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":judul", $judul_clean);
        $stmt->bindParam(":deskripsi", $deskripsi_clean);
        $stmt->bindParam(":kategori", $kategori_clean);
        $stmt->bindParam(":prioritas", $prioritas_clean);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function getAll($user_id = null, $role = 'user') {
        if($role == 'admin') {
            $query = "SELECT t.*, u.nama as user_nama FROM " . $this->table_name . " t LEFT JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC";
            $stmt = $this->conn->prepare($query);
        } else {
            $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = ? ORDER BY created_at DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $user_id);
        }

        $stmt->execute();
        return $stmt;
    }

    public function getById($id) {
        $query = "SELECT t.*, u.nama as user_nama FROM " . $this->table_name . " t LEFT JOIN users u ON t.user_id = u.id WHERE t.id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();

        $num = $stmt->rowCount();
        if($num > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    public function updateStatus($id, $status) {
        $query = "UPDATE " . $this->table_name . " SET status = :status, updated_at = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);

        return $stmt->execute();
    }
}
?>