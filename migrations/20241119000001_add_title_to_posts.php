<?php

class AddTitleToPostsMigration {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function up() {
        // Check if title column already exists
        $stmt = $this->pdo->query("SHOW COLUMNS FROM posts LIKE 'title'");
        if ($stmt->rowCount() == 0) {
            // Add title column to posts table
            $this->pdo->exec("
                ALTER TABLE posts
                ADD COLUMN title VARCHAR(255) NOT NULL DEFAULT 'Untitled Post' AFTER user_id
            ");
        }
    }

    public function down() {
        // Remove title column from posts table
        $this->pdo->exec("
            ALTER TABLE posts
            DROP COLUMN title
        ");
    }
}