<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $user_id = $_POST['user_id'];
  $email = $_POST['email'];
  $facility = $_POST['facility'];
  $role = $_POST['role'];

  $stmt = $pdo->prepare("UPDATE users SET email = ?, facility = ?, role = ? WHERE user_id = ?");
  $stmt->execute([$email, $facility, $role, $user_id]);

  echo "<div style='padding:20px; font-family: Arial;'>
    <h2 style='color:#4CAF50;'>User Updated</h2>
    <a href='admin.php' style='color:#4CAF50;'>Back to Admin</a>
  </div>";
  exit;
}

if (!isset($_GET['user_id'])) {
  echo "No user ID specified.";
  exit;
}

$user_id = (int)$_GET['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
  echo "User not found.";
  exit;
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Edit User</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f8f9fa; padding: 40px; }
    form { background: #fff; padding: 20px; border-radius: 5px; width: 300px; margin: auto; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    h2 { color: #4CAF50; }
    label { display: block; margin: 10px 0 5px; }
    input, select { width: 94%; padding: 8px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px; }
    button { background: #4CAF50; color: #fff; border: none; padding: 10px; width: 100%; border-radius: 4px; cursor: pointer; }
    button:hover { background: #45a049; }
    #add {width: 60%;}
    a:link, a:visited {
      display: inline-block;
      background-color: #ce0c26;
      color: #fff;
      border: none; 
      padding: 10px; 
      border-radius: 4px; 
      cursor: pointer; 
      margin-bottom: 10px; 
      margin-top: 15px; 
      margin-left: auto; 
      font-size: 13px; 
      text-align: center;
      padding: 10px 20px;
      text-align: center;
      font-size: 13px;
      text-decoration: none;
      width: 20%;
    }
  </style>
</head>
<body>
  <form method="POST">
    <h2>Edit User</h2>
    <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['user_id']) ?>">

    <label>Email</label>
    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

    <label>Facility</label>
    <input type="text" name="facility" value="<?= htmlspecialchars($user['facility']) ?>" required>

    <label>Role</label>
    <select name="role">
      <option value="Admin" <?= $user['role'] === 'Admin' ? 'selected' : '' ?>>Admin</option>
      <option value="Head" <?= $user['role'] === 'Head' ? 'selected' : '' ?>>Head</option>
      <option value="Member" <?= $user['role'] === 'Member' ? 'selected' : '' ?>>Member</option>
    </select>
    <a href="admin.php" id="cancel">Cancel</a>
    <button id="add" type="submit">Add User</button>
  </form>
</body>
</html>
