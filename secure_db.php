<?php
require_once 'security.php';

// Enhanced secure database wrapper
class SecureDatabase {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function prepare($sql) {
        // Log all queries for monitoring
        error_log("SQL Query: " . $sql);
        return $this->pdo->prepare($sql);
    }
    
    public function safeQuery($sql, $params = []) {
        try {
            // Validate parameters
            foreach ($params as $key => $value) {
                if (is_string($value)) {
                    SecurityManager::validateInput($value, 'general', 2000);
                }
            }
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($params);
            
            if (!$result) {
                throw new PDOException("Query execution failed");
            }
            
            return $stmt;
        } catch (Exception $e) {
            SecurityManager::logSecurityEvent("Database Error", $e->getMessage());
            throw $e;
        }
    }
    
    public function safeInsert($table, $data) {
        $allowedTables = ['feedback', 'public_visits', 'upload_logs', 'posts', 'users'];
        
        if (!in_array($table, $allowedTables)) {
            throw new SecurityException("Table not allowed: $table");
        }
        
        // Validate all data
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = SecurityManager::validateInput($value, 'general', 2000);
            }
        }
        
        $columns = array_keys($data);
        $placeholders = ':' . implode(', :', $columns);
        
        $sql = "INSERT INTO $table (" . implode(', ', $columns) . ") VALUES ($placeholders)";
        
        return $this->safeQuery($sql, $data);
    }
    
    public function safeSelect($table, $conditions = [], $limit = 100, $offset = 0) {
        $allowedTables = ['feedback', 'public_visits', 'upload_logs', 'posts', 'users'];
        
        if (!in_array($table, $allowedTables)) {
            throw new SecurityException("Table not allowed: $table");
        }
        
        // Enforce reasonable limits
        $limit = min($limit, 1000);
        $offset = max($offset, 0);
        
        $sql = "SELECT * FROM $table";
        $params = [];
        
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $column => $value) {
                // Validate column names (whitelist approach)
                if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $column)) {
                    throw new SecurityException("Invalid column name: $column");
                }
                
                $whereClause[] = "$column = :$column";
                $params[$column] = $value;
            }
            $sql .= " WHERE " . implode(" AND ", $whereClause);
        }
        
        $sql .= " LIMIT $limit OFFSET $offset";
        
        return $this->safeQuery($sql, $params);
    }
}

// Initialize secure database
$secureDB = new SecureDatabase($pdo);
?>