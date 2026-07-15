<?php
require_once 'page_security.php';

PageSecurity::initPageSecurity();
PageSecurity::requireAdmin();

require 'db.php';

header('Content-Type: application/json');

$rawInput = file_get_contents('php://input');
$payload = json_decode((string)$rawInput, true);

$targetUserId = (int)($payload['user_id'] ?? 0);
$adminUserId = (int)($_SESSION['user_id'] ?? 0);

if ($targetUserId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid target user.']);
    exit;
}

if ($targetUserId === $adminUserId) {
    echo json_encode(['success' => false, 'message' => 'Cannot impersonate your own account.']);
    exit;
}

$stmt = $pdo->prepare('SELECT user_id, email, facility, role, profile_picture, can_create_folder, can_delete, can_delete_files, can_generate_report, folder_id FROM users WHERE user_id = ? LIMIT 1');
$stmt->execute([$targetUserId]);
$targetUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$targetUser) {
    echo json_encode(['success' => false, 'message' => 'User not found.']);
    exit;
}

if (empty($_SESSION['impersonation_admin_user_id'])) {
    $_SESSION['impersonation_admin_user_id'] = $adminUserId;
    $_SESSION['impersonation_admin_email'] = $_SESSION['email'] ?? '';
    $_SESSION['impersonation_admin_facility'] = $_SESSION['facility'] ?? '';
    $_SESSION['impersonation_admin_role'] = $_SESSION['role'] ?? 'Admin';
    $_SESSION['impersonation_admin_profile_picture'] = $_SESSION['profile_picture'] ?? '';
    $_SESSION['impersonation_admin_can_create_folder'] = $_SESSION['can_create_folder'] ?? 0;
    $_SESSION['impersonation_admin_can_delete'] = $_SESSION['can_delete'] ?? 0;
    $_SESSION['impersonation_admin_can_delete_files'] = $_SESSION['can_delete_files'] ?? 0;
    $_SESSION['impersonation_admin_can_generate_report'] = $_SESSION['can_generate_report'] ?? 0;
    $_SESSION['impersonation_admin_folder_id'] = $_SESSION['folder_id'] ?? '';
}

session_regenerate_id(true);

$_SESSION['user_id'] = (int)$targetUser['user_id'];
$_SESSION['email'] = (string)$targetUser['email'];
$_SESSION['facility'] = (string)$targetUser['facility'];
$_SESSION['role'] = (string)$targetUser['role'];
$_SESSION['profile_picture'] = (string)($targetUser['profile_picture'] ?? '');
$_SESSION['can_create_folder'] = (int)$targetUser['can_create_folder'];
$_SESSION['can_delete'] = (int)$targetUser['can_delete'];
$_SESSION['can_delete_files'] = (int)$targetUser['can_delete_files'];
$_SESSION['can_generate_report'] = (int)$targetUser['can_generate_report'];
$_SESSION['folder_id'] = (string)($targetUser['folder_id'] ?? '');
$_SESSION['is_impersonating'] = 1;

if (empty($_SESSION['folder_id'])) {
    switch (strtoupper((string)$targetUser['facility'])) {
        case 'MMKI':
            $_SESSION['folder_id'] = '12iYH8uDg9Zx7TZDA9HZhSVc0vYSjbi7r';
            break;
        case 'CAPDEV':
            $_SESSION['folder_id'] = '1S9KnuKJpKo5V-CT95CaILBRn78P8_yAp';
            break;
        case 'INSTI-LEGAL':
            $_SESSION['folder_id'] = '1KDUMT0tDHUY4wNoJPZA-FPJaRFVB1gLp';
            break;
        case 'PUBLIC ED':
            $_SESSION['folder_id'] = '1XVzrb2jh_m0jO22xo34jMEwtD7dAr_z0';
            break;
        case 'BILIRAN':
            $_SESSION['folder_id'] = '1bxeTbYsbh24JmVCGvrDEVRjjCiYJ65NM';
            break;
        case 'EASTERN SAMAR':
            $_SESSION['folder_id'] = '1oNfEXhc9pZfmUXLQQgN94PnpAst6T8OK';
            break;
        case 'NORTHERN SAMAR':
            $_SESSION['folder_id'] = '1HLQ9yOxhvbeAuv5DOMLR2Z3jDDJjZwVt';
            break;
        case 'LEYTE':
            $_SESSION['folder_id'] = '1y6z5dPyXRXgi2Yw0BQdET7cv6uzfOOyg';
            break;
        case 'SOUTHERN LEYTE':
            $_SESSION['folder_id'] = '1paeMrEqoXsqjNkz92CzQS8vpBO3zw5Dh';
            break;
        case 'ORMOC':
            $_SESSION['folder_id'] = '1DNBPpy57XnfJcydrRuBLZBGJiSgLTql6';
            break;
        case 'TACLOBAN':
            $_SESSION['folder_id'] = '1kDXCDHAbUo8zwgbXxMJS0BUYAYnUw4IH';
            break;
        case 'LINKAGE':
            $_SESSION['folder_id'] = '1kVFVPSjm9mJVPvQ4A_BfVORZmw0iga94';
            break;
        case 'SAMAR':
            $_SESSION['folder_id'] = '1We0wIJMg0XURRRDWRq4QQNt6ejGJYrZG';
            break;
        default:
            $_SESSION['folder_id'] = '1WqMh5IylXYHS-o_iPxImJLHczCi-WtyN';
            break;
    }
}

echo json_encode(['success' => true, 'message' => 'Impersonation started.']);
