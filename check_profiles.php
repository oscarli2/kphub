<?php
require 'db.php';
try {
    $stmt = $pdo->query('SELECT user_id, email, profile_picture FROM users WHERE profile_picture IS NOT NULL AND profile_picture != "" LIMIT 10');
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo 'Users with profile pictures:' . PHP_EOL;
    foreach ($users as $user) {
        echo 'User: ' . $user['email'] . ' - Picture: ' . $user['profile_picture'] . PHP_EOL;
        $fullPath = 'uploads/' . $user['profile_picture'];
        echo '  Full path: ' . $fullPath . PHP_EOL;
        echo '  Exists: ' . (file_exists($fullPath) ? 'YES' : 'NO') . PHP_EOL;
        echo '---' . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>