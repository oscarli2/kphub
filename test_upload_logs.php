<?php
require 'session_validate.php';

echo "<h3>Database Upload Logs Test</h3>";

try {
    // Check if upload_logs table exists and has data
    $stmt = $pdo->query("SHOW TABLES LIKE 'upload_logs'");
    $tableExists = $stmt->rowCount() > 0;
    echo "<p>Upload logs table exists: " . ($tableExists ? 'YES' : 'NO') . "</p>";
    
    if ($tableExists) {
        // Check total records
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM upload_logs");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p>Total records in upload_logs: $count</p>";
        
        if ($count > 0) {
            // Show recent records
            echo "<h4>Recent Upload Logs:</h4>";
            $stmt = $pdo->query("
                SELECT ul.*, u.email, u.facility, u.role 
                FROM upload_logs ul 
                LEFT JOIN users u ON ul.user_id = u.user_id 
                ORDER BY ul.upload_time DESC 
                LIMIT 10
            ");
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>User</th><th>Filename</th><th>Type</th><th>Upload Time</th><th>Location</th></tr>";
            foreach ($logs as $log) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($log['log_id']) . "</td>";
                echo "<td>" . htmlspecialchars($log['email'] ?? 'Unknown') . "</td>";
                echo "<td>" . htmlspecialchars($log['filename']) . "</td>";
                echo "<td>" . htmlspecialchars($log['upload_type']) . "</td>";
                echo "<td>" . htmlspecialchars($log['upload_time']) . "</td>";
                echo "<td>" . htmlspecialchars($log['upload_location']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // Test the logFileUpload function directly
        echo "<h4>Testing logFileUpload function:</h4>";
        require 'upload_logger.php';
        
        $testResult = logFileUpload(
            $_SESSION['user_id'] ?? 1,
            'test_file.txt',
            1024,
            'txt',
            'Test Location',
            'folder_upload'
        );
        
        echo "<p>Test logFileUpload result: " . ($testResult ? 'SUCCESS' : 'FAILED') . "</p>";
        
        if ($testResult) {
            // Check if the test record was inserted
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM upload_logs WHERE filename = 'test_file.txt'");
            $stmt->execute();
            $testCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "<p>Test record found in database: " . ($testCount > 0 ? 'YES' : 'NO') . "</p>";
            
            // Clean up test record
            if ($testCount > 0) {
                $pdo->prepare("DELETE FROM upload_logs WHERE filename = 'test_file.txt'")->execute();
                echo "<p>Test record cleaned up.</p>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Stack trace:</strong><br>" . nl2br($e->getTraceAsString()) . "</p>";
}

echo "<hr>";
echo "<h4>Quick Actions:</h4>";
echo '<a href="upload_monitoring.php" style="margin-right: 10px;">View All System Logs</a>';
echo '<a href="api_upload_logs.php?action=test">Test API Endpoint</a>';
?>