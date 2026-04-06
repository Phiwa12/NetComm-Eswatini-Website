<?php
class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $firstname;
    public $lastname;
    public $email;
    public $password;
    public $phone;
    public $user_type;
    public $is_active;
    public $email_verified;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Check if email already exists
    public function emailExists() {
        $query = "SELECT id, firstname, lastname, email FROM " . $this->table_name . " WHERE email = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->email);
        $stmt->execute();
        
        $num = $stmt->rowCount();
        
        if($num > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->firstname = $row['firstname'];
            $this->lastname = $row['lastname'];
            return true;
        }
        return false;
    }

    // Create new user
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET firstname=:firstname, lastname=:lastname, email=:email, 
                      password=:password, phone=:phone, user_type=:user_type, 
                      is_active=:is_active, email_verified=:email_verified";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->firstname = htmlspecialchars(strip_tags($this->firstname));
        $this->lastname = htmlspecialchars(strip_tags($this->lastname));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->phone = htmlspecialchars(strip_tags($this->phone));

        // Hash password
        $password_hash = password_hash($this->password, PASSWORD_DEFAULT);

        // Bind values
        $stmt->bindParam(":firstname", $this->firstname);
        $stmt->bindParam(":lastname", $this->lastname);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $password_hash);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":user_type", $this->user_type);
        $stmt->bindParam(":is_active", $this->is_active);
        $stmt->bindParam(":email_verified", $this->email_verified);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Log user activity
    public function logActivity($action, $description, $ip_address, $device_info) {
        $query = "INSERT INTO logs 
                  SET user_id=:user_id, log_action=:log_action, entity_type=:entity_type,
                      entity_id=:entity_id, log_description=:log_description, 
                      ip_address=:ip_address, device_info=:device_info, log_status=:log_status";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":user_id", $this->id);
        $stmt->bindParam(":log_action", $action);
        $stmt->bindValue(":entity_type", "user");
        $stmt->bindParam(":entity_id", $this->id);
        $stmt->bindParam(":log_description", $description);
        $stmt->bindParam(":ip_address", $ip_address);
        $stmt->bindParam(":device_info", $device_info);
        $stmt->bindValue(":log_status", "success");

        return $stmt->execute();
    }
}
?>