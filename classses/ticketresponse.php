<?php
// classes/TicketResponse.php - Response Class

class TicketResponse {
    private $conn;
    private $table_name = "ticket_responses";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($ticket_id, $user_id, $response) {
        $query = "INSERT INTO " . $this->table_name . " SET ticket_id=:ticket_id, user_id=:user_id, response=:response";
        $stmt = $this->conn->prepare($query);

        $response_clean = htmlspecialchars(strip_tags($response));

        $stmt->bindParam(":ticket_id", $ticket_id);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":response", $response_clean);

        return $stmt->execute();
    }

    public function getByTicketId($ticket_id) {
        $query = "SELECT tr.*, u.nama as user_nama FROM " . $this->table_name . " tr LEFT JOIN users u ON tr.user_id = u.id WHERE tr.ticket_id = ? ORDER BY tr.created_at ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $ticket_id);
        $stmt->execute();

        return $stmt;
    }
}
?>
