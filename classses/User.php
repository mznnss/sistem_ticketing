<?php
// classes/User.php - User Class

class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $nama;
    public $email;
    public $password;
    public $role;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function register($nama, $email, $password, $role = 'user') {
        $query = "INSERT INTO " . $this->table_name . " SET nama=:nama, email=:email, password=:password, role=:role";
        $stmt = $this->conn->prepare($query);

        $this->nama = htmlspecialchars(strip_tags($nama));
        $this->email = htmlspecialchars(strip_tags($email));
        $this->password = password_hash($password, PASSWORD_DEFAULT);
        $this->role = $role;

        $stmt->bindParam(":nama", $this->nama);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":role", $this->role);

        try {
            if($stmt->execute()) {
                return true;
            }
        } catch(PDOException $e) {
            if ($e->getCode() == '23000') { // SQLSTATE for Integrity Constraint Violation (e.g., duplicate entry)
                error_log("Registration error: Duplicate email - " . $this->email);
                return false; // Indicate failure due to duplicate email
            }
            error_log("Registration error: " . $e->getMessage());
        }
        return false;
    }

    public function login($email, $password) {
        $query = "SELECT id, nama, email, password, role FROM " . $this->table_name . " WHERE email = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $email);
        $stmt->execute();

        $num = $stmt->rowCount();
        if($num > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if(password_verify($password, $row['password'])) {
                $this->id = $row['id'];
                $this->nama = $row['nama'];
                $this->email = $row['email'];
                $this->role = $row['role'];
                return true;
            }
        }
        return false;
    }
}
?>