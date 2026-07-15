<?php
require 'db.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['user_id'])) {
  echo json_encode(['success' => false, 'error' => 'Invalid request.']);
  exit;
}

$userId = (int)$data['user_id'];
$stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
if ($stmt->execute([$userId])) {
  echo json_encode(['success' => true]);
} else {
  echo json_encode(['success' => false, 'error' => 'Delete failed.']);
}
?>
