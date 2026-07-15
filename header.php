<?php
// header.php (markup only; no session_start, no header redirects)
$isLoggedIn = isset($_SESSION['user_id']);
$username = $_SESSION['facility'] ?? ($_SESSION['username'] ?? ($_SESSION['email'] ?? 'User'));
$role = $_SESSION['role'] ?? '';
$profilePicPath = '/uploads/default.png';
if ($isLoggedIn && isset($_SESSION['profile_picture']) && $_SESSION['profile_picture']) {
    $profilePicPath = '/uploads/' . $_SESSION['profile_picture'];
}
?>

<style>
  :root {
    --font-body: 'Inter', 'Open Sans', Arial, sans-serif;
    --font-heading: 'Poppins', 'Lato', 'Inter', 'Open Sans', Arial, sans-serif;
    --sidebar-expanded: 250px;
    --sidebar-collapsed: 74px;
    --brand: #B22222;
    --brand-hover: #FFD700;
    --brand-start: #B22222;
    --brand-end: #8F1B1B;
    --accent-navy: #1A237E;
  }

  body.has-side-menu {
    transition: padding-left 0.28s ease;
    padding-left: var(--sidebar-expanded);
  }

  body.has-side-menu.sidebar-collapsed {
    padding-left: var(--sidebar-collapsed);
  }

  .site-header {
    position: relative;
    z-index: 100;
  }

  .sidebar-toggle {
    position: fixed;
    top: 12px;
    left: 12px;
    width: 42px;
    height: 42px;
    border: none;
    border-radius: 8px;
    background: rgba(178, 34, 34, 0.92);
    color: #fff;
    font-size: 18px;
    cursor: pointer;
    z-index: 1202;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.25s ease;
  }

  .sidebar-toggle:hover {
    background: var(--brand-hover);
    color: var(--accent-navy);
  }

  .sidebar-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.45);
    opacity: 0;
    visibility: hidden;
    z-index: 1198;
    transition: opacity 0.25s ease, visibility 0.25s ease;
  }

  .sidebar-overlay.active {
    opacity: 1;
    visibility: visible;
  }

  .side-menu {
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    width: var(--sidebar-expanded);
    background: linear-gradient(180deg, var(--brand-start) 0%, var(--brand-end) 100%);
    color: #fff;
    box-shadow: 2px 0 8px rgba(0, 0, 0, 0.15);
    z-index: 1200;
    display: flex;
    flex-direction: column;
    transition: width 0.28s ease, transform 0.28s ease;
    overflow: hidden;
  }

  .side-menu.collapsed {
    width: var(--sidebar-collapsed);
  }

  .side-menu-header {
    min-height: 66px;
    padding: 14px 14px 14px 62px;
    display: flex;
    align-items: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.16);
  }

  .logo {
    color: #fff;
    text-decoration: none;
    font-family: var(--font-heading);
    font-size: 18px;
    font-weight: bold;
    white-space: nowrap;
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .logo-full {
    display: inline;
  }

  .logo-collapsed {
    display: none;
    font-size: 14px;
    letter-spacing: 0.2px;
  }

  .side-menu.collapsed .logo-full {
    display: none;
  }

  .side-menu.collapsed .logo-collapsed {
    display: inline;
  }

  .side-nav {
    flex: 1;
    padding: 12px 8px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    overflow-y: auto;
  }

  .menu-item {
    color: #fff;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 11px 12px;
    border-radius: 8px;
    transition: background 0.22s ease;
    font-size: 14px;
    white-space: nowrap;
  }

  .menu-item:hover {
    background: var(--brand-hover);
    color: var(--accent-navy);
  }

  .menu-item.active {
    background: var(--brand-hover);
    color: var(--accent-navy);
    box-shadow: inset 0 0 0 1px rgba(26, 35, 126, 0.32);
  }

  .menu-item.with-submenu {
    justify-content: space-between;
  }

  .menu-item .menu-item-main {
    display: inline-flex;
    align-items: center;
    gap: 12px;
  }

  .menu-item .menu-item-main i {
    width: 20px;
    text-align: center;
    font-size: 16px;
    flex-shrink: 0;
  }

  .menu-submenu-chevron {
    font-size: 12px;
    transition: transform 0.24s ease;
  }

  .menu-item.with-submenu.expanded .menu-submenu-chevron {
    transform: rotate(180deg);
  }

  .folder-submenu {
    margin: -2px 0 6px;
    padding-left: 10px;
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.28s ease;
  }

  .folder-submenu.expanded {
    max-height: 260px;
  }

  .folder-submenu-item {
    width: 100%;
    border: none;
    background: transparent;
    color: #fff;
    border-radius: 8px;
    padding: 9px 12px;
    margin: 3px 0;
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    text-align: left;
    font-size: 13px;
    transition: background 0.22s ease;
  }

  .folder-submenu-item:hover {
    background: var(--brand-hover);
    color: var(--accent-navy);
  }

  .folder-submenu-item.active {
    background: var(--brand-hover);
    color: var(--accent-navy);
    box-shadow: inset 0 0 0 1px rgba(26, 35, 126, 0.32);
  }

  .folder-submenu-item i {
    width: 16px;
    text-align: center;
  }

  .menu-group {
    border-radius: 10px;
    overflow: hidden;
    background: rgba(255, 255, 255, 0.08);
  }

  .menu-group-toggle {
    width: 100%;
    border: none;
    background: transparent;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    cursor: pointer;
    padding: 11px 12px;
    border-radius: 8px;
    font-size: 13px;
    font-family: var(--font-heading);
    font-weight: 600;
  }

  .menu-group-toggle:hover {
    background: var(--brand-hover);
    color: var(--accent-navy);
  }

  .menu-group-title {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    letter-spacing: 0.2px;
  }

  .menu-group-title i {
    width: 20px;
    text-align: center;
    font-size: 15px;
    flex-shrink: 0;
  }

  .menu-group-chevron {
    transition: transform 0.2s ease;
  }

  .menu-group.collapsed .menu-group-chevron {
    transform: rotate(-90deg);
  }

  .menu-group-links {
    display: grid;
    gap: 4px;
    padding: 0 6px 8px;
    transition: max-height 0.2s ease;
  }

  .menu-group.collapsed .menu-group-links {
    display: none;
  }

  .menu-item i {
    width: 20px;
    text-align: center;
    font-size: 16px;
    flex-shrink: 0;
  }

  .menu-label {
    opacity: 1;
    transition: opacity 0.18s ease;
  }

  .side-menu.collapsed .menu-label {
    opacity: 0;
    pointer-events: none;
    width: 0;
    overflow: hidden;
  }

  .side-menu.collapsed .menu-group-toggle {
    justify-content: center;
    padding-left: 10px;
    padding-right: 10px;
  }

  .side-menu.collapsed .menu-group-title span,
  .side-menu.collapsed .menu-group-chevron {
    display: none;
  }

  .side-menu.collapsed .menu-group-links {
    display: none;
  }

  .side-menu.collapsed .folder-submenu {
    display: none;
  }

  .side-menu.collapsed .menu-item {
    justify-content: center;
    padding-left: 10px;
    padding-right: 10px;
  }

  .side-footer {
    border-top: 1px solid rgba(255, 255, 255, 0.16);
    padding: 12px 8px;
    display: flex;
    flex-direction: column;
    gap: 6px;
  }

  .user-menu-wrap {
    position: relative;
  }

  .user-trigger {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #fff;
    cursor: pointer;
    padding: 10px 12px;
    border-radius: 8px;
    transition: background 0.22s ease;
  }

  .user-trigger:hover {
    background: var(--brand-hover);
    color: var(--accent-navy);
  }

  .user-profile-pic {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    object-fit: cover;
    border: 1px solid rgba(255, 255, 255, 0.5);
  }

  .user-name {
    font-weight: bold;
    max-width: 130px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .side-menu.collapsed .user-name {
    display: none;
  }

  .user-dropdown {
    display: none;
    position: absolute;
    left: calc(100% + 6px);
    bottom: 0;
    background: #fff;
    border: 1px solid #ccc;
    border-radius: 6px;
    min-width: 175px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.18);
    z-index: 1205;
    overflow: hidden;
  }

  .side-menu:not(.collapsed) .user-dropdown {
    left: 8px;
    right: 8px;
    bottom: calc(100% + 6px);
  }

  .user-dropdown a {
    display: block;
    padding: 10px;
    text-decoration: none;
    color: #333;
    font-size: 14px;
  }

  .user-dropdown a:hover {
    background: #f5f5f5;
  }

  .notification-badge {
    position: absolute;
    top: -6px;
    right: -6px;
    background: #ff4444;
    color: #fff;
    border-radius: 50%;
    width: 16px;
    height: 16px;
    font-size: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
  }

  .site-header .banner {
    width: 100%;
    height: 240px;
    background-image: url('uploads/KPHUBBANNER.jpg');
    background-size: contain;
    background-position: center;
    background-repeat: no-repeat;
    margin-top: 20px;
  }

  @media (max-width: 768px) {
    body.has-side-menu,
    body.has-side-menu.sidebar-collapsed {
      padding-left: 0;
    }

    .side-menu {
      transform: translateX(-100%);
      width: var(--sidebar-expanded);
    }

    .side-menu.mobile-open {
      transform: translateX(0);
    }

    .side-menu.collapsed {
      width: var(--sidebar-expanded);
    }

    .side-menu.collapsed .menu-label {
      opacity: 1;
      width: auto;
      overflow: visible;
    }

    .side-menu.collapsed .menu-item {
      justify-content: flex-start;
      padding-left: 12px;
      padding-right: 12px;
    }

    .side-menu.collapsed .logo {
      opacity: 1;
      pointer-events: auto;
    }

    .side-menu.collapsed .user-name {
      display: inline;
    }

    .side-menu:not(.collapsed) .user-dropdown,
    .user-dropdown {
      left: 8px;
      right: 8px;
      bottom: calc(100% + 6px);
      min-width: auto;
    }
  }
</style>

<header class="site-header">
  <button id="sidebarToggle" class="sidebar-toggle" type="button" aria-label="Toggle menu" aria-expanded="true">
    <i class="fas fa-bars"></i>
  </button>

  <div id="sideMenuOverlay" class="sidebar-overlay"></div>

  <aside id="sideMenu" class="side-menu">
    <div class="side-menu-header">
      <a href="index.php" class="logo">
        <span class="logo-full">Knowledge Product Hub</span>
        <span class="logo-collapsed">EV-LGRRC KP-HUB</span>
      </a>
    </div>

    <nav class="side-nav">
      <div class="menu-group" data-group="workspace">
        <button class="menu-group-toggle" type="button" onclick="toggleMenuGroup('workspace')" aria-expanded="true">
          <span class="menu-group-title">
            <i class="fas fa-layer-group"></i>
            <span>Workspace</span>
          </span>
          <i class="fas fa-chevron-down menu-group-chevron"></i>
        </button>
        <div class="menu-group-links">
          <a href="index.php" class="menu-item" data-menu-section="home">
            <i class="fas fa-home"></i>
            <span class="menu-label">Home</span>
          </a>

          <?php if ($isLoggedIn): ?>
            <button type="button" class="menu-item with-submenu" data-menu-section="folders" id="foldersMenuToggle" onclick="toggleFoldersSubmenu(event)">
              <span class="menu-item-main">
                <i class="fas fa-folder-open"></i>
                <span class="menu-label">Folders</span>
              </span>
              <i class="fas fa-chevron-down menu-submenu-chevron"></i>
            </button>
            <div id="foldersSubmenu" class="folder-submenu">
              <button type="button" class="folder-submenu-item" data-folder-name="Decision Support" onclick="navigateFolderFromSidebar(event, 'Decision Support')">
                <i class="fas fa-folder"></i><span>Decision Support</span>
              </button>
              <button type="button" class="folder-submenu-item" data-folder-name="Knowledge Sharing &amp; Networking" onclick="navigateFolderFromSidebar(event, 'Knowledge Sharing & Networking')">
                <i class="fas fa-folder"></i><span>Knowledge Sharing &amp; Networking</span>
              </button>
              <button type="button" class="folder-submenu-item" data-folder-name="Learning &amp; Development" onclick="navigateFolderFromSidebar(event, 'Learning & Development')">
                <i class="fas fa-folder"></i><span>Learning &amp; Development</span>
              </button>
              <button type="button" class="folder-submenu-item" data-folder-name="Strategic Planning &amp; Implementation" onclick="navigateFolderFromSidebar(event, 'Strategic Planning & Implementation')">
                <i class="fas fa-folder"></i><span>Strategic Planning &amp; Implementation</span>
              </button>
            </div>
            <a href="#newsfeed-tab" class="menu-item" data-menu-section="newsfeed" onclick="switchTab(event, 'newsfeed-tab')">
              <i class="fas fa-newspaper"></i>
              <span class="menu-label">Newsfeed</span>
            </a>
            <a href="facility_cards_manage.php" class="menu-item" data-menu-section="newsfeed-manage">
              <i class="fas fa-pen-to-square"></i>
              <span class="menu-label">Manage Facility Cards</span>
            </a>
            <?php if (strtolower($role) === 'admin' || strtolower($role) === 'head'): ?>
              <a href="#reports-tab" class="menu-item" data-menu-section="reports" onclick="switchTab(event, 'reports-tab')">
                <i class="fas fa-chart-column"></i>
                <span class="menu-label">Reports</span>
              </a>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>

      <div class="menu-group" data-group="resources">
        <button class="menu-group-toggle" type="button" onclick="toggleMenuGroup('resources')" aria-expanded="true">
          <span class="menu-group-title">
            <i class="fas fa-compass"></i>
            <span>Resources</span>
          </span>
          <i class="fas fa-chevron-down menu-group-chevron"></i>
        </button>
        <div class="menu-group-links">
          <a href="ev-lgrrc.php" class="menu-item">
            <i class="fas fa-building-columns"></i>
            <span class="menu-label">EV-LGRRC</span>
          </a>

          <a href="#" class="menu-item" onclick="showUserGuide(event)">
            <i class="fas fa-circle-question"></i>
            <span class="menu-label">Help</span>
          </a>

          <a href="https://www.facebook.com/DILGRegion8/" target="_blank" class="menu-item" title="Follow us on Facebook">
            <i class="fab fa-facebook-f"></i>
            <span class="menu-label">Facebook</span>
          </a>
        </div>
      </div>
    </nav>

    <div class="side-footer">
      <?php if ($isLoggedIn): ?>
        <a href="#" class="menu-item" onclick="toggleNotifications(); return false;" id="notification-bell" style="position: relative;">
          <i class="fas fa-bell"></i>
          <span class="menu-label">Notifications</span>
          <span id="notification-badge" class="notification-badge" style="display: none;">0</span>
        </a>

        <div id="user-menu" class="user-menu-wrap">
          <div class="user-trigger" onclick="toggleDropdown()">
            <img id="headerProfilePic" src="<?= $profilePicPath ?>" alt="Profile" class="user-profile-pic" />
            <span class="user-name"><?= htmlspecialchars($username) ?></span>
          </div>
          <div class="user-dropdown" id="dropdown-menu">
            <a href="index.php">Home</a>
            <?php if (strtolower($role) === 'admin'): ?>
              <a href="admin.php">Admin Panel</a>
            <?php endif; ?>
            <a href="profile.php">Edit My Profile</a>
            <a href="logout.php">Logout</a>
          </div>
        </div>
      <?php else: ?>
        <a href="login.html" class="menu-item">
          <i class="fas fa-right-to-bracket"></i>
          <span class="menu-label">Login</span>
        </a>
      <?php endif; ?>
    </div>
  </aside>

  <div class="banner" id="banner-placeholder" aria-hidden="true"></div>

  <div class="user-menu-container" style="display: none;">
    <!-- legacy container intentionally hidden -->
  </div>
</header>

<script>
  const sideMenu = document.getElementById('sideMenu');
  const sideMenuOverlay = document.getElementById('sideMenuOverlay');
  const sideMenuToggle = document.getElementById('sidebarToggle');

  function isMobileSidebar() {
    return window.matchMedia('(max-width: 768px)').matches;
  }

  function applySidebarState() {
    const mobile = isMobileSidebar();
    document.body.classList.add('has-side-menu');

    if (mobile) {
      document.body.classList.remove('sidebar-collapsed');
      sideMenu.classList.remove('collapsed');
      sideMenu.classList.remove('mobile-open');
      sideMenuOverlay.classList.remove('active');
      sideMenuToggle.setAttribute('aria-expanded', 'false');
      return;
    }

    const collapsed = localStorage.getItem('sidebarCollapsed') === '1';
    sideMenu.classList.toggle('collapsed', collapsed);
    document.body.classList.toggle('sidebar-collapsed', collapsed);
    sideMenu.classList.remove('mobile-open');
    sideMenuOverlay.classList.remove('active');
    sideMenuToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
  }

  function toggleSidebar() {
    if (isMobileSidebar()) {
      const open = sideMenu.classList.toggle('mobile-open');
      sideMenuOverlay.classList.toggle('active', open);
      sideMenuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      return;
    }

    const willCollapse = !sideMenu.classList.contains('collapsed');
    sideMenu.classList.toggle('collapsed', willCollapse);
    document.body.classList.toggle('sidebar-collapsed', willCollapse);
    localStorage.setItem('sidebarCollapsed', willCollapse ? '1' : '0');
    sideMenuToggle.setAttribute('aria-expanded', willCollapse ? 'false' : 'true');
  }

  function toggleMenuGroup(groupName) {
    const group = document.querySelector(`.menu-group[data-group="${groupName}"]`);
    if (!group) return;
    const collapsed = group.classList.toggle('collapsed');
    const toggle = group.querySelector('.menu-group-toggle');
    if (toggle) {
      toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    }
  }

  function setActiveMenuSection(section) {
    document.querySelectorAll('.menu-item[data-menu-section]').forEach((item) => {
      item.classList.toggle('active', item.getAttribute('data-menu-section') === section);
    });

    if (section !== 'folders') {
      setActiveFolderSubItem('');
    }
  }

  window.setSidebarActiveSection = setActiveMenuSection;

  function toggleFoldersSubmenu(event) {
    if (event) {
      event.preventDefault();
      event.stopPropagation();
    }

    const toggle = document.getElementById('foldersMenuToggle');
    const submenu = document.getElementById('foldersSubmenu');
    if (!toggle || !submenu) return;

    const expanded = submenu.classList.toggle('expanded');
    toggle.classList.toggle('expanded', expanded);
    setActiveMenuSection('folders');

    if (typeof window.switchTab === 'function') {
      window.switchTab(null, 'view-folder-tab');
    }
  }

  function expandFoldersSubmenu() {
    const toggle = document.getElementById('foldersMenuToggle');
    const submenu = document.getElementById('foldersSubmenu');
    if (!toggle || !submenu) return;
    submenu.classList.add('expanded');
    toggle.classList.add('expanded');
  }

  function setActiveFolderSubItem(folderName) {
    document.querySelectorAll('.folder-submenu-item').forEach((item) => {
      item.classList.toggle('active', folderName && item.getAttribute('data-folder-name') === folderName);
    });
    if (folderName) {
      expandFoldersSubmenu();
      setActiveMenuSection('folders');
    }
  }

  window.setSidebarActiveFolder = setActiveFolderSubItem;

  function navigateFolderFromSidebar(event, folderName) {
    if (event) {
      event.preventDefault();
      event.stopPropagation();
    }

    setActiveFolderSubItem(folderName);

    if (typeof window.openMainFolderByName === 'function') {
      window.openMainFolderByName(folderName);
      return;
    }

    window.location.href = `index.php?tab=folders&mainFolder=${encodeURIComponent(folderName)}`;
  }

  sideMenuToggle.addEventListener('click', toggleSidebar);
  sideMenuOverlay.addEventListener('click', function () {
    sideMenu.classList.remove('mobile-open');
    sideMenuOverlay.classList.remove('active');
    sideMenuToggle.setAttribute('aria-expanded', 'false');
  });

  window.addEventListener('resize', applySidebarState);
  applySidebarState();

  function toggleDropdown() {
    const menu = document.getElementById('dropdown-menu');
    menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
  }

  document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('dropdown-menu');
    const menu = document.getElementById('user-menu');
    if (menu && dropdown && !menu.contains(event.target)) {
      dropdown.style.display = 'none';
    }
  });

  function toggleNotifications() {
    const dropdown = document.getElementById('notification-dropdown');
    if (!dropdown) return;

    const isVisible = dropdown.style.display === 'block';

    if (isVisible) {
      dropdown.style.display = 'none';
    } else {
      dropdown.style.display = 'block';
      if (typeof displayNotifications === 'function') {
        displayNotifications();
      }
    }
  }

  function updateHeaderProfilePic() {
    fetch('session.php')
      .then(res => res.json())
      .then(user => {
        if (user && user.profile_picture) {
          const headerPic = document.getElementById('headerProfilePic');
          if (headerPic) {
            headerPic.src = user.profile_picture;
          }
        }
      })
      .catch(error => {
        console.log('Could not load session data for header profile pic:', error);
      });
  }

  document.addEventListener('DOMContentLoaded', function() {
    updateHeaderProfilePic();
    applySidebarState();
  });

  window.addEventListener('profileUpdated', function() {
    setTimeout(updateHeaderProfilePic, 500);
  });
</script>
