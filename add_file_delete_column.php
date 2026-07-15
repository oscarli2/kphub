<?php
require 'db.php';
try {
    $pdo->exec('ALTER TABLE users ADD COLUMN can_delete_files TINYINT(1) DEFAULT 1');
    echo 'Column can_delete_files added successfully';
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>