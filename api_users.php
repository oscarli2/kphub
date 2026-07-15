<?php
require 'db.php';

$action = $_POST['action'] ?? '';

if ($action === 'add') {
  $email = $_POST['email'];
  $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
  $facility = $_POST['facility'];
  $role = $_POST['role'];
  $folder_id = trim($_POST['folder_id'] ?? '');
  $can_create_folder = isset($_POST['can_create_folder']) ? 1 : 0;
  $can_delete = isset($_POST['can_delete']) ? 1 : 0;
  $can_delete_files = isset($_POST['can_delete_files']) ? 1 : 0;
  $can_generate_report = isset($_POST['can_generate_report']) ? 1 : 0;

  $stmt = $pdo->prepare("INSERT INTO users (email, password, facility, role, folder_id, can_create_folder, can_delete, can_delete_files, can_generate_report) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
  $stmt->execute([$email, $password, $facility, $role, $folder_id, $can_create_folder, $can_delete, $can_delete_files, $can_generate_report]);

  echo json_encode(['success' => true, 'message' => 'User added successfully.']);
}

elseif ($action === 'edit') {
  $user_id = $_POST['user_id'];
  $email = $_POST['email'];
  $facility = $_POST['facility'];
  $role = $_POST['role'];
  $folder_id = trim($_POST['folder_id'] ?? '');
  $can_create_folder = isset($_POST['can_create_folder']) ? 1 : 0;
  $can_delete = isset($_POST['can_delete']) ? 1 : 0;
  $can_delete_files = isset($_POST['can_delete_files']) ? 1 : 0;
  $can_generate_report = isset($_POST['can_generate_report']) ? 1 : 0;

  if (!empty($_POST['password'])) {
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET email = ?, password = ?, facility = ?, role = ?, folder_id = ?, can_create_folder = ?, can_delete = ?, can_delete_files = ?, can_generate_report = ? WHERE user_id = ?");
    $stmt->execute([$email, $password, $facility, $role, $folder_id, $can_create_folder, $can_delete, $can_delete_files, $can_generate_report, $user_id]);
  } else {
    $stmt = $pdo->prepare("UPDATE users SET email = ?, facility = ?, role = ?, folder_id = ?, can_create_folder = ?, can_delete = ?, can_delete_files = ?, can_generate_report = ? WHERE user_id = ?");
    $stmt->execute([$email, $facility, $role, $folder_id, $can_create_folder, $can_delete, $can_delete_files, $can_generate_report, $user_id]);
  }

  echo json_encode(['success' => true, 'message' => 'User updated successfully.']);
}

else {
  echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
