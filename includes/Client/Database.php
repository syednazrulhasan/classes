<?php
namespace Client;

use PDO;
use PDOException;

class Database {
    private $host = "localhost"; // Change this
    private $dbname = "leads"; // Change this
    private $username = "root"; // Change this
    private $password = "root"; // Change this
    private $conn;

    public function __construct() {
        try {
            $this->conn = new PDO("mysql:host={$this->host};dbname={$this->dbname}", $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Database Connection Failed: " . $e->getMessage());
        }
    }

    public function insert($table, $data) {
        $columns = implode(", ", array_keys($data));
        $values = ":" . implode(", :", array_keys($data));
        $sql = "INSERT INTO $table ($columns) VALUES ($values)";
        $stmt = $this->conn->prepare($sql);

        if ($stmt->execute($data)) {
            return $this->conn->lastInsertId(); // Return the last inserted ID
        } else {
            return false; // Return false on failure
        }
    }


    public function update($table, $data, $where, $whereParams) {
        // Wrap column names in backticks (`) to handle spaces
        $setClause = implode(", ", array_map(fn($key) => "`$key` = :$key", array_keys($data)));

        $sql = "UPDATE `$table` SET $setClause WHERE $where";
        $stmt = $this->conn->prepare($sql);

        // Merge update data with WHERE parameters
        $params = array_merge($data, $whereParams);

        if ($stmt->execute($params)) {
            return $stmt->rowCount(); // Return number of updated rows
        } else {
            return false;
        }
    }



    public function delete($table, $where) {
        $sql = "DELETE FROM $table WHERE $where";
        return $this->conn->exec($sql);
    }

    public function select($table, $where = "1") {
        $sql = "SELECT * FROM $table WHERE $where";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>