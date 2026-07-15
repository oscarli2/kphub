<?php
require 'session_validate.php';
require 'upload_logger.php';

echo "<h1>Debug Upload Logs</h1>";
echo "<h3>All System Logs Debug</h3>";

// Check if table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'upload_logs'");
    $tableExists = $stmt->rowCount() > 0;
    echo "<p>Upload logs table exists: " . ($tableExists ? 'YES' : 'NO') . "</p>";
    
    if ($tableExists) {
        // Check table structure
        $stmt = $pdo->query("DESCRIBE upload_logs");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h4>Table Structure:</h4><pre>";
        print_r($columns);
        echo "</pre>";
        
        // Check if there's any data
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM upload_logs");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p>Total records: $count</p>";
        
        if ($count == 0) {
            echo "<p><strong>No upload logs found. Upload a file in a post to create test data.</strong></p>";
        }
        
        // Test the stats function
        echo "<h4>Stats Function Test:</h4>";
        $stats = getUploadStats();
        echo "<pre>";
        print_r($stats);
        echo "</pre>";
        
        // Test direct API call
        echo "<h4>Direct API Test:</h4>";
        echo '<a href="api_upload_logs.php?action=test" target="_blank">Test API Endpoint</a><br>';
        echo '<a href="api_upload_logs.php?action=stats" target="_blank">Test Stats Endpoint</a>';
        
    } else {
        echo "<p><strong>Table doesn't exist! Creating it now...</strong></p>";
        
        $createTable = "
        CREATE TABLE upload_logs (
            log_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            filename VARCHAR(255) NOT NULL,
            file_size BIGINT,
            file_type VARCHAR(50),
            upload_location VARCHAR(500),
            upload_type ENUM('post_attachment', 'profile_picture', 'folder_upload') DEFAULT 'post_attachment',
            ip_address VARCHAR(45),
            user_agent TEXT,
            upload_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
            INDEX idx_upload_time (upload_time),
            INDEX idx_user_id (user_id)
        )";
        
        $pdo->exec($createTable);
        echo "<p>Table created successfully!</p>";
        echo '<meta http-equiv="refresh" content="2">';
    }
    
} catch (Exception $e) {
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
}
?>