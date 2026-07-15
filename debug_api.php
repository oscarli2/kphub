<?php
require 'session_validate.php';
require 'upload_logger.php';

// Set content type to plain text for easier reading
header('Content-Type: text/plain');

// Debug the actual API call
try {
    echo "=== API DEBUG TEST ===\n\n";
    
    // First, check if there are any records at all
    echo "=== BASIC DATABASE CHECK ===\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM upload_logs");
    $totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "Total records in upload_logs table: $totalRecords\n\n";
    
    if ($totalRecords > 0) {
        // Show some sample records
        echo "=== SAMPLE RECORDS ===\n";
        $stmt = $pdo->query("SELECT * FROM upload_logs ORDER BY upload_time DESC LIMIT 3");
        $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($samples as $sample) {
            echo "ID: {$sample['log_id']}, User: {$sample['user_id']}, File: {$sample['filename']}, Time: {$sample['upload_time']}\n";
        }
        echo "\n";
    }
    
    // Test the getUploadLogs function
    echo "=== TESTING getUploadLogs() FUNCTION ===\n";
    $logs = getUploadLogs(25, 0, null, null);
    echo "getUploadLogs() returned " . count($logs) . " records\n";
    
    if (count($logs) > 0) {
        echo "First record details:\n";
        print_r($logs[0]);
    } else {
        echo "No records returned by getUploadLogs()\n";
        
        // Test with direct query to see what's different
        echo "\n=== DIRECT QUERY TEST ===\n";
        $directSql = "
            SELECT 
                ul.*,
                u.email,
                u.facility,
                u.role
            FROM upload_logs ul
            LEFT JOIN users u ON ul.user_id = u.user_id
            WHERE 1=1
            ORDER BY ul.upload_time DESC
            LIMIT 25 OFFSET 0
        ";
        echo "Direct SQL: $directSql\n";
        $stmt = $pdo->query($directSql);
        $directResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Direct query returned " . count($directResults) . " records\n";
        
        if (count($directResults) > 0) {
            echo "Direct query first record:\n";
            print_r($directResults[0]);
        }
    }
    
    // Test API endpoint directly
    echo "\n=== TESTING API ENDPOINT ===\n";
    $_GET['action'] = 'logs';
    $_GET['page'] = '1';
    $_GET['limit'] = '25';
    
    ob_start();
    include 'api_upload_logs.php';
    $apiOutput = ob_get_clean();
    
    echo "API output length: " . strlen($apiOutput) . " characters\n";
    echo "API output: $apiOutput\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString();
}
?>