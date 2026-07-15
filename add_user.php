<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = $_POST['email'];
  $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
  $facility = $_POST['facility'];
  $role = $_POST['role'];

  $stmt = $pdo->prepare("INSERT INTO users (email, password, facility, role) VALUES (?, ?, ?, ?)");
  $stmt->execute([$email, $password, $facility, $role]);

  echo "<div style='padding:20px; font-family: Arial;'>
    <h2 style='color:#4CAF50;'>User Added Successfully</h2>
    <a href='admin.php' style='color:#4CAF50;'>Back to Admin</a>
  </div>";
  exit;
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Add User</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f8f9fa; padding: 40px; }
    form { background: #fff; padding: 20px; border-radius: 5px; width: 300px; margin: auto; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    h2 { color: #4CAF50; }
    label { display: block; margin: 10px 0 5px; }
    input, select { width: 93%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
    button { display: inline-block; background: #4CAF50; color: #fff; border: none; padding: 10px; border-radius: 4px; cursor: pointer; margin-bottom: 10px; margin-top: 15px; margin-left: auto; }
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
    
    a:hover, a:active {
      background-color: #a0061a;
      color: white;
    }
  </style>
</head>
<body>
  <form method="POST">
    <h2>Add User</h2>
    <label>Email</label>
    <input type="email" name="email" required>

    <label>Password</label>
    <input type="password" name="password" required>

    <label>Facility</label>
    <input type="text" name="facility" required>

    <label>Role</label>
    <select name="role">
      <option value="Admin">Admin</option>
      <option value="Head">Head</option>
      <option value="Member">Member</option>
    </select>
    <a href="admin.php" id="cancel">Cancel</a>
    <button id="add" type="submit">Add User</button>
  </form>
</body>
</html>
