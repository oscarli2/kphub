<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Profile</title>
  <?php session_start(); ?>
  <style>
        /* Hide the banner but keep the menu bar */
        .site-header .banner {
          display: none !important;
        }

        /* Adjust body margin since banner is hidden */
        body {
          font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
          background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
          min-height: 100vh;
          margin: 0;
          padding: 70px 20px 20px 20px; /* Added top padding for menu bar */
        }

        .profile-container {
          max-width: 600px;
          margin: 0 auto;
          background: #fff;
          border-radius: 15px;
          box-shadow: 0 20px 40px rgba(0,0,0,0.1);
          overflow: hidden;
        }

        .profile-header {
          background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
          color: white;
          padding: 30px;
          text-align: center;
        }

        .profile-header h2 {
          margin: 0;
          font-size: 28px;
          font-weight: 300;
        }

        .profile-content {
          padding: 40px;
        }

        .current-profile-pic {
          text-align: center;
          margin-bottom: 30px;
        }

        .profile-pic-container {
          position: relative;
          display: inline-block;
          margin-bottom: 15px;
        }

        .profile-pic {
          width: 120px;
          height: 120px;
          border-radius: 50%;
          object-fit: cover;
          border: 4px solid #4CAF50;
          box-shadow: 0 8px 16px rgba(0,0,0,0.2);
          transition: transform 0.3s ease;
        }

        .profile-pic:hover {
          transform: scale(1.05);
        }

        .form-group {
          margin-bottom: 25px;
        }

        .form-group label {
          display: block;
          margin-bottom: 8px;
          color: #333;
          font-weight: 500;
          font-size: 14px;
        }

        .form-group input[type="email"],
        .form-group input[type="password"] {
          width: 100%;
          padding: 12px 16px;
          border: 2px solid #e1e5e9;
          border-radius: 8px;
          font-size: 16px;
          transition: border-color 0.3s ease;
          box-sizing: border-box;
        }

        .form-group input[type="email"]:focus,
        .form-group input[type="password"]:focus,
        .form-group input[type="text"]:focus {
          outline: none;
          border-color: #4CAF50;
          box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
        }

        .password-confirm-field {
          display: none;
          margin-top: 15px;
        }

        .password-confirm-field input.correct {
          border-color: #28a745;
          box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
        }

        .password-confirm-field input.incorrect {
          border-color: #dc3545;
          box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
        }

        .password-match-status {
          margin-top: 5px;
          font-size: 12px;
          font-weight: 500;
        }

        .password-match-status.correct {
          color: #28a745;
        }

        .password-match-status.incorrect {
          color: #dc3545;
        }

        .password-confirmation {
          display: none;
          margin-top: 15px;
          padding: 15px;
          background: #fff3cd;
          border: 1px solid #ffeaa7;
          border-radius: 8px;
          animation: slideDown 0.3s ease;
        }

        .password-confirmation label {
          color: #856404;
          font-weight: 600;
          margin-bottom: 8px;
          display: block;
        }

        .password-confirmation input {
          width: 100%;
          padding: 10px 12px;
          border: 2px solid #ffeaa7;
          border-radius: 6px;
          font-size: 14px;
          box-sizing: border-box;
        }

        .password-confirmation input:focus {
          border-color: #856404;
          box-shadow: 0 0 0 3px rgba(133, 100, 4, 0.1);
        }

        .password-confirmation input.correct {
          border-color: #28a745;
          box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
        }

        .password-confirmation input.incorrect {
          border-color: #dc3545;
          box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
        }

        .confirmation-status {
          margin-top: 5px;
          font-size: 12px;
          font-weight: 500;
        }

        .confirmation-status.correct {
          color: #28a745;
        }

        .confirmation-status.incorrect {
          color: #dc3545;
        }

        @keyframes slideDown {
          from {
            opacity: 0;
            transform: translateY(-10px);
          }
          to {
            opacity: 1;
            transform: translateY(0);
          }
        }

        .password-section {
          position: relative;
        }

        .password-strength-container {
          margin-top: 10px;
        }

        .password-strength-meter {
          height: 6px;
          width: 100%;
          background: #e0e0e0;
          border-radius: 3px;
          overflow: hidden;
          margin-bottom: 5px;
        }

        .password-strength-bar {
          height: 100%;
          width: 0%;
          transition: all 0.3s ease;
          border-radius: 3px;
        }

        .password-strength-text {
          font-size: 12px;
          font-weight: 500;
          text-transform: uppercase;
          letter-spacing: 0.5px;
        }

        .profile-pic-container {
          position: relative;
          display: inline-block;
          cursor: pointer;
          transition: transform 0.2s ease;
        }

        .profile-pic-container:hover {
          transform: scale(1.05);
        }

        .profile-pic-overlay {
          position: absolute;
          top: 0;
          left: 0;
          right: 0;
          bottom: 0;
          background: rgba(0, 0, 0, 0.7);
          display: flex;
          align-items: center;
          justify-content: center;
          opacity: 0;
          transition: opacity 0.3s ease;
          border-radius: 50%;
        }

        .profile-pic-container:hover .profile-pic-overlay {
          opacity: 1;
        }

        .change-pic-btn {
          color: white;
          font-size: 12px;
          font-weight: 500;
          text-decoration: underline;
          cursor: pointer;
          padding: 4px 8px;
          border-radius: 4px;
          transition: background-color 0.3s ease;
          position: relative;
        }

        .change-pic-btn:hover {
          background: rgba(255, 255, 255, 0.2);
        }

        /* Tooltip styles */
        .tooltip {
          position: relative;
          display: inline-block;
        }

        .tooltip .tooltip-text {
          visibility: hidden;
          width: 200px;
          background-color: #333;
          color: #fff;
          text-align: center;
          border-radius: 6px;
          padding: 8px 12px;
          position: absolute;
          z-index: 1;
          bottom: 125%;
          left: 50%;
          margin-left: -100px;
          opacity: 0;
          transition: opacity 0.3s;
          font-size: 12px;
          line-height: 1.4;
        }

        .tooltip .tooltip-text::after {
          content: "";
          position: absolute;
          top: 100%;
          left: 50%;
          margin-left: -5px;
          border-width: 5px;
          border-style: solid;
          border-color: #333 transparent transparent transparent;
        }

        .tooltip:hover .tooltip-text {
          visibility: visible;
          opacity: 1;
        }

        .file-input {
          display: none;
        }

        .file-preview {
          margin-top: 8px;
          text-align: center;
        }

        .file-preview img {
          max-width: 100px;
          max-height: 100px;
          border-radius: 8px;
          border: 2px solid #4CAF50;
          margin-bottom: 8px;
        }

        /* Crop Modal Styles */
        .crop-modal {
          display: none;
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background: rgba(0, 0, 0, 0.8);
          z-index: 10000;
          justify-content: center;
          align-items: center;
        }

        .crop-modal.show {
          display: flex;
        }

        .crop-container {
          background: white;
          border-radius: 12px;
          padding: 20px;
          max-width: 600px;
          width: 90%;
          max-height: 80vh;
          overflow: hidden;
          box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .crop-header {
          text-align: center;
          margin-bottom: 20px;
          padding-bottom: 15px;
          border-bottom: 1px solid #eee;
        }

        .crop-header h3 {
          margin: 0;
          color: #333;
          font-size: 18px;
        }

        .crop-canvas-container {
          position: relative;
          margin: 0 auto;
          max-width: 400px;
          border: 2px solid #4CAF50;
          border-radius: 8px;
          overflow: hidden;
        }

        .crop-canvas {
          display: block;
          max-width: 100%;
          cursor: move;
        }

        .crop-overlay {
          position: absolute;
          top: 0;
          left: 0;
          border: 2px solid #fff;
          box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.5);
          cursor: move;
          pointer-events: none;
        }

        .crop-instructions {
          text-align: center;
          margin: 15px 0;
          color: #666;
          font-size: 14px;
        }

        .crop-controls {
          display: flex;
          justify-content: center;
          gap: 10px;
          margin-top: 20px;
        }

        .crop-btn {
          padding: 10px 20px;
          border: none;
          border-radius: 6px;
          font-size: 14px;
          font-weight: 500;
          cursor: pointer;
          transition: all 0.3s ease;
        }

        .crop-btn.apply {
          background: #4CAF50;
          color: white;
        }

        .crop-btn.apply:hover {
          background: #45a049;
          transform: translateY(-1px);
        }

        .crop-btn.cancel {
          background: #f44336;
          color: white;
        }

        .crop-btn.cancel:hover {
          background: #d32f2f;
          transform: translateY(-1px);
        }

        .submit-btn {
          width: 100%;
          padding: 15px;
          background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
          color: white;
          border: none;
          border-radius: 8px;
          font-size: 16px;
          font-weight: 600;
          cursor: pointer;
          transition: all 0.3s ease;
          margin-top: 20px;
        }

        .submit-btn:hover {
          transform: translateY(-2px);
          box-shadow: 0 8px 20px rgba(76, 175, 80, 0.3);
        }

        .submit-btn:disabled {
          background: #ccc;
          cursor: not-allowed;
          transform: none;
          box-shadow: none;
        }

        .result-message {
          margin-top: 20px;
          padding: 12px;
          border-radius: 6px;
          text-align: center;
          font-weight: 500;
        }

        .result-message.success {
          background: #d4edda;
          color: #155724;
          border: 1px solid #c3e6cb;
        }

        .result-message.error {
          background: #f8d7da;
          color: #721c24;
          border: 1px solid #f5c6cb;
        }

        .loading {
          display: inline-block;
          width: 20px;
          height: 20px;
          border: 3px solid #f3f3f3;
          border-top: 3px solid #4CAF50;
          border-radius: 50%;
          animation: spin 1s linear infinite;
          margin-right: 10px;
        }

        @keyframes spin {
          0% { transform: rotate(0deg); }
          100% { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
          .profile-content {
            padding: 20px;
          }

          .profile-pic {
            width: 100px;
            height: 100px;
          }
        }
  </style>
</head>
<body>

    <?php include 'header.php'; ?>

  <div class="profile-container">
    <div class="profile-header">
      <h2>My Profile</h2>
    </div>

    <div class="profile-content">
      <!-- Current Profile Picture Display -->
      <div class="current-profile-pic">
        <div class="profile-pic-container">
          <img id="currentProfilePic" src="/uploads/default.png" alt="Current Profile Picture" class="profile-pic">
          <div class="profile-pic-overlay">
            <div class="tooltip">
              <label for="profilePic" class="change-pic-btn">Change Picture</label>
              <span class="tooltip-text">Supported formats: JPG, PNG, GIF, WebP<br>Maximum size: 5MB</span>
            </div>
          </div>
        </div>
        <p style="color: #666; margin: 10px 0 0 0; font-size: 14px;">Current Profile Picture</p>
        <div class="file-preview" id="filePreview" style="display: none;">
          <small style="color: #666;">New picture selected - will be applied when you save</small>
        </div>
      </div>

      <form id="profileForm" enctype="multipart/form-data">
        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" name="email" id="email" placeholder="Enter your email address" required />
        </div>

        <div class="form-group">
          <label for="password">New Password (leave empty to keep current)</label>
          <div class="password-section">
            <input type="password" id="password" name="password" placeholder="Enter new password" oninput="checkPasswordStrength(); togglePasswordConfirmation(); checkPasswordMatch()" />
            <div class="password-strength-container">
              <div class="password-strength-meter">
                <div id="password-strength-bar" class="password-strength-bar"></div>
              </div>
              <div id="password-strength-text" class="password-strength-text"></div>
            </div>
          </div>
          
          <div class="password-confirm-field">
            <label for="passwordConfirmField">Confirm New Password</label>
            <input type="password" id="passwordConfirmField" name="passwordConfirmField" placeholder="Re-enter new password" oninput="checkPasswordMatch()" />
            <div id="passwordMatchStatus" class="password-match-status" style="display: none;"></div>
          </div>
          
          <!-- Password Change Confirmation -->
          <div id="passwordConfirmation" class="password-confirmation">
            <label for="passwordConfirm">Confirm Password Change</label>
            <input type="text" id="passwordConfirm" name="passwordConfirm" placeholder="Type 'CONFIRM PASSWORD CHANGE' to proceed" oninput="checkConfirmation()" />
            <div id="confirmationStatus" class="confirmation-status" style="display: none;"></div>
            <div class="confirmation-hint">This extra step helps prevent accidental password changes</div>
          </div>
        </div>

        <!-- Hidden file input - will be triggered by the overlay -->
        <input type="file" name="profilePic" id="profilePic" class="file-input" accept="image/*" style="display: none;" />

        <button type="submit" id="submitBtn" class="submit-btn">
          <span id="btnText">Save Changes</span>
        </button>
      </form>

      <div id="result" class="result-message" style="display: none;"></div>
    </div>
  </div>

  <!-- Crop Modal -->
  <div id="cropModal" class="crop-modal">
    <div class="crop-container">
      <div class="crop-header">
        <h3>Adjust Profile Picture</h3>
      </div>
      <div class="crop-canvas-container">
        <canvas id="cropCanvas" class="crop-canvas"></canvas>
        <div id="cropOverlay" class="crop-overlay"></div>
      </div>
      <div class="crop-instructions">
        Drag to reposition • The square area will be your profile picture
      </div>
      <div class="crop-controls">
        <button id="cropCancel" class="crop-btn cancel">Cancel</button>
        <button id="cropApply" class="crop-btn apply">Apply Crop</button>
      </div>
    </div>
  </div>

  <script>
    let currentUser = null;

    // Load user data
    fetch('session.php')
      .then(res => res.json())
      .then(user => {
        if (!user) {
          window.location.href = 'login.html';
          return;
        }
        currentUser = user;
        document.getElementById('email').value = user.email;
        // Add cache-busting parameter to prevent loading old cached image
        const cacheBust = '?t=' + Date.now();
        document.getElementById('currentProfilePic').src = user.profile_picture + cacheBust;
      })
      .catch(error => {
        console.error('Error loading user data:', error);
        window.location.href = 'login.html';
      });

    // Form submission
    document.getElementById('profileForm').onsubmit = function(e) {
      e.preventDefault();

      const password = document.getElementById('password').value;
      const passwordConfirmField = document.getElementById('passwordConfirmField').value;
      const passwordConfirm = document.getElementById('passwordConfirm').value;

      // Check password confirmation if password is being changed
      if (password.trim() !== '') {
        // First check if passwords match
        if (password !== passwordConfirmField) {
          const resultDiv = document.getElementById('result');
          resultDiv.className = 'result-message error';
          resultDiv.textContent = 'Passwords do not match. Please make sure both password fields contain the same password.';
          resultDiv.style.display = 'block';
          document.getElementById('passwordConfirmField').focus();
          return;
        }

        // Then check the confirmation text
        const expectedText = 'CONFIRM PASSWORD CHANGE';
        if (passwordConfirm.toUpperCase().trim() !== expectedText) {
          const resultDiv = document.getElementById('result');
          resultDiv.className = 'result-message error';
          resultDiv.textContent = `Please type "${expectedText}" to confirm your password change.`;
          resultDiv.style.display = 'block';
          document.getElementById('passwordConfirm').focus();
          return;
        }
      }

      const submitBtn = document.getElementById('submitBtn');
      const btnText = document.getElementById('btnText');
      const originalText = btnText.textContent;

      // Show loading state
      submitBtn.disabled = true;
      btnText.innerHTML = '<div class="loading"></div>Updating...';

      const formData = new FormData(this);

      // Debug: Log what we're sending
      console.log('🔍 PROFILE UPLOAD DEBUG:');
      console.log('Form data entries:');
      for (let [key, value] of formData.entries()) {
        if (value instanceof File) {
          console.log(`  ${key}: File "${value.name}" (${value.size} bytes, type: ${value.type})`);
        } else {
          console.log(`  ${key}: "${value}"`);
        }
      }

      fetch('update_profile.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
          const resultDiv = document.getElementById('result');

          if (data.success) {
            resultDiv.className = 'result-message success';
            resultDiv.textContent = 'Profile updated successfully!';
            resultDiv.style.display = 'block';

            // Clear password fields after successful update
            document.getElementById('password').value = '';
            document.getElementById('passwordConfirmField').value = '';
            document.getElementById('passwordConfirm').value = '';
            checkPasswordStrength(); // Reset password strength display
            togglePasswordConfirmation(); // Hide confirmation boxes

            // Clear file input and preview
            document.getElementById('profilePic').value = '';
            document.getElementById('filePreview').style.display = 'none';
            document.getElementById('filePreview').innerHTML = '';

            // Update current profile picture display if a new one was uploaded
            const uploadedFile = formData.get('profilePic');
            if (uploadedFile && uploadedFile.name) {
              // Create a preview URL for the uploaded image and update immediately
              const reader = new FileReader();
              reader.onload = function(e) {
                document.getElementById('currentProfilePic').src = e.target.result;
              };
              reader.readAsDataURL(uploadedFile);

              // Also refresh from session data after a short delay to ensure server update
              setTimeout(() => {
                fetch('session.php')
                  .then(res => res.json())
                  .then(user => {
                    if (user && user.profile_picture) {
                      // Add cache-busting parameter
                      const cacheBust = '?t=' + Date.now();
                      document.getElementById('currentProfilePic').src = user.profile_picture + cacheBust;
                    }
                  })
                  .catch(error => {
                    console.log('Could not refresh profile picture:', error);
                  });
              }, 1000);
            }

            // Dispatch custom event to update header profile picture
            window.dispatchEvent(new CustomEvent('profileUpdated'));

            // Hide success message after 5 seconds
            setTimeout(() => {
              resultDiv.style.display = 'none';
            }, 5000);
          } else {
            resultDiv.className = 'result-message error';
            resultDiv.textContent = data.message || 'Update failed. Please try again.';
            resultDiv.style.display = 'block';
          }
        })
        .catch(error => {
          console.error('Error:', error);
          const resultDiv = document.getElementById('result');
          resultDiv.className = 'result-message error';
          resultDiv.textContent = 'Network error. Please try again.';
          resultDiv.style.display = 'block';
        })
        .finally(() => {
          // Reset button state
          submitBtn.disabled = false;
          btnText.textContent = originalText;
        });
    }

    // Password strength checker
    function checkPasswordStrength() {
      const password = document.getElementById("password").value;
      const bar = document.getElementById("password-strength-bar");
      const text = document.getElementById("password-strength-text");

      // Hide strength display when password is empty
      if (password === '') {
        bar.style.width = "0%";
        text.innerText = "";
        return;
      }

      let strength = 0;
      if (password.length >= 8) strength++;
      if (/[A-Z]/.test(password)) strength++;
      if (/[a-z]/.test(password)) strength++;
      if (/[0-9]/.test(password)) strength++;
      if (/[^A-Za-z0-9]/.test(password)) strength++;

      let strengthText = "";
      let strengthColor = "";

      switch (strength) {
        case 0:
        case 1:
          strengthText = "Very Weak";
          strengthColor = "red";
          break;
        case 2:
          strengthText = "Weak";
          strengthColor = "orange";
          break;
        case 3:
          strengthText = "Medium";
          strengthColor = "gold";
          break;
        case 4:
          strengthText = "Strong";
          strengthColor = "blue";
          break;
        case 5:
          strengthText = "Very Strong";
          strengthColor = "green";
          break;
      }

      bar.style.width = (strength * 20) + "%";
      bar.style.background = strengthColor;
      text.innerText = strengthText;
      text.style.color = strengthColor;
    }

    // Check if passwords match in real-time
    function checkPasswordMatch() {
      const password = document.getElementById('password').value;
      const confirmPassword = document.getElementById('passwordConfirmField').value;
      const confirmInput = document.getElementById('passwordConfirmField');
      const status = document.getElementById('passwordMatchStatus');
      
      if (confirmPassword === '') {
        confirmInput.classList.remove('correct', 'incorrect');
        status.style.display = 'none';
        return;
      }
      
      if (password === confirmPassword) {
        confirmInput.classList.remove('incorrect');
        confirmInput.classList.add('correct');
        status.textContent = '✓ Passwords match';
        status.className = 'password-match-status correct';
        status.style.display = 'block';
      } else {
        confirmInput.classList.remove('correct');
        confirmInput.classList.add('incorrect');
        status.textContent = '✗ Passwords do not match';
        status.className = 'password-match-status incorrect';
        status.style.display = 'block';
      }
    }

    // Toggle password confirmation box
    function togglePasswordConfirmation() {
      const password = document.getElementById('password').value;
      const confirmationBox = document.getElementById('passwordConfirmation');
      const confirmField = document.querySelector('.password-confirm-field');
      
      if (password.trim() !== '') {
        confirmField.style.display = 'block';
        confirmationBox.style.display = 'block';
      } else {
        confirmField.style.display = 'none';
        confirmationBox.style.display = 'none';
        document.getElementById('passwordConfirmField').value = '';
        document.getElementById('passwordConfirm').value = '';
        checkPasswordMatch(); // Reset password match status
        checkConfirmation(); // Reset confirmation status
      }
    }

    // Check confirmation text in real-time
    function checkConfirmation() {
      const input = document.getElementById('passwordConfirm');
      const status = document.getElementById('confirmationStatus');
      
      if (!input || !status) return; // Safety check
      
      const value = input.value.trim();
      const expected = 'CONFIRM PASSWORD CHANGE';
      
      if (value === '') {
        input.classList.remove('correct', 'incorrect');
        status.style.display = 'none';
        return;
      }
      
      if (value.toUpperCase() === expected) {
        input.classList.remove('incorrect');
        input.classList.add('correct');
        status.textContent = '✓ Confirmation text is correct';
        status.className = 'confirmation-status correct';
        status.style.display = 'block';
      } else {
        input.classList.remove('correct');
        input.classList.add('incorrect');
        status.textContent = '✗ Please type the exact confirmation text';
        status.className = 'confirmation-status incorrect';
        status.style.display = 'block';
      }
    }

    // Enhanced file upload functionality with cropping
    const fileInput = document.getElementById('profilePic');
    const filePreview = document.getElementById('filePreview');
    let selectedFile = null;
    let cropModal = null;
    let cropCanvas = null;
    let cropOverlay = null;
    let cropImage = null;
    let cropX = 0;
    let cropY = 0;
    let cropSize = 200;
    let imageScale = 1;
    let isDragging = false;
    let dragStartX = 0;
    let dragStartY = 0;

    // Initialize crop modal elements
    function initCropModal() {
      cropModal = document.getElementById('cropModal');
      cropCanvas = document.getElementById('cropCanvas');
      cropOverlay = document.getElementById('cropOverlay');

      // Event listeners
      document.getElementById('cropApply').addEventListener('click', applyCrop);
      document.getElementById('cropCancel').addEventListener('click', cancelCrop);

      // Close modal when clicking outside
      cropModal.addEventListener('click', function(e) {
        if (e.target === cropModal) {
          cancelCrop();
        }
      });

      // Close modal on escape key
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && cropModal.classList.contains('show')) {
          cancelCrop();
        }
      });

      // Mouse events for dragging
      cropCanvas.addEventListener('mousedown', startDrag);
      document.addEventListener('mousemove', drag);
      document.addEventListener('mouseup', endDrag);
    }

    // File input change handler
    fileInput.addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (file) {
        selectedFile = file;
        showCropModal(file);
      }
    });

    function showCropModal(file) {
      if (!cropModal) initCropModal();

      const reader = new FileReader();
      reader.onload = function(e) {
        cropImage = new Image();
        cropImage.onload = function() {
          setupCropCanvas();
        };
        cropImage.src = e.target.result;
      };
      reader.readAsDataURL(file);

      cropModal.classList.add('show');
    }

    function setupCropCanvas() {
      const canvas = cropCanvas;
      const ctx = canvas.getContext('2d');
      const container = canvas.parentElement;

      // Calculate scaling to fit canvas
      const maxWidth = 400;
      const maxHeight = 400;
      imageScale = Math.min(maxWidth / cropImage.width, maxHeight / cropImage.height);

      canvas.width = cropImage.width * imageScale;
      canvas.height = cropImage.height * imageScale;

      // Draw image
      ctx.drawImage(cropImage, 0, 0, canvas.width, canvas.height);

      // Initialize crop area (center square)
      cropSize = Math.min(canvas.width, canvas.height) * 0.8;
      cropX = (canvas.width - cropSize) / 2;
      cropY = (canvas.height - cropSize) / 2;

      updateCropOverlay();
    }

    function updateCropOverlay() {
      cropOverlay.style.width = cropSize + 'px';
      cropOverlay.style.height = cropSize + 'px';
      cropOverlay.style.left = cropX + 'px';
      cropOverlay.style.top = cropY + 'px';
    }

    function startDrag(e) {
      isDragging = true;
      dragStartX = e.clientX - cropX;
      dragStartY = e.clientY - cropY;
      cropCanvas.style.cursor = 'grabbing';
    }

    function drag(e) {
      if (!isDragging) return;

      const newX = e.clientX - dragStartX;
      const newY = e.clientY - dragStartY;

      // Constrain to canvas bounds
      cropX = Math.max(0, Math.min(newX, cropCanvas.width - cropSize));
      cropY = Math.max(0, Math.min(newY, cropCanvas.height - cropSize));

      updateCropOverlay();
    }

    function endDrag() {
      isDragging = false;
      cropCanvas.style.cursor = 'move';
    }

    function applyCrop() {
      const canvas = document.createElement('canvas');
      const ctx = canvas.getContext('2d');
      canvas.width = 200;
      canvas.height = 200;

      // Calculate source coordinates (accounting for scaling)
      const sourceX = cropX / imageScale;
      const sourceY = cropY / imageScale;
      const sourceSize = cropSize / imageScale;

      // Draw cropped area
      ctx.drawImage(
        cropImage,
        sourceX, sourceY, sourceSize, sourceSize, // source
        0, 0, 200, 200 // destination
      );

      // Convert to blob and create new file
      canvas.toBlob(function(blob) {
        const croppedFile = new File([blob], selectedFile.name, {
          type: selectedFile.type,
          lastModified: Date.now()
        });

        // Update file input with cropped image
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(croppedFile);
        fileInput.files = dataTransfer.files;

        // Update preview
        handleFileSelect(croppedFile);

        // Hide modal
        cropModal.classList.remove('show');
      }, selectedFile.type);
    }

    function cancelCrop() {
      cropModal.classList.remove('show');
      fileInput.value = '';
      selectedFile = null;
    }

    function handleFileSelect(file) {
      const preview = document.getElementById('filePreview');
      const currentPic = document.getElementById('currentProfilePic');
      
      if (!file) {
        preview.style.display = 'none';
        preview.innerHTML = '';
        // Reset to current profile picture if no file selected
        if (currentUser && currentUser.profile_picture) {
          const cacheBust = '?t=' + Date.now();
          currentPic.src = currentUser.profile_picture + cacheBust;
        } else {
          currentPic.src = '/uploads/default.png';
        }
        return;
      }

      // Validate file type
      if (!file.type.startsWith('image/')) {
        alert('Please select an image file.');
        fileInput.value = '';
        preview.style.display = 'none';
        preview.innerHTML = '';
        return;
      }

      // Validate file size (5MB max)
      if (file.size > 5 * 1024 * 1024) {
        alert('File size must be less than 5MB.');
        fileInput.value = '';
        preview.style.display = 'none';
        preview.innerHTML = '';
        return;
      }

      // Show image preview in both places
      const reader = new FileReader();
      reader.onload = function(e) {
        // Update main profile picture display immediately
        currentPic.src = e.target.result;
        
        // Show preview section
        preview.innerHTML = `
          <img src="${e.target.result}" alt="Selected image preview" />
          <br>
          <small style="color: #666;">New picture selected - will be applied when you save</small>
        `;
        preview.style.display = 'block';
      };
      reader.readAsDataURL(file);
    }
  </script>
</body>
</html>
