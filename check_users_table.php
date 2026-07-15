<?php
require 'session_validate.php';

echo "<h3>Users Table Structure</h3>";

try {
    // Show users table structure
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>Users Table Columns:</h4>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show sample user data
    echo "<h4>Sample User Data:</h4>";
    $stmt = $pdo->query("SELECT * FROM users LIMIT 3");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($users)) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr>";
        foreach (array_keys($users[0]) as $key) {
            echo "<th>" . htmlspecialchars($key) . "</th>";
        }
        echo "</tr>";
        
        foreach ($users as $user) {
            echo "<tr>";
            foreach ($user as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
}
?>