<?php
require_once 'page_security.php';

// Initialize page security and require admin access
PageSecurity::initPageSecurity();
PageSecurity::requireAdmin();

require 'db.php';

// Get all users
$stmt = $pdo->query("SELECT * FROM users ORDER BY user_id ASC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <title>Admin Dashboard</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f8f9fa;
      padding: 40px;
    }
    h2 {
      color: #4CAF50;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
      background: #fff;
      border-radius: 5px;
      overflow: hidden;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    th, td {
      padding: 12px 15px;
      border-bottom: 1px solid #ddd;
      text-align: left;
    }
    th {
      background: #4CAF50;
      color: #fff;
    }
    a.button {
      display: inline-block;
      background: #4CAF50;
      color: #fff;
      padding: 8px 12px;
      border-radius: 4px;
      text-decoration: none;
      margin-right: 5px;
      transition: 0.3s;
    }
    button {
      display: inline-block;
      border: none;
      background: #4CAF50;
      color: #fff;
      padding: 8px 12px;
      border-radius: 4px;
      margin-right: 5px;
      transition: 0.3s;
      cursor: pointer;
      font-size: 16px;
    }
    button:hover {
      background: #45a049;
    }
    a.button:hover {
      background: #45a049;
    }
    .top-actions {
      margin-bottom: 20px;
    }
    @keyframes slideIn {
      from { transform: translateY(-20px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }
  </style>
</head>
<body>
    
    <!-- ✅ Beautified Add/Edit User Modal -->
    <div id="userModal" style="
      display: none;
      position: fixed; top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(0,0,0,0.5);
      justify-content: center; align-items: center; z-index: 9999;
      font-family: Arial, sans-serif;
    ">
      <div style="
        background: #fff;
        padding: 30px;
        border-radius: 10px;
        width: 400px;
        max-width: 90%;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        position: relative;
        animation: slideIn 0.3s ease-out;
      ">
        <h2 id="modalTitle" style="margin-top: 0; color: #4CAF50;">Add User</h2>
        <form id="userForm">
          <div style="margin-bottom: 15px;">
            <label for="email" style="font-weight: bold;">Email</label><br>
            <input type="email" name="email" id="email" required style="
              width: 94%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
          </div>
          <div style="margin-bottom: 15px;">
            <label for="password" style="font-weight: bold;">Password</label><br>
            <input type="password" name="password" id="password" placeholder="Leave blank to keep same password" style="
              width: 94%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
          </div>
          <div style="margin-bottom: 15px;">
            <label for="facility" style="font-weight: bold;">Facility</label><br>
            <input type="text" name="facility" id="facility" required style="
              width: 94%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
          </div>
          <div style="margin-bottom: 20px;">
            <label for="role" style="font-weight: bold;">Role</label><br>
            <select name="role" id="role" style="
              width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
              <option value="Admin">Admin</option>
              <option value="Head">Head</option>
              <option value="Member">Member</option>
            </select>
          </div>
          <div style="margin-bottom: 20px;">
            <label for="folder_id" style="font-weight: bold;">Google Drive Folder ID (optional)</label><br>
            <input type="text" name="folder_id" id="folder_id" placeholder="Enter Google Drive folder ID" style="
              width: 94%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
            <small style="color: #666; font-size: 12px;">Used for Google Drive integration and folder sharing</small>
          </div>
          <div style="margin-bottom: 20px;">
            <label style="font-weight: bold;">Permissions</label><br>
            <div style="display: flex; flex-wrap: wrap; gap: 15px; margin-top: 10px;">
              <label style="display: flex; align-items: center; gap: 5px;">
                <input type="checkbox" name="can_create_folder" id="can_create_folder" style="width: 16px; height: 16px;">
                <span style="font-size: 14px;">Can create folders</span>
              </label>
              <label style="display: flex; align-items: center; gap: 5px;">
                <input type="checkbox" name="can_delete" id="can_delete" style="width: 16px; height: 16px;">
                <span style="font-size: 14px;">Can delete posts</span>
              </label>
              <label style="display: flex; align-items: center; gap: 5px;">
                <input type="checkbox" name="can_delete_files" id="can_delete_files" style="width: 16px; height: 16px;">
                <span style="font-size: 14px;">Can delete files</span>
              </label>
              <label style="display: flex; align-items: center; gap: 5px;">
                <input type="checkbox" name="can_generate_report" id="can_generate_report" style="width: 16px; height: 16px;">
                <span style="font-size: 14px;">Can generate reports</span>
              </label>
            </div>
          </div>
          <div style="text-align: right;">
            <button type="button" onclick="closeModal()" style="
              background: #ccc; border: none; padding: 8px 12px;
              border-radius: 5px; margin-right: 10px; cursor: pointer;">
              Cancel
            </button>
            <button type="submit" style="
              background: #4CAF50; color: #fff; border: none; padding: 8px 15px;
              border-radius: 5px; cursor: pointer;">
              Save
            </button>
          </div>
          <input type="hidden" id="user_id" name="user_id">
        </form>
      </div>
    </div>

  <h2>EV LGRRC Knowledge Product Hub Admin Dashboard</h2>

  <div class="top-actions">
    <a href="#" class="button" onclick="openAddModal()">Add User</a>
    <a href="upload_monitoring.php" class="button" style="background: #2196F3;">
      <i class="fas fa-chart-line"></i> All System Logs
    </a>
    <a href="index.php" class="button">Back to Home</a>
  </div>

  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Email</th>
        <th>Facility</th>
        <th>Role</th>
        <th>Google Drive</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $user): ?>
        <tr>
          <td><?= htmlspecialchars($user['user_id']) ?></td>
          <td><?= htmlspecialchars($user['email']) ?></td>
          <td><?= htmlspecialchars($user['facility']) ?></td>
          <td><?= htmlspecialchars($user['role']) ?></td>
          <td>
            <?php if (!empty($user['folder_id'])): ?>
              <span style="color: #4CAF50;">✓ Configured</span>
            <?php else: ?>
              <span style="color: #f44336;">Not set</span>
            <?php endif; ?>
          </td>
          <td>
            <a href="#" class="button" onclick='openEditModal(<?= json_encode($user) ?>)'>Edit</a>
            <?php if ((int)$user['user_id'] !== (int)($_SESSION['user_id'] ?? 0)): ?>
            <button onclick="impersonateUser(<?= (int)$user['user_id'] ?>, '<?= htmlspecialchars($user['email'], ENT_QUOTES) ?>')" class="button" style="background: #ff9800;">Impersonate</button>
            <?php endif; ?>
            <button onclick="deleteUser(<?= $user['user_id'] ?>)" class="button" style="background: #f44336;">Delete</button>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<script>
  function openAddModal() {
    document.getElementById('modalTitle').innerText = "Add User";
    document.getElementById('userForm').reset();
    document.getElementById('user_id').value = "";
    // Set default permissions for new users
    document.getElementById('can_create_folder').checked = true;
    document.getElementById('can_delete').checked = true;
    document.getElementById('can_delete_files').checked = true;
    document.getElementById('can_generate_report').checked = true;
    document.getElementById('userModal').style.display = 'flex';
  }

  function openEditModal(user) {
    document.getElementById('modalTitle').innerText = "Edit User";
    document.getElementById('user_id').value = user.user_id;
    document.getElementById('email').value = user.email;
    document.getElementById('facility').value = user.facility;
    document.getElementById('role').value = user.role;
    document.getElementById('folder_id').value = user.folder_id || '';
    // Set permission checkboxes based on user data
    document.getElementById('can_create_folder').checked = user.can_create_folder == 1;
    document.getElementById('can_delete').checked = user.can_delete == 1;
    document.getElementById('can_delete_files').checked = user.can_delete_files == 1;
    document.getElementById('can_generate_report').checked = user.can_generate_report == 1;
    // Password left empty for security
    document.getElementById('password').value = "";
    document.getElementById('userModal').style.display = 'flex';
  }

  function closeModal() {
    document.getElementById('userModal').style.display = 'none';
  }

  // Submit form
  document.getElementById('userForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const isEdit = formData.get('user_id') !== "";
    formData.append('action', isEdit ? 'edit' : 'add');

    fetch('api_users.php', {
      method: 'POST',
      body: formData
    }).then(r => r.json())
      .then(res => {
        if (res.success) {
          alert(res.message);
          window.location.reload();
        } else {
          alert(res.message || "Something went wrong");
        }
      });
  });
  
  function deleteUser(userId) {
    Swal.fire({
      title: 'Are you sure?',
      text: "This action will permanently delete the user.",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#aaa',
      confirmButtonText: 'Yes, delete it!',
      reverseButtons: true
    }).then((result) => {
      if (result.isConfirmed) {
        // Make your delete request
        fetch('delete_user.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ user_id: userId })
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            Swal.fire(
              'Deleted!',
              'The user has been removed.',
              'success'
            ).then(() => location.reload());
          } else {
            Swal.fire(
              'Error!',
              data.error || 'Something went wrong.',
              'error'
            );
          }
        })
        .catch(err => {
          Swal.fire(
            'Error!',
            err.message || 'Something went wrong.',
            'error'
          );
        });
      }
    });
  }

  function impersonateUser(userId, userEmail) {
    Swal.fire({
      title: 'Impersonate User?',
      html: `You are about to sign in as <strong>${userEmail}</strong>.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Continue',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#ff9800'
    }).then((result) => {
      if (!result.isConfirmed) {
        return;
      }

      fetch('impersonate_user.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: userId })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          window.location.href = 'index.php';
          return;
        }

        Swal.fire('Error', data.message || 'Failed to impersonate user.', 'error');
      })
      .catch(err => {
        Swal.fire('Error', err.message || 'Failed to impersonate user.', 'error');
      });
    });
  }
</script>
</body>
</html>
