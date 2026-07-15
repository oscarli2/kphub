<?php

class AddLinksToPostsMigration {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function up() {
        // Check if links column already exists
        $stmt = $this->pdo->query("SHOW COLUMNS FROM posts LIKE 'links'");
        if ($stmt->rowCount() == 0) {
            // Add links column to posts table
            $this->pdo->exec("
                ALTER TABLE posts
                ADD COLUMN links TEXT NULL AFTER file_type
            ");
        }
    }

    public function down() {
        // Remove links column from posts table
        $this->pdo->exec("
            ALTER TABLE posts
            DROP COLUMN links
        ");
    }
}