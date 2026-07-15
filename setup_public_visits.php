<?php
require 'db.php';

// Create public visits table
$createPublicVisitsTable = "
CREATE TABLE IF NOT EXISTS public_visits (
    visit_id INT AUTO_INCREMENT PRIMARY KEY,
    page VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    visit_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_page (page),
    INDEX idx_visit_time (visit_time),
    INDEX idx_ip_address (ip_address)
)";

try {
    $pdo->exec($createPublicVisitsTable);
    echo "✅ Public visits table created successfully\n";
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>