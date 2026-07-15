<?php
session_start();
header('Content-Type: application/json');

// Disable error display for production
error_reporting(0);
ini_set('display_errors', 0);

// Try to load security if available, but don't fail if missing
if (file_exists('security.php')) {
    require_once 'security.php';
    try {
        SecurityManager::checkRateLimit('login_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        SecurityManager::validateUserAgent();
    } catch (Exception $e) {
        // Continue without security if there's an issue
        error_log("Security check failed: " . $e->getMessage());
    }
}

//DB config - Use Hostinger credentials
$host = "localhost";
$db_user = "u926715344_kphub";
$db_pass = "RictuR82025$";
$db_name = "u926715344_kphub";

// Fallback to local if Hostinger fails (for development)
// $host = "localhost";
// $db_user = "root";
// $db_pass = "";
// $db_name = "kphub";

// Connect
$conn = new mysqli($host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed."]);
    exit;
}

// Get and validate POST data
try {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    
    // Basic validation
    if (empty($email) || empty($password)) {
        echo json_encode(["success" => false, "message" => "Email and password are required."]);
        exit;
    }
    
    // Enhanced validation if security class is available
    if (class_exists('SecurityManager')) {
        $email = SecurityManager::validateInput($email, 'email', 100);
        $password = SecurityManager::validateInput($password, 'general', 100);
    } else {
        // Basic sanitization
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(["success" => false, "message" => "Invalid email format."]);
            exit;
        }
    }
} catch (Exception $e) {
    error_log("Login validation error: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Invalid input detected."]);
    exit;
}

// Query by EMAIL
$stmt = $conn->prepare("SELECT user_id, email, password, facility, role, profile_picture, can_create_folder, can_delete, can_delete_files, can_generate_report, folder_id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    if (password_verify($password, $user['password'])) {
        // ✅ Set session values
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['facility'] = $user['facility'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['profile_picture'] = $user['profile_picture'];
        $_SESSION['can_create_folder'] = $user['can_create_folder'];
        $_SESSION['can_delete'] = $user['can_delete'];
        $_SESSION['can_delete_files'] = $user['can_delete_files'];
        $_SESSION['can_generate_report'] = $user['can_generate_report'];

        // ✅ Set folder ID - use database value if set, otherwise use facility-based fallback
        if (!empty($user['folder_id'])) {
            $_SESSION['folder_id'] = $user['folder_id'];
        } else {
            // Fallback to facility-based folder IDs
            switch (strtoupper($user['facility'])) {
                case 'MMKI':
                    $_SESSION['folder_id'] = '12iYH8uDg9Zx7TZDA9HZhSVc0vYSjbi7r'; break;
                case 'CAPDEV':
                    $_SESSION['folder_id'] = '1S9KnuKJpKo5V-CT95CaILBRn78P8_yAp'; break;
                case 'INSTI-LEGAL':
                    $_SESSION['folder_id'] = '1KDUMT0tDHUY4wNoJPZA-FPJaRFVB1gLp'; break;
                case 'PUBLIC ED':
                    $_SESSION['folder_id'] = '1XVzrb2jh_m0jO22xo34jMEwtD7dAr_z0'; break;
                case 'BILIRAN':
                    $_SESSION['folder_id'] = '1bxeTbYsbh24JmVCGvrDEVRjjCiYJ65NM'; break;
                case 'EASTERN SAMAR':
                    $_SESSION['folder_id'] = '1oNfEXhc9pZfmUXLQQgN94PnpAst6T8OK'; break;
                case 'NORTHERN SAMAR':
                    $_SESSION['folder_id'] = '1HLQ9yOxhvbeAuv5DOMLR2Z3jDDJjZwVt'; break;
                case 'LEYTE':
                    $_SESSION['folder_id'] = '1y6z5dPyXRXgi2Yw0BQdET7cv6uzfOOyg'; break;
                case 'SOUTHERN LEYTE':
                    $_SESSION['folder_id'] = '1paeMrEqoXsqjNkz92CzQS8vpBO3zw5Dh'; break;
                case 'ORMOC':
                    $_SESSION['folder_id'] = '1DNBPpy57XnfJcydrRuBLZBGJiSgLTql6'; break;
                case 'TACLOBAN':
                    $_SESSION['folder_id'] = '1kDXCDHAbUo8zwgbXxMJS0BUYAYnUw4IH'; break;
                case 'LINKAGE':
                    $_SESSION['folder_id'] = '1kVFVPSjm9mJVPvQ4A_BfVORZmw0iga94'; break;
                case 'SAMAR':
                    $_SESSION['folder_id'] = '1We0wIJMg0XURRRDWRq4QQNt6ejGJYrZG'; break;
                default:
                    $_SESSION['folder_id'] = '1WqMh5IylXYHS-o_iPxImJLHczCi-WtyN'; break;
            }
        }

        echo json_encode([
            "success" => true,
            "redirect" => "index.php"
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Incorrect password."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Email not found."]);
}

$stmt->close();
$conn->close();
?>
