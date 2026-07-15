<?php
require_once 'page_security.php';

// Initialize page security
PageSecurity::initPageSecurity();

// Check if user is logged in (optional for public newsfeed)
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['role'] ?? null;
$userId = $_SESSION['user_id'] ?? null;
$newsfeedEditMode = isset($_GET['newsfeed_mode']) && $_GET['newsfeed_mode'] === 'manage';

// Log visits
if ($isLoggedIn) {
    // Increment authenticated visit counter
    require_once 'visit_counter.php';
  incrementPageVisit($newsfeedEditMode ? 'newsfeed_manage' : 'index');
} else {
    // Log public visit
    require_once 'public_visit_logger.php';
    if (checkPublicVisitsTable()) {
        logPublicVisit('newsfeed');
    }
}

// Get total visit count (combines logged-in and public visits)
require_once 'visit_counter.php';
$loggedInVisits = getPageVisits('index');

// Get public visits count
require_once 'public_visit_logger.php';
$publicStats = getPublicVisitStats('newsfeed', 365); // Get all-time public visits
$publicVisits = $publicStats['total_visits'] ?? 0;

// Combine both counts
$visitCount = $loggedInVisits + $publicVisits;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <title>Knowledge Product Hub</title>

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!-- CKEditor 5 Classic Build -->
  <script src="https://cdn.ckeditor.com/ckeditor5/40.2.0/classic/ckeditor.js"></script>

  <style>
    :root {
      --brand: #4CAF50;
      --brand-hover: #45a049;
      --ink: #333;
      --card: #fff;
      --muted: #f8f9fa;
      --border: #ddd;
      --shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    * { box-sizing: border-box; }

    body {
      font-family: Arial, sans-serif;
      margin: 20px;
      color: var(--ink);
      background: linear-gradient(135deg, #FF894F 0%, #FEFFC4 100%);
      background-attachment: fixed;
    }
    h3 { margin: 10px 0; color: var(--brand); }

    /* Container widths */
    .wrap {
      width: 95%;
      max-width: 1100px;
      margin: 0 auto;
    }

    /* Tabs */
    .tabs {
      display: flex;
      margin: 50px auto 0;
      background: var(--card);
      border-radius: 5px;
      overflow: hidden;
      box-shadow: var(--shadow);
    }
    .tab {
      flex: 1;
      padding: 10px;
      text-align: center;
      cursor: pointer;
      background: var(--muted);
      color: #555;
      border-right: 1px solid var(--border);
      transition: background 0.3s, color 0.3s;
    }
    .tab:last-child { border-right: 0; }
    .tab:hover,
    .tab.active { background: var(--brand); color: #fff; }
    .tab:focus-visible {
      outline: 2px solid var(--brand);
      outline-offset: -2px;
    }

    /* Tab content */
    .tab-content {
      display: none;
      padding: 20px;
      background: var(--card);
      border-radius: 10px 10px 10px 10px;
      box-shadow: var(--shadow);
      margin: 20px 80px;
    }
    .tab-content.active { display: block; }
    .tab-content .wrap { padding: 0; }

    /* Inputs / buttons */
    select, input, button {
      padding: 8px;
      margin: 5px 0;
      border-radius: 5px;
      border: 1px solid var(--border);
      font-size: 14px;
    }
    button {
      background: var(--brand);
      color: #fff;
      border: none;
      cursor: pointer;
      transition: transform 0.2s, background 0.3s;
    }
    button:hover { background: var(--brand-hover); }
    button:active { transform: translateY(1px); }
    button:focus-visible { outline: 2px solid var(--brand); outline-offset: 2px; }

    /* Folder grid */
    .folder-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
      gap: 16px;
      align-items: start;
    }

    .folder-card {
      height: 140px;
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 8px;
      box-shadow: 0 1px 4px rgba(0,0,0,0.08);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      padding: 10px;
      cursor: pointer;
      transition: transform 0.2s, box-shadow 0.2s;
      position: relative;
    }
    .folder-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    }
    .folder-card a { text-decoration: none; }

    .folder-icon {
      font-size: 38px;
      color: var(--brand);
    }
    .folder-name {
      margin-top: 8px;
      font-size: 12px;
      color: var(--ink);
      word-break: break-word;
    }

    .copy-link-btn {
      position: absolute;
      top: 8px;
      right: 8px;
      background: rgba(255, 255, 255, 0.9);
      border: 1px solid #ddd;
      border-radius: 4px;
      width: 24px;
      height: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      opacity: 0;
      transition: opacity 0.2s, background 0.2s;
      z-index: 10;
    }

    .folder-card:hover .copy-link-btn {
      opacity: 1;
    }

    .copy-link-btn:hover {
      background: rgba(76, 175, 80, 0.1);
      border-color: var(--brand);
    }

    .copy-link-btn i {
      font-size: 12px;
      color: #666;
    }

    .copy-link-btn:hover i {
      color: var(--brand);
    }

    .post-card:hover .copy-link-btn {
      opacity: 1;
    }

    .post-actions .copy-link-btn {
      position: static;
      opacity: 0;
      margin-left: 8px;
    }

    .post-card:hover .post-actions .copy-link-btn {
      opacity: 1;
    }

    .share-to-newsfeed-btn {
      position: absolute;
      top: 8px;
      right: 40px;
      background: rgba(255, 255, 255, 0.9);
      border: 1px solid #ddd;
      border-radius: 4px;
      width: 24px;
      height: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      opacity: 0;
      transition: opacity 0.2s, background 0.2s;
      z-index: 10;
    }

    .folder-card:hover .share-to-newsfeed-btn {
      opacity: 1;
    }

    .share-to-newsfeed-btn:hover {
      background: rgba(33, 150, 243, 0.1);
      border-color: #2196F3;
    }

    .share-to-newsfeed-btn i {
      font-size: 12px;
      color: #666;
    }

    .share-to-newsfeed-btn:hover i {
      color: #2196F3;
    }

    .delete-file-btn:hover {
      background: rgba(244, 67, 54, 0.1);
      border-color: #f44336;
    }

    .delete-file-btn i {
      font-size: 12px;
      color: #666;
    }

    .delete-file-btn:hover i {
      color: #f44336;
    }

    .empty {
      padding: 16px;
      text-align: center;
      color: #666;
      border: 1px dashed var(--border);
      border-radius: 8px;
      background: #fafafa;
    }

    /* View Toggle Styles */
    .view-toggle {
      margin-bottom: 15px;
    }

    .view-btn {
      padding: 8px 16px;
      border: 1px solid #ddd;
      background: white;
      color: #666;
      border-radius: 6px;
      cursor: pointer;
      transition: all 0.2s ease;
      font-size: 14px;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .view-btn:hover {
      background: #f8f9fa;
      border-color: #bbb;
    }

    .view-btn.active {
      background: var(--brand);
      color: white;
      border-color: var(--brand);
    }

    .view-btn.active:hover {
      background: var(--brand-hover);
      border-color: var(--brand-hover);
    }

    /* List View Styles */
    .folder-list {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .folder-list .folder-card {
      height: auto;
      min-height: 60px;
      display: flex;
      flex-direction: row;
      align-items: center;
      padding: 12px;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .folder-list .folder-card .folder-icon {
      font-size: 24px;
      margin-right: 12px;
      flex-shrink: 0;
    }

    .folder-list .folder-card .folder-name {
      flex: 1;
      margin: 0;
      font-size: 14px;
      word-break: break-word;
      text-align: left;
    }

    .folder-list .folder-card .copy-link-btn,
    .folder-list .folder-card .share-to-newsfeed-btn,
    .folder-list .folder-card .delete-file-btn {
      position: static;
      opacity: 0;
      margin-left: 8px;
      flex-shrink: 0;
    }

    .folder-list .folder-card:hover .copy-link-btn,
    .folder-list .folder-card:hover .share-to-newsfeed-btn,
    .folder-list .folder-card:hover .delete-file-btn {
      opacity: 1;
    }

    /* Upload progress overlay */
    #uploadProgressOverlay {
      display: none;
      position: fixed;
      inset: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.6);
      z-index: 9999;
      justify-content: center;
      align-items: center;
    }

    /* Highlight animation for new files */
    .highlight { animation: fadeHighlight 2s ease; }
    @keyframes fadeHighlight {
      from { background: #c8f7c5; }
      to { background: transparent; }
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    /* Newsfeed Styles */
    .post-card {
      background: var(--card);
      border-radius: 8px;
      padding: 20px;
      margin-bottom: 20px;
      box-shadow: var(--shadow);
      border-left: 4px solid var(--brand);
      transition: all 0.3s ease;
    }

    .post-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(0,0,0,0.15);
      border-left-color: var(--brand-hover);
    }

    .post-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 15px;
      gap: 15px;
    }

    .post-actions {
      display: flex;
      gap: 8px;
      flex-shrink: 0;
    }

    .action-btn {
      background: none;
      border: none;
      padding: 6px 8px;
      border-radius: 6px;
      cursor: pointer;
      transition: all 0.2s ease;
      color: #666;
      font-size: 14px;
    }

    .action-btn:hover {
      background: #f0f0f0;
    }

    .edit-btn:hover {
      color: #4CAF50;
      background: #e8f5e8;
    }

    .delete-btn:hover {
      color: #f44336;
      background: #ffeaea;
    }

    .post-links {
      background: #f8f9fa;
      border: 1px solid #e9ecef;
      border-radius: 8px;
      padding: 15px;
      margin: 15px 0;
    }

    .links-header {
      font-weight: bold;
      color: var(--brand);
      margin-bottom: 10px;
      font-size: 14px;
    }

    .post-link {
      margin: 8px 0;
    }

    .post-link a {
      color: #007bff;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 6px 10px;
      border-radius: 4px;
      transition: background-color 0.2s ease;
    }

    .post-link a:hover {
      background-color: #e3f2fd;
      text-decoration: none;
    }

    /* Links toggle section styles */
    .links-toggle-section button {
      transition: all 0.2s ease;
    }

    .links-toggle-section button:hover {
      color: #4CAF50 !important;
    }

    .links-toggle-section #linksChevron {
      transition: transform 0.2s ease;
    }

    .post-author {
      font-weight: bold;
      color: var(--brand);
      margin-right: 10px;
    }

    .post-date {
      color: #666;
      font-size: 12px;
    }

    .post-content {
      margin-bottom: 15px;
      line-height: 1.6;
      word-wrap: break-word;
    }
    
    .post-content p {
      margin: 0 0 10px 0;
    }
    
    .post-content p:last-child {
      margin-bottom: 0;
    }
    
    .post-content ul, .post-content ol {
      margin: 10px 0;
      padding-left: 20px;
    }
    
    .post-content li {
      margin-bottom: 5px;
    }
    
    .post-content a {
      color: var(--brand);
      text-decoration: none;
    }
    
    .post-content a:hover {
      text-decoration: underline;
    }
    
    .post-content strong, .post-content b {
      font-weight: bold;
    }
    
    .post-content em, .post-content i {
      font-style: italic;
    }
    
    .post-content u {
      text-decoration: underline;
    }
    
    .post-content s, .post-content strike {
      text-decoration: line-through;
    }

    .newsfeed-sections-card {
      background: #f9fbff;
      border: 1px solid #dfe7f5;
      border-radius: 10px;
      padding: 18px;
      margin-bottom: 20px;
      box-shadow: var(--shadow);
    }

    .newsfeed-sections-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
      margin-bottom: 14px;
    }

    .newsfeed-section-form,
    .newsfeed-link-form,
    .newsfeed-section-controls {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      align-items: center;
    }

    .newsfeed-section-form {
      margin-bottom: 14px;
    }

    .newsfeed-section-form input,
    .newsfeed-section-controls input,
    .newsfeed-link-form input {
      flex: 1;
      min-width: 180px;
    }

    .newsfeed-section-card {
      border: 1px solid #e1e7f0;
      border-radius: 10px;
      overflow: hidden;
      margin-bottom: 12px;
      background: #fff;
    }

    .newsfeed-section-toggle {
      width: 100%;
      border: 0;
      background: #eef4ff;
      color: var(--ink);
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
      padding: 14px 16px;
      cursor: pointer;
      font-weight: bold;
      text-align: left;
    }

    .newsfeed-section-toggle:hover {
      background: #e3edff;
      color: var(--brand);
    }

    .newsfeed-section-body {
      display: none;
      padding: 14px 16px 16px;
      border-top: 1px solid #e1e7f0;
      background: #fff;
    }

    .newsfeed-section-card.open .newsfeed-section-body {
      display: block;
    }

    .newsfeed-section-toggle-label {
      display: flex;
      align-items: center;
      gap: 8px;
      min-width: 0;
      flex: 1;
    }

    .newsfeed-section-toggle-title {
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .newsfeed-section-toggle-hint {
      flex: 0 0 auto;
      padding: 3px 10px;
      border-radius: 999px;
      background: rgba(38, 99, 235, 0.12);
      color: #1d4ed8;
      font-size: 12px;
      font-weight: 700;
      letter-spacing: 0.02em;
      text-transform: uppercase;
    }

    .newsfeed-section-toggle-chevron {
      flex: 0 0 auto;
      color: #1d4ed8;
      font-size: 18px;
      line-height: 1;
    }

    .newsfeed-section-links {
      margin: 14px 0;
      display: grid;
      gap: 10px;
    }

    .newsfeed-section-link {
      border: 1px solid #e7ecf4;
      border-radius: 8px;
      padding: 12px;
      background: #fafcff;
    }

    .newsfeed-section-link-header {
      display: flex;
      justify-content: space-between;
      gap: 10px;
      align-items: center;
      margin-bottom: 10px;
    }

    .newsfeed-section-link-meta a {
      color: var(--brand);
      text-decoration: none;
      word-break: break-word;
    }

    .newsfeed-section-link-meta a:hover {
      text-decoration: underline;
    }

    .newsfeed-section-actions {
      display: flex;
      gap: 6px;
      flex-wrap: wrap;
      align-items: center;
    }

    .newsfeed-section-actions button {
      background: #f2f6fb;
      color: #444;
      border: 1px solid #d8e0eb;
      padding: 6px 10px;
      border-radius: 6px;
      cursor: pointer;
    }

    .newsfeed-section-actions button:hover {
      background: #e7eef8;
    }

    .facility-card-grid {
      display: grid;
      grid-template-columns: 1fr;
      gap: 18px;
      margin-top: 18px;
    }

    .facility-card {
      background: #fff;
      border: 1px solid #dfe7f5;
      border-radius: 14px;
      overflow: hidden;
      box-shadow: var(--shadow);
    }

    .facility-card-header {
      padding: 16px 18px;
      background: linear-gradient(135deg, #eef4ff 0%, #f8fbff 100%);
      border-bottom: 1px solid #dfe7f5;
      display: flex;
      justify-content: space-between;
      gap: 12px;
      align-items: center;
    }

    .facility-card-title {
      margin: 0;
      color: var(--brand);
      font-size: 18px;
    }

    .facility-card-subtitle {
      margin: 4px 0 0;
      color: #64748b;
      font-size: 13px;
    }

    .facility-card-body {
      padding: 16px 18px 18px;
    }

    .facility-empty {
      padding: 14px;
      border: 1px dashed #d7deeb;
      border-radius: 10px;
      color: #64748b;
      background: #fafcff;
    }

    .post-folder {
      background: var(--muted);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 20px;
      margin: 15px 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
    }

    .post-folder .folder-info {
      width: 100%;
    }

    .post-folder .folder-link {
      transition: color 0.2s;
    }

    .post-folder .folder-link:hover {
      color: var(--brand-hover);
      text-decoration: underline;
    }

    .post-file i {
      margin-right: 10px;
      font-size: 20px;
      color: var(--brand);
    }

    .post-file-info {
      flex: 1;
    }

    .post-file-name {
      font-weight: bold;
      margin-bottom: 3px;
    }

    .post-file-size {
      font-size: 12px;
      color: #666;
    }

    .post-file.google-drive-file {
      background: linear-gradient(135deg, #f8f9fa 0%, #e3f2fd 100%);
      border: none;
      margin-left: 15px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      padding: 10px;
    }

    .post-file.google-drive-file .post-file-info .post-file-size {
      color: #4285f4;
      font-weight: 500;
    }

    .drive-preview-link {
      text-decoration: none;
    }

    .drive-preview-link:hover {
      text-decoration: none;
    }

    .embedded-video-iframe {
      width: 100%;
      height: 400px;
      border: none;
      border-radius: 8px;
    }

    /* Embedded Media Styles */
    .embedded-media {
      margin: 15px 0;
      border-radius: 8px;
      overflow: hidden;
      border: 1px solid #eee;
    }

    .embedded-image {
      width: 100%;
      max-height: 400px;
      object-fit: contain;
      background: #f8f9fa;
      display: block;
    }

    .embedded-video {
      width: 100%;
      max-height: 400px;
      background: #000;
    }

    .embedded-audio {
      width: 100%;
    }

    .embedded-pdf {
      width: 100%;
      height: 500px;
      border: none;
    }

    .embedded-folder {
      width: 100%;
      height: 300px;
      border: 1px solid #ddd;
      border-radius: 4px;
    }

    .post-folder-simple {
      display: flex;
      align-items: center;
      padding: 15px;
      background: #f8f9fa;
      border-radius: 8px;
      border: 1px solid #e9ecef;
      margin: 10px 0;
    }

    .folder-info-simple {
      flex: 1;
    }

    .folder-link-simple:hover {
      text-decoration: underline !important;
    }

    .media-caption {
      background: #f8f9fa;
      padding: 10px;
      font-size: 12px;
      color: #666;
      border-top: 1px solid #eee;
    }

    .download-link {
      color: var(--brand);
      text-decoration: none;
      margin-left: 10px;
    }

    .download-link:hover {
      text-decoration: underline;
    }

    .post-reactions {
      display: flex;
      gap: 10px;
      padding-top: 15px;
      border-top: 1px solid #eee;
    }

    .reaction-btn {
      display: flex;
      align-items: center;
      gap: 5px;
      padding: 5px 10px;
      border: 1px solid #ddd;
      border-radius: 15px;
      background: white;
      cursor: pointer;
      transition: all 0.2s;
      font-size: 12px;
    }

    .reaction-btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .reaction-btn.active {
      background: var(--brand);
      color: white;
      border-color: var(--brand);
    }

    .reaction-count {
      font-weight: bold;
    }

    /* Post Creation Styles */
    .post-create-card {
      background: var(--card);
      border-radius: 8px;
      padding: 20px;
      margin-bottom: 25px;
      box-shadow: var(--shadow);
      border: 1px solid #e8f5e8;
    }

    .post-textarea {
      width: 100%;
      min-height: 120px;
      padding: 12px;
      border: 1px solid #ddd;
      border-radius: 6px;
      resize: vertical;
      font-family: inherit;
      font-size: 14px;
      margin-bottom: 15px;
      box-sizing: border-box;
      display: none; /* Hide the original textarea when CKEditor is active */
    }

    /* CKEditor styling */
    .ck-editor__editable {
      min-height: 120px;
      border-radius: 6px;
      border: 1px solid #ddd;
    }

    .ck.ck-toolbar {
      border: 1px solid #ddd;
      border-bottom: none;
      border-radius: 6px 6px 0 0;
      background: #f8f9fa;
    }

    .ck.ck-editor__main > .ck-editor__editable {
      border-radius: 0 0 6px 6px;
      border-top: none;
    }

    .post-actions {
      display: flex;
      align-items: center;
      gap: 15px;
      flex-wrap: wrap;
    }

    .file-attach-btn {
      color: var(--brand);
      cursor: pointer;
      font-size: 14px;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .file-attach-btn:hover {
      text-decoration: underline;
    }

    .selected-file {
      font-size: 12px;
      color: #666;
      font-style: italic;
    }

    .post-submit-btn {
      background: var(--brand);
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 5px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 5px;
      margin-left: auto;
    }

    .post-submit-btn:hover {
      background: var(--brand-hover);
    }

    /* New Post Indicator Styles */
    .new-post-indicator {
      background: linear-gradient(135deg, #ff6b6b, #ee5a24);
      color: white;
      font-size: 10px;
      font-weight: bold;
      padding: 2px 6px;
      border-radius: 10px;
      margin-left: 8px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      box-shadow: 0 2px 4px rgba(255, 107, 107, 0.3);
      animation: pulse 2s infinite;
    }

    .new-post {
      border-left: 4px solid #ff6b6b !important;
      box-shadow: 0 2px 15px rgba(255, 107, 107, 0.1) !important;
    }

    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.7; }
    }

    /* Notification Styles */
    .notification-badge {
      position: absolute;
      top: -8px;
      right: -8px;
      background: #ff4444;
      color: white;
      border-radius: 50%;
      width: 18px;
      height: 18px;
      font-size: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
    }

    .notification-dropdown {
      position: fixed !important;
      top: 60px !important;
      right: 20px !important;
      width: 350px;
      max-height: 400px;
      background: white;
      border: 1px solid #ddd;
      border-radius: 8px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      z-index: 1000;
      overflow: hidden;
    }

    .notification-header {
      padding: 15px;
      background: var(--muted);
      border-bottom: 1px solid #eee;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .notification-header h4 {
      margin: 0;
      font-size: 16px;
    }

    .mark-read-btn {
      color: var(--brand);
      font-size: 12px;
      cursor: pointer;
    }

    .mark-read-btn:hover {
      text-decoration: underline;
    }

    .notification-list {
      max-height: 300px;
      overflow-y: auto;
    }

    .notification-item {
      padding: 12px 15px;
      border-bottom: 1px solid #f0f0f0;
      cursor: pointer;
    }

    .notification-item:hover {
      background: #f8f9fa;
    }

    .notification-item.unread {
      background: #f0f8f0;
      border-left: 3px solid var(--brand);
    }

    .notification-message {
      font-size: 13px;
      margin-bottom: 5px;
      line-height: 1.4;
    }

    .notification-time {
      font-size: 11px;
      color: #666;
    }

    /* Pagination Styles */
    .pagination-btn {
      background: var(--brand);
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 5px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      margin: 0 5px;
      transition: background 0.2s;
    }

    .pagination-btn:hover:not(:disabled) {
      background: var(--brand-hover);
    }

    .pagination-btn:disabled {
      background: #ccc;
      cursor: not-allowed;
      opacity: 0.6;
    }

    #page-info {
      color: var(--ink);
      font-size: 14px;
    }

    /* SweetAlert2 Image Modal Styles */
    .swal-large-image {
      max-width: 90vw !important;
      max-height: 80vh !important;
      object-fit: contain !important;
    }

    /* Download Counter Styles */
    .download-counter {
      display: inline-flex;
      align-items: center;
      gap: 3px;
      font-size: 11px;
      color: #888;
      margin-left: 10px;
      opacity: 0.7;
    }

    .download-counter i {
      font-size: 10px;
    }

    .download-counter:hover {
      opacity: 1;
    }

    /* Breadcrumb Navigation Styles */
    .breadcrumb {
      background: none;
      margin: 0;
      padding: 0;
      border-radius: 0;
      display: flex;
      flex-wrap: nowrap;
      align-items: center;
    }

    .breadcrumb-item {
      display: inline;
    }

    .breadcrumb-item + .breadcrumb-item::before {
      content: "/";
      color: #6c757d;
      padding: 0 8px;
    }

    .breadcrumb-item a {
      color: #007bff;
      text-decoration: none;
      transition: color 0.2s ease;
    }

    .breadcrumb-item a:hover {
      color: #0056b3;
      text-decoration: underline;
    }

    .breadcrumb-item.active {
      color: #6c757d;
      font-weight: 500;
    }

    #breadcrumb-nav {
      margin-bottom: 15px;
      padding: 10px 15px;
      background: #f8f9fa;
      border-radius: 6px;
      border: 1px solid #e9ecef;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    /* Global loading overlay for folder operations */
    .folder-operation-loading {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.3);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 8888;
      backdrop-filter: blur(2px);
    }

    .folder-operation-loading .loading-content {
      background: white;
      padding: 20px;
      border-radius: 10px;
      text-align: center;
      box-shadow: 0 4px 15px rgba(0,0,0,0.2);
      min-width: 200px;
    }

    /* Global loading overlay for folder operations */
    .folder-operation-loading {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.3);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 8888;
      backdrop-filter: blur(2px);
    }

    .folder-operation-loading .loading-content {
      background: white;
      padding: 20px;
      border-radius: 10px;
      text-align: center;
      box-shadow: 0 4px 15px rgba(0,0,0,0.2);
      min-width: 200px;
    }

    .folder-operation-loading .spinner {
      width: 40px;
      height: 40px;
      border: 4px solid #f3f3f3;
      border-top: 4px solid var(--brand);
      border-radius: 50%;
      animation: spin 1s linear infinite;
      margin: 0 auto 15px;
    }

    /* Introduction Section Styles */
    .introduction-section {
      background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      padding: 20px 0;
      margin: 20px 80px;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }

    .intro-content {
      text-align: center;
      max-width: 1200px;
      margin: 0 auto 0 20px;
      padding-right: 20px;
    }

    .intro-content h1 {
      color: var(--brand);
      font-size: 2.5rem;
      margin-bottom: 10px;
      font-weight: 700;
    }

    .intro-subtitle {
      color: #666;
      font-size: 1rem;
      margin-bottom: 40px;
      font-weight: 300;
    }

    .intro-features {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 30px;
      margin-bottom: 40px;
    }

    .feature-item {
      background: white;
      padding: 30px 20px;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.08);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .feature-item:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }

    .feature-item i {
      font-size: 2.5rem;
      color: var(--brand);
      margin-bottom: 15px;
      display: block;
    }

    .feature-item h3 {
      color: var(--ink);
      margin-bottom: 10px;
      font-size: 1.3rem;
    }

    .feature-item p {
      color: #666;
      line-height: 1.6;
      margin: 0;
    }

    .intro-stats {
      display: flex;
      justify-content: center;
      gap: 60px;
      flex-wrap: wrap;
    }

    .stat-item {
      text-align: center;
    }

    .stat-number {
      display: block;
      font-size: 2rem;
      font-weight: bold;
      color: var(--brand);
      margin-bottom: 5px;
    }

    .stat-label {
      color: #666;
      font-size: 0.9rem;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    /* Responsive Design for Introduction */
    @media (max-width: 768px) {
      .introduction-section {
        padding: 15px 0;
        margin: 15px 10px;
      }

      .intro-content {
        margin: 0 auto 0 10px;
        padding-right: 10px;
      }

      .intro-content h1 {
        font-size: 2rem;
      }

      .intro-subtitle {
        font-size: 1rem;
      }

      .intro-features {
        grid-template-columns: 1fr;
        gap: 20px;
      }

      .intro-stats {
        gap: 30px;
      }

      .stat-number {
        font-size: 1.5rem;
      }
    }

    @media (max-width: 480px) {
      .introduction-section {
        margin: 10px 5px;
      }

      .intro-content {
        margin: 0 auto 0 5px;
        padding-right: 5px;
      }
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

    /* Media Slider Styles */
    .media-slider-container {
      position: relative;
      margin: 15px 0;
      border-radius: 8px;
      overflow: hidden;
      border: 1px solid #eee;
    }

    .media-slider {
      display: flex;
      transition: transform 0.3s ease;
    }

    .slider-slide {
      flex: 0 0 100%;
      display: none;
    }

    .slider-slide.active {
      display: block;
    }

    .slider-nav {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      background: rgba(0, 0, 0, 0.5);
      color: white;
      border: none;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 16px;
      transition: background 0.3s ease;
      z-index: 10;
    }

    .slider-nav:hover {
      background: rgba(0, 0, 0, 0.8);
    }

    .slider-prev {
      left: 10px;
    }

    .slider-next {
      right: 10px;
    }

    .slider-thumbnails {
      display: flex;
      justify-content: center;
      gap: 8px;
      padding: 10px;
      background: #f8f9fa;
      border-top: 1px solid #eee;
      overflow-x: auto;
    }

    .slider-thumbnail {
      flex-shrink: 0;
      width: 60px;
      height: 45px;
      border-radius: 4px;
      overflow: hidden;
      cursor: pointer;
      position: relative;
      border: 2px solid transparent;
      transition: all 0.3s ease;
      opacity: 0.7;
      background: #f0f0f0;
    }

    .slider-thumbnail:hover {
      opacity: 0.9;
      transform: scale(1.05);
    }

    .slider-thumbnail.active {
      border-color: var(--brand);
      opacity: 1;
      box-shadow: 0 0 8px rgba(76, 175, 80, 0.4);
    }

    .slider-thumbnail img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .thumbnail-overlay {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.3);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 14px;
      opacity: 1;
      transition: opacity 0.3s ease;
    }

    .slider-thumbnail:hover .thumbnail-overlay {
      opacity: 0.8;
    }

    .slider-thumbnail img + .thumbnail-overlay {
      opacity: 0;
    }

    .slider-thumbnail img + .thumbnail-overlay:hover {
      opacity: 0.6;
    }

    /* Legacy indicator styles (keeping for backward compatibility) */
    .slider-indicators {
      position: absolute;
      bottom: 10px;
      left: 50%;
      transform: translateX(-50%);
      display: flex;
      gap: 8px;
      z-index: 10;
    }

    .slider-indicator {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.5);
      cursor: pointer;
      transition: background 0.3s ease;
    }

    .slider-indicator.active {
      background: rgba(255, 255, 255, 0.9);
    }

    .slider-indicator:hover {
      background: rgba(255, 255, 255, 0.7);
    }

    /* Profile Header Styles */
    .profile-header {
      background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
      color: white;
      padding: 30px;
      text-align: center;
      margin-left: -20px;
      margin-right: -20px;
      margin-top: -20px;
      margin-bottom: 30px;
      width: calc(100% + 40px);
      border-radius: 10px 10px 0 0;
    }

    .profile-header h2 {
      margin: 0;
      font-size: 36px;
      font-weight: 500;
      font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, 'Roboto', 'Helvetica Neue', Arial, sans-serif;
      text-shadow: 0 2px 4px rgba(0,0,0,0.3);
      letter-spacing: 1px;
    }

    /* User Guide Modal Styles */
    #userGuideModal {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.8);
      z-index: 10000;
      overflow-y: auto;
      display: none;
    }

    #userGuideModal > div {
      max-width: 900px;
      margin: 50px auto;
      background: white;
      border-radius: 10px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    }

    #userGuideModal .modal-header {
      padding: 20px 30px;
      border-bottom: 1px solid #eee;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    #userGuideModal .modal-header h2 {
      margin: 0;
      color: var(--brand);
      font-size: 24px;
    }

    #userGuideModal .modal-header button {
      background: none;
      border: none;
      font-size: 24px;
      cursor: pointer;
      color: #666;
      padding: 0;
    }

    #userGuideModal .modal-header button:hover {
      color: #333;
    }

    #userGuideContent {
      padding: 30px;
      max-height: 70vh;
      overflow-y: auto;
      line-height: 1.6;
    }

    #userGuideContent h1 {
      color: var(--brand);
      margin-top: 0;
      margin-bottom: 25px;
      font-size: 28px;
      text-align: center;
    }

    #userGuideContent h2 {
      color: var(--brand);
      margin-top: 40px;
      margin-bottom: 20px;
      font-size: 22px;
      border-bottom: 2px solid #eee;
      padding-bottom: 10px;
    }

    #userGuideContent h3 {
      color: var(--brand);
      margin-top: 30px;
      margin-bottom: 15px;
      font-size: 18px;
    }

    #userGuideContent p {
      margin-bottom: 15px;
      line-height: 1.6;
    }

    #userGuideContent ul, #userGuideContent ol {
      margin: 15px 0;
      padding-left: 30px;
    }

    #userGuideContent li {
      margin-bottom: 8px;
    }

    #userGuideContent code {
      background: #f1f3f4;
      padding: 2px 6px;
      border-radius: 3px;
      font-family: monospace;
      font-size: 14px;
    }

    #userGuideContent pre {
      background: #f8f9fa;
      padding: 15px;
      border-radius: 6px;
      border: 1px solid #e9ecef;
      margin: 15px 0;
      overflow-x: auto;
    }

    #userGuideContent pre code {
      background: none;
      padding: 0;
    }

    #userGuideContent a {
      color: var(--brand);
      text-decoration: none;
    }

    #userGuideContent a:hover {
      text-decoration: underline;
    }

    #userGuideContent hr {
      border: none;
      border-top: 1px solid #eee;
      margin: 30px 0;
    }

    #userGuideContent strong {
      font-weight: bold;
    }

    #userGuideContent em {
      font-style: italic;
    }

    /* Responsive modal */
    @media (max-width: 768px) {
      #userGuideModal > div {
        margin: 20px;
        max-width: none;
      }

      #userGuideModal .modal-header {
        padding: 15px 20px;
      }

      #userGuideModal .modal-header h2 {
        font-size: 20px;
      }

      #userGuideContent {
        padding: 20px;
        max-height: 80vh;
      }

      #userGuideContent h1 {
        font-size: 24px;
      }

      #userGuideContent h2 {
        font-size: 20px;
      }
    }

    /* User Guide Navigation Styles */
    #userGuideNav {
      transition: all 0.3s ease;
    }

    #userGuideNav.show-nav {
      display: block !important;
    }

    .nav-section {
      margin-bottom: 15px;
    }

    .nav-section-title {
      font-weight: bold;
      color: var(--brand);
      font-size: 13px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin: 0 0 8px 0;
      padding: 0 20px;
    }

    .nav-link {
      display: block;
      padding: 8px 20px;
      color: #555;
      text-decoration: none;
      font-size: 14px;
      border-left: 3px solid transparent;
      transition: all 0.2s ease;
      margin-bottom: 2px;
    }

    .nav-link:hover {
      background: rgba(76, 175, 80, 0.1);
      color: var(--brand);
      border-left-color: var(--brand);
      text-decoration: none;
    }

    .nav-link.active {
      background: var(--brand);
      color: white;
      border-left-color: var(--brand);
    }

    .nav-link i {
      margin-right: 8px;
      width: 14px;
      text-align: center;
    }

    #toggleNavBtn {
      transition: all 0.2s ease;
    }

    #toggleNavBtn.active {
      background: #666 !important;
    }

    /* Mobile navigation adjustments */
    @media (max-width: 768px) {
      #userGuideModal > div {
        flex-direction: column;
        margin: 10px;
      }

      #userGuideNav {
        width: 100% !important;
        border-right: none !important;
        border-bottom: 1px solid #eee !important;
        border-radius: 0 !important;
        max-height: 200px !important;
      }

      #userGuideNav.show-nav {
        display: block !important;
      }

      #userGuideContent {
        max-height: 60vh !important;
      }
    }
  </style>
</head>
<body>
  <?php include 'header.php'; ?>

  <!-- Introduction Section -->
  <div class="introduction-section">
    <div class="wrap">
      <div class="intro-content">
        <h1>Welcome to EV-LGRRC KP-HUB</h1>
        <?php if ($isLoggedIn): ?>
        <p class="intro-subtitle">developed by the DILG Region 8 - EV-LGRRC</p>
        <div class="intro-features">
          <div class="feature-item">
            <i class="fas fa-folder-open"></i>
            <h3>Organized File Management</h3>
            <p>Access and manage your facility's documents, reports, and resources in structured folders</p>
          </div>
          <div class="feature-item">
            <i class="fas fa-users"></i>
            <h3>Community Newsfeed</h3>
            <p>Share updates, insights, and knowledge with your colleagues through our interactive newsfeed</p>
          </div>
          <div class="feature-item">
            <i class="fas fa-chart-bar"></i>
            <h3>Analytics & Reports</h3>
            <p>Generate comprehensive reports and track usage patterns across your organization</p>
          </div>
        </div>
        <?php else: ?>
        <p class="intro-subtitle">Repository of knowledge products created by DILG Region 8 and partner institutions developed by the DILG Region 8 - EV-LGRRC</p>
        <div class="intro-features">
          <div class="feature-item">
            <i class="fas fa-users"></i>
            <h3>View and Download Knowledge Products</h3>
            <p>Easily access knowledge products created by DILG Region 8 and partner institutions.</p>
          </div>
          <div class="feature-item">
            <i class="fas fa-comments"></i>
            <h3>Share Your Feedback</h3>
            <p>Help us improve by sharing your experience and suggestions about our knowledge products and services.</p>
          </div>
        </div>
        <?php endif; ?>
        <div class="intro-stats">
          <div class="stat-item">
            <span class="stat-number"><?php echo number_format($visitCount ?? 0); ?></span>
            <span class="stat-label">Total Visits</span>
          </div>
          <div class="stat-item">
            <span class="stat-number" id="total-posts">0</span>
            <span class="stat-label">Facility Cards</span>
          </div>
          <div class="stat-item">
            <span class="stat-number" id="active-users">0</span>
            <span class="stat-label">Active Users</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Progress Overlay -->
  <div id="uploadProgressOverlay" style="display:none; justify-content:center; align-items:center;">
    <div style="background:#fff; border:2px solid #4CAF50; border-radius:8px; padding:20px 25px; box-shadow:0 4px 10px rgba(0,0,0,0.3); width:320px; text-align:center;">
      <h4 style="margin:0 0 10px; color:#4CAF50;">Uploading...</h4>
      <div style="width:100%; background:#ddd; border-radius:5px; overflow:hidden; height:20px;">
        <div id="uploadProgressBar" style="width:0%; height:100%; background:#4CAF50; transition:width 0.3s;"></div>
      </div>
      <p id="uploadProgressText" style="margin:8px 0 0; font-size:14px;">0%</p>
    </div>
  </div>

  <!-- Newsfeed Upload Progress Overlay -->
  <div id="newsfeedUploadProgressOverlay" style="display:none; justify-content:center; align-items:center;">
    <div style="background:#fff; border:2px solid #4CAF50; border-radius:8px; padding:20px 25px; box-shadow:0 4px 10px rgba(0,0,0,0.3); width:320px; text-align:center;">
      <h4 style="margin:0 0 10px; color:#4CAF50;">Sharing Post...</h4>
      <div style="width:100%; background:#ddd; border-radius:5px; overflow:hidden; height:20px;">
        <div id="newsfeedUploadProgressBar" style="width:0%; height:100%; background:#4CAF50; transition:width 0.3s;"></div>
      </div>
      <p id="newsfeedUploadProgressText" style="margin:8px 0 0; font-size:14px;">0%</p>
    </div>
  </div>

  <!-- Tabs -->
  <div class="tabs wrap" style="display: none;">
    <?php if ($isLoggedIn): ?>
    <div class="tab" onclick="switchTab(event, 'view-folder-tab')" tabindex="0">View Folders</div>
    <?php endif; ?>
    <div class="tab" onclick="switchTab(event, 'newsfeed-tab')" tabindex="0">Newsfeed</div>
    <?php if ($isLoggedIn): ?>
    <div class="tab" onclick="switchTab(event, 'reports-tab')" tabindex="0">Reports</div>
    <?php endif; ?>
  </div>

  <?php if ($isLoggedIn): ?>
  <!-- View Folders -->
  <div id="view-folder-tab" class="tab-content">
    <div class="wrap">

      <h3 style="margin-bottom: 20px; color: var(--brand);">
        <i class="fas fa-folder-open" style="margin-right: 10px;"></i>
        Main Folders
      </h3>

      <!-- View Toggle Buttons -->
      <div class="view-toggle" style="margin-bottom: 15px; display: flex; gap: 10px;">
        <button id="grid-view-btn" class="view-btn active" onclick="setFolderView('grid')" title="Grid View">
          <i class="fas fa-th"></i> Grid
        </button>
        <button id="list-view-btn" class="view-btn" onclick="setFolderView('list')" title="List View">
          <i class="fas fa-list"></i> List
        </button>
      </div>

      <div id="folder-list" class="folder-grid"></div>
      <div id="folder-contents" style="margin-top: 30px;"></div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Newsfeed -->
  <div id="newsfeed-tab" class="tab-content">
    <div class="profile-header">
      <h2>🗂️ KP-HUB Facility Sections</h2>
    </div>
    <div class="wrap">
      <div class="newsfeed-directory-intro" style="margin-bottom: 20px; padding: 16px; background: #f8fbff; border: 1px solid #dfe7f5; border-radius: 10px;">
        <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start; flex-wrap:wrap;">
          <div>
            <h3 style="margin: 0 0 6px; color: var(--brand);"><?= $newsfeedEditMode ? 'Facility Card Manager' : 'Facility Sections Directory' ?></h3>
            <p style="margin: 0; color: #666; font-size: 14px;">
              <?= $newsfeedEditMode ? 'Edit the sections and links for your own facility card.' : 'Browse accordion sections by facility.' ?>
            </p>
          </div>
          <?php if ($isLoggedIn): ?>
            <a href="facility_cards_manage.php" style="display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border-radius:8px; background: var(--brand); color:#fff; text-decoration:none; font-weight:bold;">
              <i class="fas fa-pen-to-square"></i>
              <span><?= $newsfeedEditMode ? 'Manage View' : 'Open Manager' ?></span>
            </a>
          <?php endif; ?>
        </div>
      </div>

      <div id="facility-cards-container">
        <div class="empty">Loading facility cards...</div>
      </div>
      
      <!-- Notification Dropdown -->
      <div id="notification-dropdown" class="notification-dropdown" style="display: none; position: fixed; top: 60px; right: 20px;">
        <div class="notification-header">
          <h4>Notifications</h4>
          <span onclick="markAllAsRead()" class="mark-read-btn">Mark all as read</span>
        </div>
        <div id="notification-list" class="notification-list">
          <!-- Notifications will be loaded here -->
        </div>
      </div>
      
    </div>
  </div>

  <?php if ($isLoggedIn): ?>
  <!-- Reports -->
  <div id="reports-tab" class="tab-content" style="text-align:center;">
    <div class="wrap" style="display:flex; flex-direction:column; align-items:center;">
      <h3>Reports</h3>
      
      <!-- Development Warning -->
      <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 15px; margin-bottom: 20px; max-width: 600px; text-align: left;">
        <div style="display: flex; align-items: center; margin-bottom: 10px;">
          <i class="fas fa-info-circle" style="color: #856404; margin-right: 10px; font-size: 18px;"></i>
          <strong style="color: #856404;">Feature in Development</strong>
        </div>
        <p style="color: #856404; margin: 0; font-size: 14px;">
          This reporting feature is still in development and may take some time to generate your report. 
          We're working to improve performance and add more detailed analytics.<br><br>
          <em>Should you wish to continue, Patience is a Virtue.</em>
        </p>
      </div>
      
      <button onclick="generateReport()">Generate Report</button>
      <div style="display:flex; justify-content:center; align-items:center; flex-direction:column; width:100%; max-width:900px;">
        <canvas id="report-chart" width="800" height="500"></canvas>
      </div>
      <div id="report-total" style="margin-top:10px; text-align:center; font-weight:bold; font-size:25px;"></div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Loading Modal -->
  <div id="loadingModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center;">
    <div style="background:#fff; padding:20px; border-radius:8px;">Generating Report...This may take a few minutes...</div>
  </div>

<script>
  const APPS_SCRIPT_URL = "https://script.google.com/macros/s/AKfycbzyElBwSKei2iUGfhb5siS2c9G0KtyejI5cSltdvbrw1fD7ktwcRdePgo7kxRLwKWEH/exec?";
  const newsfeedEditMode = <?= $newsfeedEditMode ? 'true' : 'false' ?>;

  function showFolderLoading(message = "Loading folder contents...") {
    // Remove existing loading overlay if present
    const existingOverlay = document.getElementById('folderLoadingOverlay');
    if (existingOverlay) {
      existingOverlay.remove();
    }

    // Create loading overlay
    const overlay = document.createElement('div');
    overlay.id = 'folderLoadingOverlay';
    overlay.className = 'folder-operation-loading';
    overlay.innerHTML = `
      <div class="loading-content">
        <div class="spinner"></div>
        <p style="margin: 0; color: #333; font-weight: 500;">${message}</p>
      </div>
    `;
    document.body.appendChild(overlay);
  }

  function hideFolderLoading() {
    const overlay = document.getElementById('folderLoadingOverlay');
    if (overlay) {
      overlay.remove();
    }
  }

  function loadFolderThumbnails() {
    showFolderLoading("Loading folders...");
    
    fetch(`${APPS_SCRIPT_URL}action=getFolders&parentFolderId=${window.USER_FOLDER_ID}`)
      .then(res => res.json())
      .then(folders => {
        hideFolderLoading();
        
        // Reset breadcrumb to root when loading folder thumbnails
        window.FOLDER_PATH = [{ id: window.USER_FOLDER_ID, name: 'My Files' }];
        
        const list = document.getElementById('folder-list');
        list.innerHTML = "";
        if (!Array.isArray(folders)) {
          console.error("Expected array but got:", folders);
          list.innerHTML = '<div class="empty">No folders found.</div>';
          return;
        }
        if (folders.length === 0) {
          list.innerHTML = '<div class="empty">No folders found.</div>';
          return;
        }
        folders.forEach(folder => {
          const card = document.createElement('div');
          card.className = 'folder-card';
          card.innerHTML = `
            <div class="copy-link-btn" onclick="copyFolderLink(event, '${folder.id}', '${folder.name.replace(/'/g, "\\'")}')" title="Copy folder link">
              <i class="fas fa-copy"></i>
            </div>
            <div class="share-to-newsfeed-btn" onclick="shareToNewsfeed('https://drive.google.com/drive/folders/${folder.id}', '${folder.name.replace(/'/g, "\\'")}', event, true)" title="Share folder to Newsfeed">
              <i class="fas fa-share-alt"></i>
            </div>
            <i class="fas fa-folder folder-icon"></i>
            <div class="folder-name">${folder.name}</div>
          `;
          card.onclick = (e) => {
            if (!e.target.closest('.copy-link-btn') && !e.target.closest('.share-to-newsfeed-btn')) {
              loadSubFolders(folder.id, folder.name);
            }
          };
          list.appendChild(card);
        });
      })
      .catch(() => {
        hideFolderLoading();
        const list = document.getElementById('folder-list');
        list.innerHTML = '<div class="empty">Unable to load folders.</div>';
      });
  }

  // Initialize breadcrumb path
  window.FOLDER_PATH = [{ id: null, name: 'Home' }];

  function updateBreadcrumbPath(folderId, folderName) {
    // If navigating to root (user folder), reset path
    if (folderId === window.USER_FOLDER_ID) {
      window.FOLDER_PATH = [{ id: window.USER_FOLDER_ID, name: 'My Files' }];
    } else {
      // Find if this folder is already in the path
      const existingIndex = window.FOLDER_PATH.findIndex(item => item.id === folderId);
      
      if (existingIndex !== -1) {
        // If folder exists in path, truncate to that point
        window.FOLDER_PATH = window.FOLDER_PATH.slice(0, existingIndex + 1);
      } else {
        // Add new folder to path
        window.FOLDER_PATH.push({ id: folderId, name: folderName });
      }
    }
    
    // Breadcrumb will be displayed when loadSubFolders is called
  }

  function loadSubFolders(folderId, folderName, highlightNames = []) {
    window.CURRENT_FOLDER_ID = folderId;
    
    // Update breadcrumb path
    updateBreadcrumbPath(folderId, folderName);
    
    showFolderLoading(`Opening "${folderName}"...`);

    fetch(`${APPS_SCRIPT_URL}action=getFolderContents&parentFolderId=${folderId}`)
      .then(res => res.json())
      .then(data => {
        hideFolderLoading();
        
        if (!data || !Array.isArray(data.folders) || !Array.isArray(data.files)) {
          Swal.fire("Error", "Invalid folder contents response. Check GAS.", "error");
          return;
        }

        const contents = document.getElementById('folder-contents');
        
        // Generate breadcrumb HTML
        let breadcrumbHtml = '<div id="breadcrumb-nav" style="margin-bottom: 15px; padding: 10px; background: #f8f9fa; border-radius: 6px; border: 1px solid #e9ecef;">';
        breadcrumbHtml += '<nav aria-label="breadcrumb"><ol class="breadcrumb" style="margin: 0; background: none; padding: 0;">';
        
        window.FOLDER_PATH.forEach((item, index) => {
          const isLast = index === window.FOLDER_PATH.length - 1;
          const isFirst = index === 0;
          
          if (isLast) {
            breadcrumbHtml += `<li class="breadcrumb-item active" style="color: #6c757d; font-weight: 500; display: flex; align-items: center; gap: 8px;" aria-current="page">
              <span>${item.name}</span>
              <button onclick="copyFolderLink(event, '${item.id}', '${item.name.replace(/'/g, "\\'")}')" 
                      style="background: rgba(76, 175, 80, 0.1); border: 1px solid #4CAF50; border-radius: 4px; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #4CAF50;" 
                      title="Copy folder link">
                <i class="fas fa-copy" style="font-size: 12px;"></i>
              </button>
            </li>`;
          } else {
            const clickHandler = isFirst ? 'loadFolderThumbnails()' : `loadSubFolders('${item.id}', '${item.name.replace(/'/g, "\\'")}')`;
            breadcrumbHtml += `<li class="breadcrumb-item">
              <a href="#" onclick="${clickHandler}; return false;" style="color: #007bff; text-decoration: none;">
                ${item.name}
              </a>
            </li>`;
          }
        });
        
        breadcrumbHtml += '</ol></nav></div>';
        
        contents.innerHTML = `
          ${breadcrumbHtml}
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <h4 style="margin:0;">Contents</h4>
            <div style="display: flex; gap: 10px; align-items: center;">
              ${window.USER_ROLE === 'Admin' || window.USER_ROLE === 'Head' ? `
                <input type="text" id="newSubFolderName" placeholder="New folder name" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;" />
                <button onclick="handleCreateSubFolder()" style="padding: 8px 12px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; display: flex; align-items: center; gap: 5px; min-width: 140px; justify-content: center;">
                  <i class="fas fa-folder-plus"></i> Create Folder
                </button>
              ` : ''}
              <div id="upload-here-section">
                <div class="folder-upload-buttons" style="display: flex; gap: 15px; align-items: center;">
                  <!-- Upload Here Button -->
                  <button type="button" onclick="document.getElementById('subFolderFileInput').click();" 
                          style="background: #2196F3; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-size: 14px; display: flex; align-items: center; gap: 5px; min-width: 140px; justify-content: center;"
                          title="Maximum 5 files&#10;Total size: 50MB">
                    <i class="fas fa-upload"></i> Upload Here
                  </button>
                  
                  <!-- Drive Link Upload Toggle -->
                  <div class="drive-link-toggle">
                    <button type="button" id="toggleFolderDriveLinkBtn" onclick="toggleFolderDriveLinkSection()" 
                            style="background: none; border: none; color: #ff9800; cursor: pointer; font-size: 14px; padding: 8px 12px; border-radius: 6px; border: 1px solid #ddd; display: flex; align-items: center; gap: 5px; min-width: 140px; justify-content: center;">
                      <i class="fas fa-cloud-upload-alt" id="folderDriveIcon"></i>
                      <span id="folderDriveText">Upload via <img src="uploads/gdrive.png" style="height: 15px;" alt="Drive"> Link</span>
                      <i class="fas fa-chevron-down" id="folderDriveChevron" style="font-size: 12px;"></i>
                    </button>
                  </div>
                </div>
                
                <input type="file" id="subFolderFileInput" multiple style="display:none;">
                
                <!-- Drive Link Upload Section -->
                <div id="folderDriveLinkSection" style="display: none; margin-top: 10px; padding: 10px; background: #fff3cd; border-radius: 6px; border: 1px solid #ffeaa7;">
                  <input type="url" id="folderDriveLinkInput" placeholder="Paste Google Drive share link here" 
                         style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 5px;" />
                  <input type="text" id="folderDriveFileName" placeholder="File name (optional)" 
                         style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" />
                  <button type="button" onclick="uploadDriveLinkToFolder()" 
                          style="margin-top: 10px; padding: 8px 15px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;">
                    Upload Drive Link
                  </button>
                  <small style="color: #856404; font-size: 12px; display: block; margin-top: 5px;">Upload large files via Google Drive link</small>
                </div>
              </div>
            </div>
          </div>
          <div id="sub-list" class="folder-grid" style="margin-top:16px;"></div>
        `;

        if (window.USER_ROLE !== 'Admin' && window.USER_ROLE !== 'Head') {
          const uhs = document.getElementById('upload-here-section');
          if (uhs) uhs.style.display = 'none';
        }

        const subInput = document.getElementById('subFolderFileInput');
        subInput.onchange = function () { uploadFileToFolder(folderId, folderName); };

        const subList = document.getElementById('sub-list');

        // Apply saved view preference to subfolder contents
        const savedView = localStorage.getItem('folderViewPreference') || 'grid';
        if (savedView === 'list') {
          subList.className = 'folder-list';
        }

        // Subfolders
        data.folders.forEach(sub => {
          const card = document.createElement('div');
          card.className = 'folder-card';
          card.innerHTML = `
            <div class="copy-link-btn" onclick="copyFolderLink(event, '${sub.id}', '${sub.name.replace(/'/g, "\\'")}')" title="Copy folder link">
              <i class="fas fa-copy"></i>
            </div>
            <div class="share-to-newsfeed-btn" onclick="shareToNewsfeed('https://drive.google.com/drive/folders/${sub.id}', '${sub.name.replace(/'/g, "\\'")}', event, true)" title="Share folder to Newsfeed">
              <i class="fas fa-share-alt"></i>
            </div>
            <i class="fas fa-folder folder-icon"></i>
            <div class="folder-name">${sub.name}</div>
          `;
          card.onclick = (e) => {
            if (!e.target.closest('.copy-link-btn') && !e.target.closest('.share-to-newsfeed-btn')) {
              loadSubFolders(sub.id, sub.name);
            }
          };
          subList.appendChild(card);
        });

        // Files
        data.files.forEach(file => {
          const ext = String(file.name || '').split('.').pop().toLowerCase();
          let icon = 'fa-file';
          if (ext === 'pdf') icon = 'fa-file-pdf';
          else if (['doc', 'docx'].includes(ext)) icon = 'fa-file-word';
          else if (['xls', 'xlsx'].includes(ext)) icon = 'fa-file-excel';
          else if (['ppt', 'pptx'].includes(ext)) icon = 'fa-file-powerpoint';
          else if (['png', 'jpg', 'jpeg', 'gif'].includes(ext)) icon = 'fa-file-image';
          else if (['mp4', 'avi', 'mov'].includes(ext)) icon = 'fa-file-video';
          else if (['mp3', 'wav'].includes(ext)) icon = 'fa-file-audio';

          const card = document.createElement('div');
          card.className = 'folder-card';
          if (highlightNames.includes(file.name)) card.classList.add('highlight');
          
          card.innerHTML = `
            <div class="copy-link-btn" onclick="copyFileLink(event, '${file.url}', '${file.name}')" title="Copy file link">
              <i class="fas fa-copy"></i>
            </div>
            <div class="share-to-newsfeed-btn" onclick="shareToNewsfeed('${file.url}', '${file.name}', event)" title="Share to Newsfeed">
              <i class="fas fa-share-alt"></i>
            </div>
            ${window.CAN_DELETE_FILES ? `
            <div class="delete-file-btn" onclick="deleteFile(event, '${file.id}', '${file.name.replace(/'/g, "\\'")}')" title="Delete file" style="position: absolute; top: 8px; right: 8px; background: rgba(244, 67, 54, 0.9); border: 1px solid #f44336; border-radius: 4px; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; cursor: pointer; opacity: 1; transition: opacity 0.2s, background 0.2s; z-index: 10;">
              <i class="fas fa-trash" style="font-size: 12px; color: white;"></i>
            </div>
            ` : ''}
            <i class="fas ${icon} folder-icon"></i>
            <a href="${file.url}" target="_blank" class="folder-name" title="${file.name}">${file.name}</a>
          `;
          card.onclick = (e) => {
            if (!e.target.closest('.copy-link-btn') && !e.target.closest('.share-to-newsfeed-btn') && !e.target.closest('.delete-file-btn')) {
              window.open(file.url, '_blank');
            }
          };
          subList.appendChild(card);
        });

        if (subList.children.length === 0) {
          subList.innerHTML = '<div class="empty" style="grid-column: 1 / -1;">No files yet in this folder.</div>';
        }
      })
      .catch(err => {
        hideFolderLoading();
        console.error("loadSubFolders error:", err);
        Swal.fire("Error", "Failed to load folder contents.", "error");
      });
  }

  function uploadFileToFolder(folderId, folderName) {
    const input = document.getElementById('subFolderFileInput');
    const files = input.files;
    if (!files.length) { Swal.fire("Oops!", "Please select files.", "warning"); return; }
    if (files.length > 5) { Swal.fire("Limit", "Max 5 files.", "info"); return; }
    const totalSizeMB = [...files].reduce((s, f) => s + f.size, 0) / 1024 / 1024;
    if (totalSizeMB > 50) { Swal.fire("Limit", "Total exceeds 50MB.", "info"); return; }

    // Collect file data for logging before upload
    const fileDataForLogging = [...files].map(f => ({
      name: f.name,
      size: f.size,
      type: f.type
    }));

    const overlay = document.getElementById("uploadProgressOverlay");
    const bar = document.getElementById("uploadProgressBar");
    const text = document.getElementById("uploadProgressText");
    overlay.style.display = "flex";
    bar.style.width = "0%";
    text.textContent = "0%";

    let uploadedCount = 0;
    const totalFiles = files.length;
    const newFileNames = [];

    function updateProgress() {
      const percent = Math.round((uploadedCount / totalFiles) * 100);
      bar.style.width = percent + "%";
      text.textContent = percent + "%";
    }

    [...files].forEach((file, index) => {
      const reader = new FileReader();
      
      // Track reading progress
      reader.addEventListener('progress', function(e) {
        if (e.lengthComputable) {
          // Calculate progress for this specific file
          const fileProgress = Math.round((e.loaded / e.total) * 100);
          // Update overall progress: completed files + current file progress
          const overallProgress = Math.round(((uploadedCount + (fileProgress / 100)) / totalFiles) * 100);
          bar.style.width = overallProgress + "%";
          text.textContent = overallProgress + "%";
        }
      });
      
      reader.readAsDataURL(file);
      reader.onload = () => {
        const base64Data = String(reader.result).split(',')[1];
        const formData = new FormData();
        formData.append("action", "uploadFile");
        formData.append("folderId", folderId);
        formData.append("fileName", file.name);
        formData.append("mimeType", file.type);
        formData.append("file", base64Data);

        fetch(APPS_SCRIPT_URL, { method: "POST", body: formData })
          .then(r => r.json())
          .then(data => {
            if (data && data.error) throw new Error(data.error);
            uploadedCount += 1;
            newFileNames.push(file.name);
            
            // Update progress when file is fully uploaded
            const percent = Math.round((uploadedCount / totalFiles) * 100);
            bar.style.width = percent + "%";
            text.textContent = percent + "%";
            
            if (uploadedCount === totalFiles) {
              text.textContent = "Upload Complete!";
              
              // Log the folder uploads to monitoring system
              const uploadData = {
                folder_name: folderName,
                files: fileDataForLogging
              };
              
              fetch('log_folder_upload.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(uploadData)
              })
              .then(r => r.json())
              .then(result => {
                // Logging completed
              })
              .catch(err => console.error('Logging error:', err));
              
              setTimeout(() => {
                overlay.style.display = "none";
                loadSubFolders(folderId, folderName, newFileNames);
              }, 800);
            }
          })
          .catch(err => {
            Swal.fire("Upload failed", `Failed to upload ${file.name}: ${err.message}`, "error");
            overlay.style.display = "none";
          });
      };
      
      reader.onerror = () => {
        Swal.fire("Upload failed", `Failed to read ${file.name}`, "error");
        overlay.style.display = "none";
      };
    });

    input.value = "";
  }

  function handleCreateSubFolder() {
    const name = document.getElementById("newSubFolderName").value.trim();
    if (!name) { Swal.fire("Oops!", "Please enter a folder name.", "warning"); return; }

    Swal.fire({
      title: "Create Folder",
      html: `You are about to create <strong>"${name}"</strong><br>under the current folder.`,
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "Yes, create it!",
      cancelButtonText: "Cancel"
    }).then((result) => {
      if (result.isConfirmed) {
        showFolderLoading(`Creating folder "${name}"...`);
        
        const formData = new FormData();
        formData.append("action", "createFolder");
        formData.append("folderName", name);
        formData.append("parentFolderId", window.CURRENT_FOLDER_ID);

        fetch(APPS_SCRIPT_URL, { method: "POST", body: formData })
          .then(r => r.json())
          .then(data => {
            hideFolderLoading();
            
            if (data && data.id) {
              // Add small delay to ensure loading overlay is fully hidden
              setTimeout(() => {
                Swal.fire({ title: "Created!", text: `Folder "${data.name}" was created successfully.`, icon: "success", timer: 1500, showConfirmButton: false });
              }, 100);

              if (window.CURRENT_FOLDER_ID === window.USER_FOLDER_ID) {
                loadFolderThumbnails();
              } else {
                loadSubFolders(window.CURRENT_FOLDER_ID, "");
              }
              document.getElementById("newSubFolderName").value = "";
            } else {
              Swal.fire("Error", (data && data.error) || "Unknown error occurred.", "error");
            }
          })
          .catch(err => { 
            hideFolderLoading();
            Swal.fire("Error", err.message, "error"); 
          });
      }
    });
  }

  function toggleFolderDriveLinkSection() {
    const section = document.getElementById('folderDriveLinkSection');
    const icon = document.getElementById('folderDriveIcon');
    const chevron = document.getElementById('folderDriveChevron');
    const text = document.getElementById('folderDriveText');
    
    if (section.style.display === 'none' || section.style.display === '') {
      section.style.display = 'block';
      icon.className = 'fas fa-cloud-upload-alt';
      chevron.className = 'fas fa-chevron-up';
      text.textContent = 'Hide Google Drive Upload';
    } else {
      section.style.display = 'none';
      icon.className = 'fas fa-cloud-upload-alt';
      chevron.className = 'fas fa-chevron-down';
      text.textContent = 'Upload via Google Drive Link';
    }
  }

  function uploadDriveLinkToFolder() {
    const driveLink = document.getElementById('folderDriveLinkInput').value.trim();
    const fileName = document.getElementById('folderDriveFileName').value.trim() || 'Shared File';
    
    if (!driveLink) {
      Swal.fire('Error', 'Please enter a Google Drive link', 'error');
      return;
    }
    
    // Extract file ID from drive link
    const fileIdMatch = driveLink.match(/\/d\/([a-zA-Z0-9-_]+)/);
    if (!fileIdMatch) {
      Swal.fire('Error', 'Invalid Google Drive link', 'error');
      return;
    }
    
    const fileId = fileIdMatch[1];
    
    showFolderLoading('Adding Drive file...');
    
    const formData = new FormData();
    formData.append("action", "addDriveFile");
    formData.append("folderId", window.CURRENT_FOLDER_ID);
    formData.append("fileId", fileId);
    formData.append("fileName", fileName);
    
    fetch(APPS_SCRIPT_URL, { method: "POST", body: formData })
      .then(r => r.json())
      .then(data => {
        hideFolderLoading();
        if (data && data.success) {
          Swal.fire('Success', 'Drive file added to folder', 'success');
          loadSubFolders(window.CURRENT_FOLDER_ID, '');
          // Clear inputs
          document.getElementById('folderDriveLinkInput').value = '';
          document.getElementById('folderDriveFileName').value = '';
          toggleFolderDriveLinkSection(); // Hide the section
        } else {
          Swal.fire('Error', data.error || 'Failed to add Drive file', 'error');
        }
      })
      .catch(err => {
        hideFolderLoading();
        Swal.fire('Error', 'Failed to add Drive file', 'error');
      });
  }

  function copyFileLink(event, fileUrl, fileName) {
    event.stopPropagation(); // Prevent opening the file
    
    // Use the Clipboard API if available
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(fileUrl).then(() => {
        showCopySuccess(fileName);
      }).catch(() => {
        fallbackCopyTextToClipboard(fileUrl, fileName);
      });
    } else {
      // Fallback for older browsers
      fallbackCopyTextToClipboard(fileUrl, fileName);
    }
  }

  function copyFolderLink(event, folderId, folderName) {
    event.stopPropagation(); // Prevent opening the folder
    
    const folderUrl = `https://drive.google.com/drive/folders/${folderId}`;
    
    // Use the Clipboard API if available
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(folderUrl).then(() => {
        showCopySuccess(folderName);
      }).catch(() => {
        fallbackCopyTextToClipboard(folderUrl, folderName);
      });
    } else {
      // Fallback for older browsers
      fallbackCopyTextToClipboard(folderUrl, folderName);
    }
  }

  function deleteFile(event, fileId, fileName) {
    event.stopPropagation(); // Prevent opening the file
    
    Swal.fire({
      title: 'Delete File',
      html: `Are you sure you want to delete <strong>"${fileName}"</strong>?<br><br>This action cannot be undone.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete it!',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#f44336'
    }).then((result) => {
      if (result.isConfirmed) {
        // Show loading on the confirmation dialog
        Swal.fire({
          title: 'Deleting...',
          text: `Deleting "${fileName}"...`,
          allowOutsideClick: false,
          allowEscapeKey: false,
          showConfirmButton: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });
        
        const formData = new FormData();
        formData.append("action", "deleteFile");
        formData.append("fileId", fileId);

        fetch(APPS_SCRIPT_URL, { method: "POST", body: formData })
          .then(r => r.json())
          .then(data => {
            if (data && data.success) {
              Swal.fire({
                title: "Deleted!",
                text: `"${fileName}" has been deleted successfully.`,
                icon: "success",
                timer: 1500,
                showConfirmButton: false
              }).then(() => {
                // Reload the current folder contents after success modal closes
                loadSubFolders(window.CURRENT_FOLDER_ID, "");
              });
            } else {
              Swal.fire("Error", (data && data.error) || "Failed to delete file.", "error");
            }
          })
          .catch(err => {
            console.error("deleteFile error:", err);
            Swal.fire("Error", "Failed to delete file.", "error");
          });
      }
    });
  }

  function copyPostLink(postId) {
    // Create a URL that links directly to the post
    const currentUrl = window.location.origin + window.location.pathname;
    const postUrl = `${currentUrl}?tab=newsfeed&post=${postId}`;
    
    // Use the Clipboard API if available
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(postUrl).then(() => {
        showCopySuccess('Post link');
      }).catch(() => {
        fallbackCopyTextToClipboard(postUrl, 'Post link');
      });
    } else {
      // Fallback for older browsers
      fallbackCopyTextToClipboard(postUrl, 'Post link');
    }
  }

  function showCopySuccess(itemName) {
    // Simple success notification
    const notification = document.createElement('div');
    notification.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      background: #4CAF50;
      color: white;
      padding: 12px 20px;
      border-radius: 6px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      z-index: 10000;
      font-size: 14px;
      opacity: 0;
      transform: translateY(-10px);
      transition: all 0.3s ease;
    `;
    notification.textContent = `Link copied: ${itemName}`;
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
      notification.style.opacity = '1';
      notification.style.transform = 'translateY(0)';
    }, 10);
    
    // Animate out and remove
    setTimeout(() => {
      notification.style.opacity = '0';
      notification.style.transform = 'translateY(-10px)';
      setTimeout(() => {
        if (notification.parentNode) {
          notification.parentNode.removeChild(notification);
        }
      }, 300);
    }, 2000);
  }

  function fallbackCopyTextToClipboard(text, itemName) {
    const textArea = document.createElement("textarea");
    textArea.value = text;
    
    // Avoid scrolling to bottom
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.position = "fixed";
    textArea.style.opacity = "0";
    
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
      const successful = document.execCommand('copy');
      if (successful) {
        showCopySuccess(itemName);
      } else {
        console.error('Fallback: Copy command was unsuccessful');
      }
    } catch (err) {
      console.error('Fallback: Oops, unable to copy', err);
    }
    
    document.body.removeChild(textArea);
  }

  function shareToNewsfeed(itemUrl, itemName, event, isFolder = false) {
    event.stopPropagation(); // Prevent opening the item
    
    const itemType = isFolder ? 'folder' : 'file';
    const itemIcon = isFolder ? '📁' : '📄';
    
    Swal.fire({
      title: `Share ${itemType} to Newsfeed`,
      html: `
        <div style="text-align: left;">
          <p style="margin-bottom: 15px; color: #666;">Share ${itemIcon} <strong>${itemName}</strong> to the community newsfeed</p>
          <label style="display: block; margin-bottom: 5px; font-weight: bold;">Title:</label>
          <input type="text" id="shareTitle" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 15px; font-size: 16px; font-weight: bold;" 
                 placeholder="Enter a title for this ${itemType}" required>
          <label style="display: block; margin-bottom: 5px; font-weight: bold;">Add a message (optional):</label>
          <textarea id="shareMessage" style="width: 100%; height: 80px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; resize: vertical;" 
                    placeholder="What's this ${itemType} about?"></textarea>
        </div>
      `,
      showCancelButton: true,
      confirmButtonText: `Share ${itemType}`,
      confirmButtonColor: '#4CAF50',
      preConfirm: () => {
        const title = document.getElementById('shareTitle').value.trim();
        const message = document.getElementById('shareMessage').value.trim();
        
        if (!title) {
          Swal.showValidationMessage('Please enter a title');
          return false;
        }
        
        // Show loading
        Swal.showLoading();
        
        // Send to server
        const formData = new FormData();
        
        // For folders, create a link instead of using post_type
        if (isFolder) {
          const folderLink = [{ url: itemUrl, label: itemName }];
          formData.append('links', JSON.stringify(folderLink));
        } else {
          // For files, send file details
          formData.append('file_url', itemUrl);
          formData.append('file_name', itemName);
        }
        
        formData.append('title', title);
        formData.append('content', message);
        
        return fetch('share_to_newsfeed.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (!data.success) {
            throw new Error(data.message || `Failed to share ${itemType}`);
          }
          return data;
        });
      }
    }).then((result) => {
      if (result.isConfirmed) {
        Swal.fire({
          title: 'Shared!',
          text: `${itemType.charAt(0).toUpperCase() + itemType.slice(1)} has been shared to the newsfeed`,
          icon: 'success',
          timer: 2000,
          showConfirmButton: false
        }).then(() => {
          // Reload the newsfeed to show the new post
          if (document.getElementById('newsfeed-tab').classList.contains('active')) {
            loadNewsfeed(1);
          }
        });
      }
    }).catch(error => {
      Swal.fire('Error', error.message, 'error');
    });
  }

  function setFolderView(viewType) {
    const folderList = document.getElementById('folder-list');
    const subList = document.getElementById('sub-list');
    const gridBtn = document.getElementById('grid-view-btn');
    const listBtn = document.getElementById('list-view-btn');

    // Update button states
    gridBtn.classList.toggle('active', viewType === 'grid');
    listBtn.classList.toggle('active', viewType === 'list');

    // Update container classes
    if (viewType === 'grid') {
      folderList.className = 'folder-grid';
      if (subList) subList.className = 'folder-grid';
    } else {
      folderList.className = 'folder-list';
      if (subList) subList.className = 'folder-list';
    }

    // Save preference to localStorage
    localStorage.setItem('folderViewPreference', viewType);
  }

  function switchTab(e, id) {
    document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    if (e && e.target) e.target.classList.add('active');
    document.getElementById(id).classList.add('active');
    localStorage.setItem("selectedTab", id);
    if (id === "view-folder-tab") {
      loadFolderThumbnails();
      // Apply saved view preference
      const savedView = localStorage.getItem('folderViewPreference') || 'grid';
      setFolderView(savedView);
    }
    if (id === "newsfeed-tab") loadNewsfeed(1);
  }

  function loadNewsfeed(page = 1, searchTerm = '') {
    loadNewsfeedSections();
    loadNotifications();
  }

  function updatePaginationControls(pagination) {
    return;
  }

  // Custom smooth scroll function for gentler animation
  function smoothScrollToElement(element, duration = 800) {
    const targetPosition = element.getBoundingClientRect().top + window.pageYOffset;
    const startPosition = window.pageYOffset;
    const distance = targetPosition - startPosition;
    let startTime = null;

    function animation(currentTime) {
      if (startTime === null) startTime = currentTime;
      const timeElapsed = currentTime - startTime;
      const progress = Math.min(timeElapsed / duration, 1);
      
      // Easing function for smoother animation
      const easeInOutCubic = progress < 0.5 
        ? 4 * progress * progress * progress 
        : 1 - Math.pow(-2 * progress + 2, 3) / 2;
      
      window.scrollTo(0, startPosition + distance * easeInOutCubic);
      
      if (timeElapsed < duration) {
        requestAnimationFrame(animation);
      }
    }
    
    requestAnimationFrame(animation);
  }

  function loadPage(page) {
    if (page < 1) return;
    
    // Gentle smooth scroll to top of facility cards with custom animation
    const facilityCardsContainer = document.getElementById('facility-cards-container');
    if (facilityCardsContainer) {
      smoothScrollToElement(facilityCardsContainer, 1000); // 1 second duration
    }
    
    setTimeout(() => {
      loadNewsfeed(page);
    }, 300);
  }

  // Post creation in newsfeed (only for logged-in users)
  document.addEventListener('DOMContentLoaded', function() {
    // Initialize CKEditor 5 for rich text editing
    const newsfeedContentElement = document.querySelector('#newsfeedPostContent');
    if (newsfeedContentElement) {
      ClassicEditor
        .create(newsfeedContentElement, {
          toolbar: [
            'heading', '|',
            'bold', 'italic', 'underline', 'strikethrough', '|',
            'fontColor', 'fontBackgroundColor', '|',
            'bulletedList', 'numberedList', '|',
            'link', '|',
            'blockQuote', '|',
            'undo', 'redo'
          ],
          language: 'en',
          placeholder: "What's happening in your facility?"
        })
        .then(editor => {
          window.newsfeedEditor = editor;
        })
        .catch(error => {
          console.error('CKEditor initialization error:', error);
        });
    }

    // File selection display for newsfeed
    const newsfeedFileInput = document.getElementById('newsfeedPostFile');
    if (newsfeedFileInput) {
      newsfeedFileInput.onchange = function() {
        const files = this.files;
        if (files.length > 0) {
          // Check each file size (50MB limit per file)
          const maxSize = 50 * 1024 * 1024; // 50MB in bytes
          let oversizedFiles = [];

          for (let i = 0; i < files.length; i++) {
            if (files[i].size > maxSize) {
              oversizedFiles.push(files[i].name);
            }
          }

          if (oversizedFiles.length > 0) {
            alert('The following files are too large (max 50MB each):\n' + oversizedFiles.join('\n'));
            this.value = ''; // Clear the file input
            document.getElementById('newsfeedSelectedFileName').textContent = '';
            return;
          }

          // Display selected file names
          const fileNames = Array.from(files).map(file => file.name);
          const displayText = files.length === 1 ? fileNames[0] : `${files.length} files selected`;
          document.getElementById('newsfeedSelectedFileName').textContent = displayText;
        } else {
          document.getElementById('newsfeedSelectedFileName').textContent = '';
        }
      };
    }

    // Post submission for newsfeed (only if form exists - logged-in users)
    const newsfeedPostForm = document.getElementById('newsfeedPostForm');
    if (newsfeedPostForm) {
      newsfeedPostForm.onsubmit = function(e) {
        e.preventDefault();
        
        // Get title and content from form
        const title = document.getElementById('newsfeedPostTitle').value.trim();
        const content = window.newsfeedEditor ? window.newsfeedEditor.getData() : '';
        
        if (!title) {
          document.getElementById('newsfeedPostResult').innerHTML = '<div style="color: red; margin-top: 10px;">❌ Please enter a title for your post</div>';
          return;
        }
        
        if (!content || content.trim() === '') {
          document.getElementById('newsfeedPostResult').innerHTML = '<div style="color: red; margin-top: 10px;">❌ Please enter some content</div>';
          return;
        }
        
        const formData = new FormData(this);
        
        // Override the content with CKEditor content
        formData.set('content', content);
        
        // Collect links from the create form
        const createLinkRows = document.querySelectorAll('#createLinksContainer .link-row');
        const links = [];
        createLinkRows.forEach(row => {
          const urlInput = row.querySelector('[data-create-link-url]');
          const labelInput = row.querySelector('[data-create-link-label]');
          const url = urlInput ? urlInput.value.trim() : '';
          const label = labelInput ? labelInput.value.trim() : '';
          if (url && label) {
            links.push({ url, label });
          }
        });
        
        if (links.length > 0) {
          formData.append('links', JSON.stringify(links));
        }
        
        // Show progress overlay
        const overlay = document.getElementById('newsfeedUploadProgressOverlay');
        const bar = document.getElementById('newsfeedUploadProgressBar');
        const text = document.getElementById('newsfeedUploadProgressText');
        overlay.style.display = 'flex';
        bar.style.width = '0%';
        text.textContent = 'Preparing...';
        
        // Use XMLHttpRequest for progress tracking
        const xhr = new XMLHttpRequest();
        
        // Track upload progress
        xhr.upload.addEventListener('progress', function(e) {
          if (e.lengthComputable) {
            const percentComplete = Math.round((e.loaded / e.total) * 100);
            bar.style.width = percentComplete + '%';
            text.textContent = percentComplete + '%';
          }
        });
        
        // Handle completion
        xhr.addEventListener('load', function() {
          if (xhr.status === 200) {
            try {
              const data = JSON.parse(xhr.responseText);
              const resultDiv = document.getElementById('newsfeedPostResult');
              if (data.success) {
                resultDiv.innerHTML = '<div style="color: green; margin-top: 10px;">✅ Post shared successfully!</div>';
                
                // Clear title field
                document.getElementById('newsfeedPostTitle').value = '';
                
                // Clear CKEditor content
                if (window.newsfeedEditor) {
                  window.newsfeedEditor.setData('');
                }
                
                newsfeedPostForm.reset();
                document.getElementById('newsfeedSelectedFileName').textContent = '';
                
                // Clear the links container and reset toggle state
                document.getElementById('createLinksContainer').innerHTML = '';
                updateLinksToggleState();
                
                // Reload newsfeed to show new post
                setTimeout(() => {
                  loadNewsfeed(1); // Always go to page 1 to see new post
                  resultDiv.innerHTML = '';
                }, 1500);
              } else {
                resultDiv.innerHTML = '<div style="color: red; margin-top: 10px;">❌ ' + (data.message || 'Failed to create post') + '</div>';
              }
            } catch (e) {
              console.error('JSON parse error:', e);
              console.error('Response text:', xhr.responseText);
              document.getElementById('newsfeedPostResult').innerHTML = '<div style="color: red; margin-top: 10px;">❌ Error processing response. Please check file sizes and try again.</div>';
            }
          } else {
            console.error('HTTP error:', xhr.status, xhr.statusText);
            console.error('Response text:', xhr.responseText);
            document.getElementById('newsfeedPostResult').innerHTML = '<div style="color: red; margin-top: 10px;">❌ Upload failed (HTTP ' + xhr.status + '). Please try again.</div>';
          }
          
          // Hide progress overlay
          overlay.style.display = 'none';
        });
        
        // Handle errors
        xhr.addEventListener('error', function() {
          document.getElementById('newsfeedPostResult').innerHTML = '<div style="color: red; margin-top: 10px;">❌ Network error</div>';
          overlay.style.display = 'none';
        });
        
        // Send the request
        xhr.open('POST', 'create_post.php');
        xhr.send(formData);
      };
    }
  });

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function escapeAttr(value) {
    return escapeHtml(value);
  }

  function setNewsfeedSectionsMessage(message, isError = false) {
    const messageBox = document.getElementById('newsfeedSectionsMessage');
    if (!messageBox) return;
    messageBox.textContent = message || '';
    messageBox.style.color = isError ? '#c62828' : '#666';
  }

  function postNewsfeedSectionAction(action, payload = {}) {
    const formData = new FormData();
    formData.append('action', action);
    if (newsfeedEditMode) {
      formData.append('scope', 'mine');
    }

    Object.keys(payload).forEach(key => {
      if (payload[key] !== undefined && payload[key] !== null) {
        formData.append(key, payload[key]);
      }
    });

    return fetch('newsfeed_sections.php', {
      method: 'POST',
      body: formData
    }).then(async res => {
      const text = await res.text();
      try {
        return JSON.parse(text);
      } catch (error) {
        throw new Error(text.trim() || 'Unexpected response from newsfeed sections API');
      }
    });
  }

  function loadNewsfeedSections() {
    const scopeParam = newsfeedEditMode ? '&scope=mine' : '';
    fetch(`newsfeed_sections.php?action=list${scopeParam}`)
      .then(async res => {
        const text = await res.text();
        try {
          return JSON.parse(text);
        } catch (error) {
          throw new Error(text.trim() || 'Unexpected response from newsfeed sections API');
        }
      })
      .then(data => {
        renderNewsfeedSections(data.facility_cards || []);
      })
      .catch(error => {
        console.error('Error loading newsfeed sections:', error);
        const container = document.getElementById('facility-cards-container');
        if (container) {
          container.innerHTML = '<div class="facility-empty">Failed to load facility cards.</div>';
        }
      });
  }

  function renderNewsfeedSections(facilityCards) {
    const container = document.getElementById('facility-cards-container');
    if (!container) return;

    if (!facilityCards || facilityCards.length === 0) {
      container.innerHTML = '<div class="facility-empty">No facility sections available yet.</div>';
      return;
    }

    const openSectionIds = Array.from(container.querySelectorAll('.newsfeed-section-card.open'))
      .map(card => card.getAttribute('data-section-id'))
      .filter(Boolean);
    const hasOpenSection = openSectionIds.length > 0;

    container.innerHTML = `
      <div class="facility-card-grid">
        ${facilityCards.map(card => {
          const sections = Array.isArray(card.sections) ? card.sections : [];
          const canManage = newsfeedEditMode && !!card.can_manage;
          const sectionsMarkup = sections.length > 0 ? sections.map((section, index) => {
            const links = Array.isArray(section.links) ? section.links : [];
            const linksHtml = links.length > 0 ? links.map(link => `
              <div class="newsfeed-section-link">
                <div class="newsfeed-section-link-header">
                  <div style="font-weight: bold; color: var(--brand);">${escapeHtml(link.label)}</div>
                  ${canManage ? `
                  <div class="newsfeed-section-actions">
                    <button type="button" onclick="moveNewsfeedSectionLink(${link.link_id}, 'up')" title="Move link up">↑</button>
                    <button type="button" onclick="moveNewsfeedSectionLink(${link.link_id}, 'down')" title="Move link down">↓</button>
                    <button type="button" onclick="deleteNewsfeedSectionLink(${link.link_id})" title="Delete link">Delete</button>
                  </div>` : ''}
                </div>
                ${canManage ? `
                <div class="newsfeed-section-controls" style="margin-bottom: 10px;">
                  <input type="text" id="newsfeed-link-label-${link.link_id}" value="${escapeAttr(link.label)}" placeholder="Link label">
                  <input type="url" id="newsfeed-link-url-${link.link_id}" value="${escapeAttr(link.url)}" placeholder="Link URL">
                  <button type="button" onclick="saveNewsfeedSectionLink(${section.section_id}, ${link.link_id})">Save</button>
                </div>` : ''}
                <div class="newsfeed-section-link-meta">
                  <a href="${escapeAttr(link.url)}" target="_blank" rel="noopener noreferrer">Open link</a>
                </div>
              </div>
            `).join('') : '<div class="facility-empty">No links in this section yet.</div>';

            const isOpen = openSectionIds.includes(String(section.section_id));
            const toggleHint = isOpen ? 'Collapse' : 'Expand';
            const toggleChevron = isOpen ? '▾' : '▸';

            return `
              <div class="newsfeed-section-card ${isOpen ? 'open' : ''}" data-section-id="${section.section_id}">
                <button type="button" class="newsfeed-section-toggle" onclick="toggleNewsfeedSection(${section.section_id})" aria-expanded="${isOpen ? 'true' : 'false'}">
                  <span class="newsfeed-section-toggle-label">
                    <span class="newsfeed-section-toggle-title">${escapeHtml(section.title)}</span>
                    <span class="newsfeed-section-toggle-hint">${toggleHint}</span>
                  </span>
                  <span>${links.length} link${links.length === 1 ? '' : 's'}</span>
                  <span class="newsfeed-section-toggle-chevron" aria-hidden="true">${toggleChevron}</span>
                </button>
                <div class="newsfeed-section-body">
                  <div class="newsfeed-section-links">
                    ${linksHtml}
                  </div>
                  ${canManage ? `
                  <div class="newsfeed-section-controls" style="margin-bottom: 12px;">
                    <input type="text" id="newsfeed-section-title-${section.section_id}" value="${escapeAttr(section.title)}" placeholder="Section title">
                    <button type="button" onclick="saveNewsfeedSection(${section.section_id})">Save</button>
                    <button type="button" onclick="moveNewsfeedSection(${section.section_id}, 'up')" title="Move section up">↑</button>
                    <button type="button" onclick="moveNewsfeedSection(${section.section_id}, 'down')" title="Move section down">↓</button>
                    <button type="button" onclick="deleteNewsfeedSection(${section.section_id})" title="Delete section">Delete</button>
                  </div>
                  <form class="newsfeed-link-form" onsubmit="event.preventDefault(); addNewsfeedSectionLink(${section.section_id});">
                    <input type="text" id="newsfeed-link-label-add-${section.section_id}" placeholder="Link label" required>
                    <input type="url" id="newsfeed-link-url-add-${section.section_id}" placeholder="Link URL" required>
                    <button type="submit">Add Link</button>
                  </form>` : ''}
                </div>
              </div>
            `;
          }).join('') : '<div class="facility-empty">No sections yet for this facility.</div>';

          return `
            <div class="facility-card" data-facility="${escapeAttr(card.facility)}">
              <div class="facility-card-header">
                <div>
                  <h3 class="facility-card-title">${escapeHtml(card.facility)} Facility</h3>
                  <p class="facility-card-subtitle">${canManage ? 'Manage sections for your facility.' : 'Public view of facility sections.'}</p>
                </div>
                <div style="text-align: right; color: #64748b; font-size: 13px;">
                  <div>${sections.length} section${sections.length === 1 ? '' : 's'}</div>
                  ${canManage ? '<div style="color: var(--brand); font-weight: bold;">Editable</div>' : ''}
                </div>
              </div>
              <div class="facility-card-body">
                ${canManage ? `
                <form class="newsfeed-section-form" onsubmit="event.preventDefault(); addNewsfeedSection(this);" data-facility="${escapeAttr(card.facility)}" style="margin-bottom: 14px;">
                  <input type="text" name="section_title" placeholder="New section title" required>
                  <button type="submit">Add Section</button>
                </form>` : ''}
                <div class="newsfeed-section-links">
                  ${sectionsMarkup}
                </div>
              </div>
            </div>
          `;
        }).join('')}
      </div>
    `;
  }

  function toggleNewsfeedSection(sectionId) {
    const card = document.querySelector(`.newsfeed-section-card[data-section-id="${sectionId}"]`);
    if (card) {
      card.classList.toggle('open');
      const isOpen = card.classList.contains('open');
      const toggle = card.querySelector('.newsfeed-section-toggle');
      const hint = card.querySelector('.newsfeed-section-toggle-hint');
      const chevron = card.querySelector('.newsfeed-section-toggle-chevron');

      if (toggle) {
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      }

      if (hint) {
        hint.textContent = isOpen ? 'Collapse' : 'Expand';
      }

      if (chevron) {
        chevron.textContent = isOpen ? '▾' : '▸';
      }
    }
  }

  function saveNewsfeedSection(sectionId) {
    const titleInput = document.getElementById(`newsfeed-section-title-${sectionId}`);
    const title = titleInput ? titleInput.value.trim() : '';

    if (!title) {
      Swal.fire('Missing title', 'Please enter a section title.', 'warning');
      return;
    }

    postNewsfeedSectionAction('save_section', {
      section_id: sectionId,
      title: title
    }).then(data => {
      if (data.success) {
        setNewsfeedSectionsMessage(data.message || 'Section saved.');
        renderNewsfeedSections(data.facility_cards || []);
      } else {
        Swal.fire('Error', data.message || 'Failed to save section.', 'error');
      }
    }).catch(error => {
      Swal.fire('Error', error.message || 'Failed to save section.', 'error');
    });
  }

  function addNewsfeedSection(formEl) {
    const input = formEl ? formEl.querySelector('[name="section_title"]') : null;
    const title = input ? input.value.trim() : '';

    if (!title) {
      Swal.fire('Missing title', 'Please enter a section title.', 'warning');
      return;
    }

    postNewsfeedSectionAction('save_section', { title }).then(data => {
      if (data.success) {
        if (input) input.value = '';
        renderNewsfeedSections(data.facility_cards || []);
      } else {
        Swal.fire('Error', data.message || 'Failed to add section.', 'error');
      }
    }).catch(error => {
      Swal.fire('Error', error.message || 'Failed to add section.', 'error');
    });
  }

  function deleteNewsfeedSection(sectionId) {
    Swal.fire({
      title: 'Delete section?',
      text: 'This will remove the section and all links inside it.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Delete'
    }).then(result => {
      if (!result.isConfirmed) return;

      postNewsfeedSectionAction('delete_section', { section_id: sectionId }).then(data => {
        if (data.success) {
          setNewsfeedSectionsMessage(data.message || 'Section deleted.');
          renderNewsfeedSections(data.facility_cards || []);
        } else {
          Swal.fire('Error', data.message || 'Failed to delete section.', 'error');
        }
      }).catch(error => {
        Swal.fire('Error', error.message || 'Failed to delete section.', 'error');
      });
    });
  }

  function moveNewsfeedSection(sectionId, direction) {
    postNewsfeedSectionAction('move_section', {
      section_id: sectionId,
      direction: direction
    }).then(data => {
      if (data.success) {
        renderNewsfeedSections(data.facility_cards || []);
      } else {
        Swal.fire('Error', data.message || 'Failed to move section.', 'error');
      }
    }).catch(error => {
      Swal.fire('Error', error.message || 'Failed to move section.', 'error');
    });
  }

  function addNewsfeedSectionLink(sectionId) {
    const labelInput = document.getElementById(`newsfeed-link-label-add-${sectionId}`);
    const urlInput = document.getElementById(`newsfeed-link-url-add-${sectionId}`);
    const label = labelInput ? labelInput.value.trim() : '';
    const url = urlInput ? urlInput.value.trim() : '';

    if (!label || !url) {
      Swal.fire('Missing values', 'Please enter both a label and a URL.', 'warning');
      return;
    }

    postNewsfeedSectionAction('save_link', {
      section_id: sectionId,
      label: label,
      url: url
    }).then(data => {
      if (data.success) {
        if (labelInput) labelInput.value = '';
        if (urlInput) urlInput.value = '';
        setNewsfeedSectionsMessage(data.message || 'Link added.');
        renderNewsfeedSections(data.facility_cards || []);
      } else {
        Swal.fire('Error', data.message || 'Failed to add link.', 'error');
      }
    }).catch(error => {
      Swal.fire('Error', error.message || 'Failed to add link.', 'error');
    });
  }

  function saveNewsfeedSectionLink(sectionId, linkId) {
    const labelInput = document.getElementById(`newsfeed-link-label-${linkId}`);
    const urlInput = document.getElementById(`newsfeed-link-url-${linkId}`);
    const label = labelInput ? labelInput.value.trim() : '';
    const url = urlInput ? urlInput.value.trim() : '';

    if (!label || !url) {
      Swal.fire('Missing values', 'Please enter both a label and a URL.', 'warning');
      return;
    }

    postNewsfeedSectionAction('save_link', {
      section_id: sectionId,
      link_id: linkId,
      label: label,
      url: url
    }).then(data => {
      if (data.success) {
        setNewsfeedSectionsMessage(data.message || 'Link saved.');
        renderNewsfeedSections(data.facility_cards || []);
      } else {
        Swal.fire('Error', data.message || 'Failed to save link.', 'error');
      }
    }).catch(error => {
      Swal.fire('Error', error.message || 'Failed to save link.', 'error');
    });
  }

  function deleteNewsfeedSectionLink(linkId) {
    Swal.fire({
      title: 'Delete link?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Delete'
    }).then(result => {
      if (!result.isConfirmed) return;

      postNewsfeedSectionAction('delete_link', { link_id: linkId }).then(data => {
        if (data.success) {
          setNewsfeedSectionsMessage(data.message || 'Link deleted.');
          renderNewsfeedSections(data.facility_cards || []);
        } else {
          Swal.fire('Error', data.message || 'Failed to delete link.', 'error');
        }
      }).catch(error => {
        Swal.fire('Error', error.message || 'Failed to delete link.', 'error');
      });
    });
  }

  function moveNewsfeedSectionLink(linkId, direction) {
    postNewsfeedSectionAction('move_link', {
      link_id: linkId,
      direction: direction
    }).then(data => {
      if (data.success) {
        renderNewsfeedSections(data.facility_cards || []);
      } else {
        Swal.fire('Error', data.message || 'Failed to move link.', 'error');
      }
    }).catch(error => {
      Swal.fire('Error', error.message || 'Failed to move link.', 'error');
    });
  }

  // Notification functions
  function loadNotifications() {
    fetch('get_notifications.php')
      .then(res => res.json())
      .then(data => {
        const badge = document.getElementById('notification-badge');
        const unreadCount = data.unread_count || 0;
        
        if (unreadCount > 0) {
          badge.textContent = unreadCount;
          badge.style.display = 'flex';
        } else {
          badge.style.display = 'none';
        }
        
        // Store notifications for dropdown
        window.notifications = data.notifications || [];
      })
      .catch(err => console.error('Error loading notifications:', err));
  }

  function toggleNotifications() {
    const dropdown = document.getElementById('notification-dropdown');
    const isVisible = dropdown.style.display === 'block';
    
    if (isVisible) {
      dropdown.style.display = 'none';
    } else {
      dropdown.style.display = 'block';
      displayNotifications();
    }
  }

  function displayNotifications() {
    const list = document.getElementById('notification-list');
    const notifications = window.notifications || [];
    
    if (notifications.length === 0) {
      list.innerHTML = '<div style="padding: 20px; text-align: center; color: #666;">No notifications yet</div>';
      return;
    }
    
    list.innerHTML = notifications.map(notification => {
      const time = new Date(notification.created_at).toLocaleDateString('en-US', {
        month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
      });
      
      return `
        <div class="notification-item ${notification.is_read ? '' : 'unread'}" 
             onclick="markAsRead(${notification.notification_id})">
          <div class="notification-message">${notification.message}</div>
          <div class="notification-time">${time}</div>
        </div>
      `;
    }).join('');
  }

  function markAsRead(notificationId) {
    fetch('mark_notification_read.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ notification_id: notificationId })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        loadNotifications();
      }
    });
  }

  function markAllAsRead() {
    fetch('mark_all_notifications_read.php', { method: 'POST' })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          loadNotifications();
          document.getElementById('notification-dropdown').style.display = 'none';
        }
      });
  }

  // Close notification dropdown when clicking outside
  document.addEventListener('click', function(e) {
    const bell = document.getElementById('notification-bell');
    const dropdown = document.getElementById('notification-dropdown');
    
    if (bell && dropdown && !bell.contains(e.target) && !dropdown.contains(e.target)) {
      dropdown.style.display = 'none';
    }
  });

  function createPostHTML(post) {
    const date = new Date(post.created_at).toLocaleDateString('en-US', {
      year: 'numeric', month: 'short', day: 'numeric', 
      hour: '2-digit', minute: '2-digit'
    });

    // Check if post is new (less than 24 hours old)
    const postDate = new Date(post.created_at);
    const now = new Date();
    const hoursDiff = (now - postDate) / (1000 * 60 * 60);
    const isNew = hoursDiff < 24;
    const newIndicator = isNew ? '<span class="new-post-indicator">NEW</span>' : '';

    let fileSection = '';
    if (post.post_type === 'file' && post.attachments && post.attachments.length > 0) {
      // Multiple attachments
      const mediaAttachments = post.attachments.filter(attachment => {
        const fileType = attachment.file_type.toLowerCase();
        return ['png', 'jpg', 'jpeg', 'gif', 'bmp', 'svg', 'webp', 'mp4', 'webm', 'ogg', 'mov', 'mkv', 'avi', 'wmv', 'flv'].includes(fileType);
      });

      if (mediaAttachments.length > 1) {
        // Create slider for multiple media attachments
        const sliderId = `media-slider-${post.post_id}`;
        const slideElements = mediaAttachments.map((attachment, index) =>
          `<div class="slider-slide ${index === 0 ? 'active' : ''}">${createFileDisplay(attachment, post.post_id)}</div>`
        ).join('');

        // Create thumbnail elements
        const thumbnailElements = mediaAttachments.map((attachment, index) => {
          const fileType = attachment.file_type.toLowerCase();
          const fileUrl = attachment.file_url;
          const isGoogleDrive = fileUrl.includes('drive.google.com');

          // Helper function to get thumbnail URL
          function getThumbnailUrl(url, type) {
            if (isGoogleDrive) {
              const fileIdMatch = url.match(/\/d\/([a-zA-Z0-9-_]+)/);
              if (fileIdMatch) {
                return `https://drive.google.com/thumbnail?sz=w100&id=${fileIdMatch[1]}`;
              }
            }
            return url;
          }

          let thumbnailSrc = '';
          if (['png', 'jpg', 'jpeg', 'gif', 'bmp', 'svg', 'webp'].includes(fileType)) {
            thumbnailSrc = getThumbnailUrl(fileUrl, 'image');
          }

          return `
            <div class="slider-thumbnail ${index === 0 ? 'active' : ''}" onclick="goToSlide('${sliderId}', ${index})">
              ${thumbnailSrc ? `<img src="${thumbnailSrc}" alt="Thumbnail ${index + 1}" onerror="this.style.display='none'">` : ''}
              <div class="thumbnail-overlay">
                <i class="fas ${['png', 'jpg', 'jpeg', 'gif', 'bmp', 'svg', 'webp'].includes(fileType) ? 'fa-image' : 'fa-video'}"></i>
              </div>
            </div>
          `;
        }).join('');

        fileSection = `
          <div class="media-slider-container" id="${sliderId}">
            <div class="media-slider">
              ${slideElements}
            </div>
            <button class="slider-nav slider-prev" onclick="changeSlide('${sliderId}', -1)">
              <i class="fas fa-chevron-left"></i>
            </button>
            <button class="slider-nav slider-next" onclick="changeSlide('${sliderId}', 1)">
              <i class="fas fa-chevron-right"></i>
            </button>
            <div class="slider-thumbnails">
              ${thumbnailElements}
            </div>
          </div>
        `;

        // Handle non-media attachments separately
        const nonMediaAttachments = post.attachments.filter(attachment => {
          const fileType = attachment.file_type.toLowerCase();
          return !['png', 'jpg', 'jpeg', 'gif', 'bmp', 'svg', 'webp', 'mp4', 'webm', 'ogg', 'mov', 'mkv', 'avi', 'wmv', 'flv'].includes(fileType);
        });

        if (nonMediaAttachments.length > 0) {
          const nonMediaElements = nonMediaAttachments.map(attachment => createFileDisplay(attachment, post.post_id)).join('');
          fileSection += `<div class="post-attachments">${nonMediaElements}</div>`;
        }
      } else {
        // Single attachment or mixed types - use regular display
        const attachmentElements = post.attachments.map(attachment => createFileDisplay(attachment, post.post_id)).join('');
        fileSection = `<div class="post-attachments">${attachmentElements}</div>`;
      }
    } else if (post.post_type === 'file' && post.file_name) {
      // Single attachment (backward compatibility)
      fileSection = createFileDisplay(post, post.post_id);
    }

    // Create links section
    let linksSection = '';
    if (post.links) {
      try {
        const links = JSON.parse(post.links);
        if (links && links.length > 0) {
          const linksList = links.map(link => 
            `<div class="post-link">
              <a href="${link.url}" target="_blank" rel="noopener noreferrer">
                <i class="fas fa-external-link-alt"></i> ${link.label}
              </a>
            </div>`
          ).join('');
          
          linksSection = `
            <div class="post-links">
              <div class="links-header">
                <i class="fas fa-link"></i> Links:
              </div>
              ${linksList}
            </div>
          `;
        }
      } catch (e) {
        console.error('Error parsing post links:', e);
      }
    }

    const reactions = ['like', 'love', 'celebrate', 'insightful'];
    const reactionIcons = {
      like: 'fa-thumbs-up',
      love: 'fa-heart', 
      celebrate: 'fa-party-horn',
      insightful: 'fa-lightbulb'
    };

    const reactionButtons = reactions.map(type => {
      const count = post.reactions && post.reactions[type] ? post.reactions[type].count : 0;
      const isActive = post.reactions && post.reactions[type] ? post.reactions[type].user_reacted : false;
      
      return `
        <div class="reaction-btn ${isActive ? 'active' : ''}" 
             ${window.USER_ROLE ? `onclick="toggleReaction(${post.post_id}, '${type}', this)"` : 'onclick="showLoginMessage()"'}>
          <i class="fas ${reactionIcons[type]}"></i>
          <span class="reaction-count">${count}</span>
        </div>
      `;
    }).join('');

    // Check if current user owns this post
    const isOwner = window.USER_ID && post.user_id == window.USER_ID;
    const postActions = isOwner ? `
      <div class="post-actions">
        <button class="action-btn edit-btn" onclick="editPost(${post.post_id}, this)" title="Edit post">
          <i class="fas fa-edit"></i>
        </button>
        <button class="action-btn delete-btn" onclick="deletePost(${post.post_id}, this)" title="Delete post">
          <i class="fas fa-trash"></i>
        </button>
      </div>
    ` : '';

    return `
      <div class="post-card ${isNew ? 'new-post' : ''}" data-post-id="${post.post_id}">
        <div class="post-header">
          <div class="post-author">${post.author_facility}</div>
          <div class="post-date">${date} ${newIndicator}</div>
          <div class="post-actions">
            <button class="action-btn copy-link-btn" onclick="copyPostLink(${post.post_id})" title="Copy post link">
              <i class="fas fa-link"></i>
            </button>
            ${postActions}
          </div>
        </div>
        <div class="post-title" style="font-size: 18px; font-weight: bold; color: var(--brand); margin-bottom: 10px; line-height: 1.3;">${post.title}</div>
        <div class="post-content" id="post-content-${post.post_id}">${post.content ? post.content : ''}</div>
        ${fileSection}
        ${linksSection}
        ${window.USER_ROLE ? `<div class="post-reactions">${reactionButtons}</div>` : 
          `<div class="post-reactions-public">
            <small style="color: #666; font-style: italic;">
              <i class="fas fa-info-circle"></i> <a href="login.html" style="color: #4CAF50;">Login</a> to react to posts
            </small>
           </div>`}
      </div>
    `;
  }

  function getFileIcon(fileType) {
    const type = fileType.toLowerCase();
    if (type === 'pdf') return 'fa-file-pdf';
    if (['doc', 'docx'].includes(type)) return 'fa-file-word';
    if (['xls', 'xlsx'].includes(type)) return 'fa-file-excel';
    if (['ppt', 'pptx'].includes(type)) return 'fa-file-powerpoint';
    if (['png', 'jpg', 'jpeg', 'gif'].includes(type)) return 'fa-file-image';
    if (['mp4', 'avi', 'mov'].includes(type)) return 'fa-file-video';
    if (['mp3', 'wav'].includes(type)) return 'fa-file-audio';
    return 'fa-file';
  }

  function createFileDisplay(attachment, postId) {
    const fileType = attachment.file_type.toLowerCase();
    const fileName = attachment.file_name;
    const fileUrl = attachment.file_url;
    const fileIcon = getFileIcon(attachment.file_type);
    const downloadCount = attachment.download_count || 0;
    const downloadCounterHtml = `<span class="download-counter"><i class="fas fa-download"></i> ${downloadCount}</span>`;

    // Check if this is a Google Drive file
    const isGoogleDrive = fileUrl.includes('drive.google.com');

    // Helper function to convert Google Drive sharing URL to direct/preview URLs
    function getGoogleDriveUrls(url) {
      const fileIdMatch = url.match(/\/d\/([a-zA-Z0-9-_]+)/);
      if (!fileIdMatch) return { preview: url, download: url };

      const fileId = fileIdMatch[1];
      return {
        preview: `https://drive.google.com/file/d/${fileId}/preview`,
        download: `https://drive.google.com/uc?export=download&id=${fileId}`,
        direct: `https://drive.google.com/uc?export=view&id=${fileId}`,
        thumbnail: `https://drive.google.com/thumbnail?sz=w1000&id=${fileId}`
      };
    }

    // Embeddable image formats
    if (['png', 'jpg', 'jpeg', 'gif', 'bmp', 'svg', 'webp'].includes(fileType)) {
      const imageUrl = isGoogleDrive ? getGoogleDriveUrls(fileUrl).thumbnail : fileUrl;
      return `
        <div class="embedded-media">
          <img src="${imageUrl}" alt="${fileName}" class="embedded-image"
               onclick="openImageModal('${imageUrl}', '${fileName}')"
               style="cursor: pointer;" title="Click to enlarge"
               onerror="handleImageError(this, '${getGoogleDriveUrls(fileUrl).preview}')">
          <div class="media-caption">
            📸 ${fileName}
            <a href="#" onclick="trackAndDownload(${postId}, event)" class="download-link">
              <i class="fas fa-download"></i> Download
            </a>
            ${downloadCounterHtml}
          </div>
        </div>
      `;
    }

    // Embeddable video formats
    if (['mp4', 'webm', 'ogg', 'mov', 'mkv', 'avi', 'wmv', 'flv'].includes(fileType)) {
      if (isGoogleDrive) {
        // For Google Drive videos, use preview embed
        const driveUrls = getGoogleDriveUrls(fileUrl);
        return `
          <div class="embedded-media">
            <iframe src="${driveUrls.preview}" class="embedded-video-iframe" title="${fileName}" allowfullscreen
                    onerror="handleVideoEmbedError(this, '${driveUrls.preview}', ${postId})"></iframe>
            <div class="media-caption">
              🎬 ${fileName}
              <a href="${driveUrls.download}" target="_blank" class="download-link">
                <i class="fas fa-external-link-alt"></i> Open in Drive
              </a>
              <a href="#" onclick="trackAndDownload(${postId}, event)" class="download-link">
                <i class="fas fa-download"></i> Download
              </a>
              ${downloadCounterHtml}
            </div>
          </div>
        `;
      } else {
        // Determine correct MIME type for video
        let mimeType = `video/${fileType}`;
        if (fileType === 'mov') {
          mimeType = 'video/quicktime';
        } else if (fileType === 'mkv') {
          mimeType = 'video/x-matroska';
        } else if (fileType === 'avi') {
          mimeType = 'video/x-msvideo';
        } else if (fileType === 'wmv') {
          mimeType = 'video/x-ms-wmv';
        } else if (fileType === 'flv') {
          mimeType = 'video/x-flv';
        }

        // Check if video format has limited browser support
        const limitedSupportFormats = ['mov', 'mkv', 'avi', 'wmv', 'flv'];
        const hasLimitedSupport = limitedSupportFormats.includes(fileType);
        const browserWarning = hasLimitedSupport ? `<div style="color: #ff9800; font-size: 12px; margin-bottom: 5px;"><i class="fas fa-exclamation-triangle"></i> ${fileType.toUpperCase()} files may contain unsupported codecs. If video doesn't play, <a href="#" onclick="trackAndDownload(${postId}, event)" style="color: #2196F3;">download</a> to play in a compatible media player.</div>` : '';

        return `
          <div class="embedded-media">
            ${browserWarning}
            <video controls class="embedded-video" preload="metadata" onerror="handleVideoError(this, '${postId}', '${fileType}')">
              <source src="${fileUrl}" type="${mimeType}">
              Your browser does not support the video tag.
            </video>
            <div class="media-caption">
              🎬 ${fileName}
              <a href="#" onclick="trackAndDownload(${postId}, event)" class="download-link">
                <i class="fas fa-download"></i> Download
              </a>
              ${downloadCounterHtml}
            </div>
          </div>
        `;
      }
    }

    // Embeddable audio formats
    if (['mp3', 'wav', 'ogg', 'm4a', 'aac'].includes(fileType)) {
      if (isGoogleDrive) {
        // For Google Drive audio, provide download link since embedding might not work well
        const driveUrls = getGoogleDriveUrls(fileUrl);
        return `
          <div class="post-file google-drive-file">
            <i class="fas fa-music"></i>
            <div class="post-file-info">
              <div class="post-file-name">${fileName}</div>
              <div class="post-file-size">${fileType.toUpperCase()} (Google Drive)</div>
            </div>
            <div style="margin-left: auto; display: flex; align-items: center; gap: 10px;">
              <a href="${driveUrls.preview}" target="_blank" class="drive-preview-link">
                <button style="padding: 5px 10px; font-size: 12px; background: #4285f4; color: white; border: none; border-radius: 4px;">
                  <i class="fas fa-play"></i> Preview
                </button>
              </a>
              <a href="#" onclick="trackAndDownload(${postId}, event)">
                <button style="padding: 5px 10px; font-size: 12px;">
                  <i class="fas fa-download"></i> Download
                </button>
              </a>
              ${downloadCounterHtml}
            </div>
          </div>
        `;
      } else {
        return `
          <div class="embedded-media">
            <audio controls class="embedded-audio">
              <source src="${fileUrl}" type="audio/${fileType}">
              Your browser does not support the audio tag.
            </audio>
            <div class="media-caption">
              🎵 ${fileName}
              <a href="#" onclick="trackAndDownload(${postId}, event)" class="download-link">
                <i class="fas fa-download"></i> Download
              </a>
              ${downloadCounterHtml}
            </div>
          </div>
        `;
      }
    }

    // Embeddable PDF
    if (fileType === 'pdf') {
      if (isGoogleDrive) {
        const driveUrls = getGoogleDriveUrls(fileUrl);
        return `
          <div class="embedded-media">
            <iframe src="${driveUrls.preview}" class="embedded-pdf" title="${fileName}" 
                    onerror="handlePdfEmbedError(this, '${driveUrls.preview}', ${postId})"></iframe>
            <div class="media-caption">
              📄 ${fileName}
              <a href="${driveUrls.preview}" target="_blank" class="download-link">
                <i class="fas fa-external-link-alt"></i> Open in Drive
              </a>
              <a href="#" onclick="trackAndDownload(${postId}, event)" class="download-link">
                <i class="fas fa-download"></i> Download
              </a>
              ${downloadCounterHtml}
            </div>
          </div>
        `;
      } else {
        return `
          <div class="embedded-media">
            <iframe src="${fileUrl}" class="embedded-pdf" title="${fileName}"></iframe>
            <div class="media-caption">
              📄 ${fileName}
              <a href="${fileUrl}" target="_blank" class="download-link">
                <i class="fas fa-external-link-alt"></i> Open in new tab
              </a>
              <a href="#" onclick="trackAndDownload(${postId}, event)" class="download-link">
                <i class="fas fa-download"></i> Download
              </a>
              ${downloadCounterHtml}
            </div>
          </div>
        `;
      }
    }

    // Google Docs, Sheets, Slides
    if (isGoogleDrive && ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'].includes(fileType)) {
      const driveUrls = getGoogleDriveUrls(fileUrl);
      const fileEmoji = fileType.startsWith('doc') ? '📄' : fileType.startsWith('xls') ? '📊' : '📈';

      return `
        <div class="post-file google-drive-file">
          <i class="fas ${fileIcon}"></i>
          <div class="post-file-info">
            <div class="post-file-name">${fileEmoji} ${fileName}</div>
            <div class="post-file-size">${fileType.toUpperCase()} (Google Drive)</div>
          </div>
          <div style="margin-left: auto; display: flex; align-items: center; gap: 10px;">
            <a href="${driveUrls.preview}" target="_blank" class="drive-preview-link">
              <button style="padding: 5px 10px; font-size: 12px; background: #4285f4; color: white; border: none; border-radius: 4px;">
                <i class="fas fa-eye"></i> Preview
              </button>
            </a>
            <a href="#" onclick="trackAndDownload(${postId}, event)">
              <button style="padding: 5px 10px; font-size: 12px;">
                <i class="fas fa-download"></i> Download
              </button>
            </a>
            ${downloadCounterHtml}
          </div>
        </div>
      `;
    }

    // Fall back to download link for other file types
    const driveIndicator = isGoogleDrive ? ' (Google Drive)' : '';
    return `
      <div class="post-file${isGoogleDrive ? ' google-drive-file' : ''}">
        <i class="fas ${fileIcon}"></i>
        <div class="post-file-info">
          <div class="post-file-name">${fileName}</div>
          <div class="post-file-size">${fileType.toUpperCase()}${driveIndicator}</div>
        </div>
        <div style="margin-left: auto; display: flex; align-items: center;">
          ${isGoogleDrive ? `<a href="${getGoogleDriveUrls(fileUrl).preview}" target="_blank" class="drive-preview-link" style="margin-right: 10px;">
            <button style="padding: 5px 10px; font-size: 12px; background: #4285f4; color: white; border: none; border-radius: 4px;">
              <i class="fas fa-eye"></i> Preview
            </button>
          </a>` : ''}
          <a href="#" onclick="trackAndDownload(${postId}, event)">
            <button style="padding: 5px 10px; font-size: 12px;">
              <i class="fas fa-download"></i> Download
            </button>
          </a>
          ${downloadCounterHtml}
        </div>
      </div>
    `;
  }



  function handlePdfEmbedError(iframeElement, fallbackUrl, postId) {
    // If the PDF iframe fails to load, replace it with a message
    const container = iframeElement.parentElement;
    const caption = container.querySelector('.media-caption');
    
    // Hide the iframe
    iframeElement.style.display = 'none';
    
    // Add error message to caption
    const errorMsg = document.createElement('div');
    errorMsg.style.cssText = 'color: #f44336; font-size: 12px; margin-top: 5px;';
    errorMsg.innerHTML = '<i class="fas fa-exclamation-triangle"></i> PDF preview failed to load. ' +
      '<a href="' + fallbackUrl + '" target="_blank" style="color: #2196F3;">Open in new tab</a> or ' +
      '<a href="#" onclick="trackAndDownload(' + postId + ', event)" style="color: #2196F3;">Download file</a>';
    caption.appendChild(errorMsg);
  }

  function handleVideoEmbedError(iframeElement, fallbackUrl, postId) {
    // If the video iframe fails to load, replace it with a message
    const container = iframeElement.parentElement;
    const caption = container.querySelector('.media-caption');
    
    // Hide the iframe
    iframeElement.style.display = 'none';
    
    // Add error message to caption
    const errorMsg = document.createElement('div');
    errorMsg.style.cssText = 'color: #f44336; font-size: 12px; margin-top: 5px;';
    errorMsg.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Video preview failed to load. ' +
      '<a href="' + fallbackUrl + '" target="_blank" style="color: #2196F3;">Open in new tab</a> or ' +
      '<a href="#" onclick="trackAndDownload(' + postId + ', event)" style="color: #2196F3;">Download file</a>';
    caption.appendChild(errorMsg);
  }  function trackAndDownload(postId, event) {
    event.preventDefault();
    
    fetch(`track_download.php?post_id=${postId}`)
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          // Update the download counter in the UI
          const counterElement = event.target.closest('.media-caption, .post-file').querySelector('.download-counter');
          if (counterElement) {
            counterElement.innerHTML = `<i class="fas fa-download"></i> ${data.total_downloads}`;
          }
          
          // Trigger the actual download
          const link = document.createElement('a');
          link.href = data.file_url;
          link.download = data.file_name;
          document.body.appendChild(link);
          link.click();
          document.body.removeChild(link);
        } else {
          console.error('Download tracking failed:', data.error);
          // Still allow download even if tracking fails
          window.open(event.target.href, '_blank');
        }
      })
      .catch(err => {
        console.error('Download tracking error:', err);
        // Fallback: still allow download
        window.open(event.target.href, '_blank');
      });
  }

  function toggleReaction(postId, reactionType, btnElement) {
    // Check if user is logged in
    if (!window.USER_ROLE) {
      showLoginMessage();
      return;
    }
    
    fetch('toggle_reaction.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ post_id: postId, reaction_type: reactionType })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        const countSpan = btnElement.querySelector('.reaction-count');
        countSpan.textContent = data.count;
        
        if (data.user_reacted) {
          btnElement.classList.add('active');
        } else {
          btnElement.classList.remove('active');
        }
      }
    })
    .catch(err => console.error('Reaction error:', err));
  }

  function showLoginMessage() {
    Swal.fire({
      title: 'Login Required',
      text: 'Please login to interact with posts and access all features.',
      icon: 'info',
      confirmButtonText: 'Go to Login',
      showCancelButton: true,
      cancelButtonText: 'Continue Browsing'
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = 'login.html';
      }
    });
  }

  // Media slider functions
  function changeSlide(sliderId, direction) {
    const slider = document.getElementById(sliderId);
    const slides = slider.querySelectorAll('.slider-slide');
    const indicators = slider.querySelectorAll('.slider-indicator');
    const thumbnails = slider.querySelectorAll('.slider-thumbnail');
    let activeIndex = Array.from(slides).findIndex(slide => slide.classList.contains('active'));

    // Remove active class from current slide, indicator, and thumbnail
    slides[activeIndex].classList.remove('active');
    if (indicators[activeIndex]) indicators[activeIndex].classList.remove('active');
    if (thumbnails[activeIndex]) thumbnails[activeIndex].classList.remove('active');

    // Calculate new index
    activeIndex = (activeIndex + direction + slides.length) % slides.length;

    // Add active class to new slide, indicator, and thumbnail
    slides[activeIndex].classList.add('active');
    if (indicators[activeIndex]) indicators[activeIndex].classList.add('active');
    if (thumbnails[activeIndex]) thumbnails[activeIndex].classList.add('active');
  }

  function goToSlide(sliderId, slideIndex) {
    const slider = document.getElementById(sliderId);
    const slides = slider.querySelectorAll('.slider-slide');
    const indicators = slider.querySelectorAll('.slider-indicator');
    const thumbnails = slider.querySelectorAll('.slider-thumbnail');

    // Remove active class from all slides, indicators, and thumbnails
    slides.forEach(slide => slide.classList.remove('active'));
    indicators.forEach(indicator => indicator.classList.remove('active'));
    thumbnails.forEach(thumbnail => thumbnail.classList.remove('active'));

    // Add active class to target slide, indicator, and thumbnail
    slides[slideIndex].classList.add('active');
    if (indicators[slideIndex]) indicators[slideIndex].classList.add('active');
    if (thumbnails[slideIndex]) thumbnails[slideIndex].classList.add('active');
  }

  // Search functions
  function performSearch() {
    const searchTerm = document.getElementById('newsfeedSearch').value.trim();
    loadNewsfeed(1, searchTerm);
  }

  function clearSearch() {
    document.getElementById('newsfeedSearch').value = '';
    loadNewsfeed(1);
  }

  // Add Enter key listener for search
  document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('newsfeedSearch');
    if (searchInput) {
      searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
          performSearch();
        }
      });
    }
    fetch("session.php")
      .then(r => r.json())
      .then(user => {
        if (!user || user.error) {
          // Public user - show newsfeed by default
          console.log('Public user detected');
          window.USER_ROLE = null;
          window.USER_FACILITY = null;
          window.CAN_CREATE = false;
          window.CAN_DELETE = false;
          window.CAN_DELETE_FILES = false;
          window.CAN_REPORT = false;
          
          // Show only newsfeed tab for public users
          switchTab(null, 'newsfeed-tab');
          
          // Handle URL parameters for direct linking (public users)
          const urlParams = new URLSearchParams(window.location.search);
          const postParam = urlParams.get('post');
          const tabParam = urlParams.get('tab');

          if (tabParam === 'newsfeed' && postParam) {
            // Wait for newsfeed to load, then scroll to the specific post
            setTimeout(() => {
              const postElement = document.querySelector(`[data-post-id="${postParam}"]`);
              if (postElement) {
                postElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
                // Highlight the post briefly
                postElement.style.boxShadow = '0 0 20px rgba(76, 175, 80, 0.5)';
                setTimeout(() => {
                  postElement.style.boxShadow = '';
                }, 3000);
              }
            }, 1000); // Wait 1 second for newsfeed to load
          }
          
          // Load introduction stats for public users
          loadIntroductionStats();
          return;
        }

        // Logged in user
        window.USER_ID = user.user_id;
        window.USER_ROLE = user.role;
        window.USER_FACILITY = user.facility;
        window.CAN_CREATE = user.can_create_folder;
        window.CAN_DELETE = user.can_delete;
        window.CAN_DELETE_FILES = user.can_delete_files;
        window.CAN_GENERATE = user.can_generate_report;
        window.USER_FOLDER_ID = user.folder_id;

        if (window.USER_ROLE !== "Admin" && window.USER_ROLE !== "Head") {
          const cfs = document.getElementById("create-folder-section");
          if (cfs) cfs.style.display = "none";
        }

        // Initialize tab (no click required)
        const selectedTab = newsfeedEditMode ? "newsfeed-tab" : (localStorage.getItem("selectedTab") || "view-folder-tab");
        const tabButton = document.querySelector(`.tab[onclick*="${selectedTab}"]`);
        if (tabButton) {
          tabButton.classList.add("active");
        } else {
          const firstTab = document.querySelector(".tab");
          if (firstTab) firstTab.classList.add("active");
        }
        document.getElementById(selectedTab).classList.add("active");

        if (selectedTab === "view-folder-tab") {
          loadFolderThumbnails();
          // Apply saved view preference
          const savedView = localStorage.getItem('folderViewPreference') || 'grid';
          setTimeout(() => setFolderView(savedView), 100); // Small delay to ensure DOM is ready
        }
        if (selectedTab === "newsfeed-tab") {
          loadNewsfeed(1);
        }

        // Handle URL parameters for direct linking
        const urlParams = new URLSearchParams(window.location.search);
        const postParam = urlParams.get('post');
        const tabParam = urlParams.get('tab');

        if (tabParam === 'newsfeed') {
          switchTab(null, 'newsfeed-tab');
          if (postParam) {
            // Wait for newsfeed to load, then scroll to the specific post
            setTimeout(() => {
              const postElement = document.querySelector(`[data-post-id="${postParam}"]`);
              if (postElement) {
                postElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
                // Highlight the post briefly
                postElement.style.boxShadow = '0 0 20px rgba(76, 175, 80, 0.5)';
                setTimeout(() => {
                  postElement.style.boxShadow = '';
                }, 3000);
              }
            }, 1000); // Wait 1 second for newsfeed to load
          }
        }

        // Load introduction stats for logged-in users
        loadIntroductionStats();
      })
      .catch(() => {
        // If session check fails, bail to login for safety
        window.location.href = "login.php";
      });
  });

  // Function to load introduction section statistics
  function loadIntroductionStats() {
    // Load total facility cards count
    fetch('newsfeed_sections.php?action=list')
      .then(res => res.json())
      .then(data => {
        const totalFacilities = (data.facility_cards || []).length;
        document.getElementById('total-posts').textContent = totalFacilities.toLocaleString();
      })
      .catch(err => {
        console.error('Error loading facility stats:', err);
        document.getElementById('total-posts').textContent = '0';
      });

    // Load active users count (if available)
    fetch('get_active_users_count.php')
      .then(res => res.json())
      .then(data => {
        const activeUsers = data.count || 0;
        document.getElementById('active-users').textContent = activeUsers.toLocaleString();
      })
      .catch(err => {
        console.error('Error loading user stats:', err);
        document.getElementById('active-users').textContent = '0';
      });
  }

  let pieChart;

  function generateReport() {
    const chartCanvas = document.getElementById("report-chart");
    const chartTotal = document.getElementById("report-total");
    const loading = document.getElementById("loadingModal");
    loading.style.display = "flex";

    fetch(`${APPS_SCRIPT_URL}action=getAllFiles&parentFolderId=${window.USER_FOLDER_ID}`)
      .then(res => res.json())
      .then(counts => {
        loading.style.display = "none";

        const labels = [];
        const data = [];
        let total = 0;

        Object.keys(counts || {}).forEach(type => {
          const count = counts[type];
          if (count > 0) {
            labels.push(type);
            data.push(count);
            total += count;
          }
        });

        if (labels.length === 0) {
          Swal.fire("No data", "No files found for report.", "info");
          return;
        }

        if (pieChart) pieChart.destroy();

        const ctx = chartCanvas.getContext("2d");
        pieChart = new Chart(ctx, {
          type: "bar",
          data: {
            labels: labels,
            datasets: [{
              data: data,
              backgroundColor: ["#4CAF50", "#FF9800", "#2196F3", "#9C27B0", "#F44336", "#00BCD4", "#FFC107"]
            }]
          },
          options: {
            responsive: false,
            plugins: {
              title: {
                display: true,
                text: `${window.USER_FACILITY} File Type Distribution`,
                font: {
                  size: 16,
                  weight: 'bold'
                },
                padding: {
                  top: 10,
                  bottom: 20
                }
              },
              legend: {
                position: "bottom",
                labels: {
                  generateLabels: function (chart) {
                    const d = chart.data;
                    return d.labels.map((label, i) => {
                      const v = d.datasets[0].data[i];
                      return {
                        text: `${label}: ${v}`,
                        fillStyle: d.datasets[0].backgroundColor[i],
                        strokeStyle: d.datasets[0].backgroundColor[i],
                        index: i
                      };
                    });
                  }
                }
              },
              tooltip: {
                callbacks: {
                  label: function (context) {
                    const label = context.label || "";
                    const value = context.dataset.data[context.dataIndex];
                    return `${label}: ${value}`;
                  }
                }
              }
            }
          }
        });

        chartTotal.textContent = `${window.USER_FACILITY} - Total Files: ${total}`;
      })
      .catch(err => {
        loading.style.display = "none";
        console.error(err);
        Swal.fire("Error", "Failed to generate report: " + err.message, "error");
      });
  }

  // Post management functions
  function editPost(postId) {
    // First, get the current post data
    fetch(`get_posts.php?post_id=${postId}`)
      .then(response => response.json())
      .then(postData => {
        const post = postData.posts ? postData.posts[0] : null;
        if (!post) {
          Swal.fire('Error', 'Post not found', 'error');
          return;
        }

        // Parse existing links
        let existingLinks = [];
        if (post.links) {
          try {
            existingLinks = JSON.parse(post.links);
          } catch (e) {
            console.error('Error parsing links:', e);
          }
        }

        // Build the edit form HTML
        let linksHTML = '';
        if (existingLinks.length > 0) {
          existingLinks.forEach((link, index) => {
            linksHTML += `
              <div class="link-row" style="display: flex; gap: 10px; margin: 5px 0;">
                <input type="text" placeholder="Link URL" value="${link.url}" 
                       style="flex: 2; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" 
                       data-link-url="${index}">
                <input type="text" placeholder="Link Label" value="${link.label}" 
                       style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" 
                       data-link-label="${index}">
                <button type="button" onclick="this.parentElement.remove()" 
                        style="background: #f44336; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer;">×</button>
              </div>
            `;
          });
        }

        const editFormHTML = `
          <div style="text-align: left;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Post Title:</label>
            <input type="text" id="editTitle" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 15px; font-size: 16px; font-weight: bold;" 
                   value="${post.title}" required>
            
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Post Content:</label>
            <textarea id="editContent" style="width: 100%; height: 120px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; resize: vertical;" 
                      placeholder="Enter your post content here...">${post.content}</textarea>
            
            <label style="display: block; margin: 15px 0 5px; font-weight: bold;">Attachment:</label>
            <div style="margin-bottom: 10px;">
              ${post.file_name ? `
                <div style="background: #f0f0f0; padding: 10px; border-radius: 4px; margin-bottom: 10px;">
                  <span>📎 ${post.file_name}</span>
                  <label style="margin-left: 15px;">
                    <input type="checkbox" id="removeAttachment"> Remove current attachment
                  </label>
                </div>
              ` : '<p style="color: #666; margin-bottom: 10px;">No attachment</p>'}
              <input type="file" id="newAttachment" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            </div>

            <label style="display: block; margin: 15px 0 5px; font-weight: bold;">Links:</label>
            <div id="linksContainer">
              ${linksHTML}
            </div>
            <button type="button" onclick="addLinkRow()" 
                    style="background: #4CAF50; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; margin-top: 10px;">
              + Add Link
            </button>
          </div>
        `;

        Swal.fire({
          title: 'Edit Post',
          html: editFormHTML,
          showCancelButton: true,
          confirmButtonText: 'Save Changes',
          showLoaderOnConfirm: true,
          width: '600px',
          didOpen: () => {
            // Initialize CKEditor 5 for the edit textarea
            ClassicEditor
              .create(document.querySelector('#editContent'), {
                toolbar: [
                  'heading', '|',
                  'bold', 'italic', 'underline', 'strikethrough', '|',
                  'fontColor', 'fontBackgroundColor', '|',
                  'bulletedList', 'numberedList', '|',
                  'link', '|',
                  'blockQuote', '|',
                  'undo', 'redo'
                ],
                language: 'en'
              })
              .then(editor => {
                window.editPostEditor = editor;
                // Set the existing content in the editor
                editor.setData(post.content);
              })
              .catch(error => {
                console.error('CKEditor initialization error:', error);
              });
          },
          preConfirm: async () => {
            const title = document.getElementById('editTitle').value.trim();
            const content = window.editPostEditor ? window.editPostEditor.getData() : document.getElementById('editContent').value;
            if (!title) {
              Swal.showValidationMessage('Please enter a title');
              return;
            }
            if (!content || content.trim() === '') {
              Swal.showValidationMessage('Please enter some content');
              return;
            }

            try {
              const formData = new FormData();
              formData.append('post_id', postId);
              formData.append('title', title);
              formData.append('content', content);

              // Handle attachment
              const newAttachment = document.getElementById('newAttachment').files[0];
              const removeAttachment = document.getElementById('removeAttachment')?.checked;
              
              if (newAttachment) {
                formData.append('attachment', newAttachment);
              } else if (removeAttachment) {
                formData.append('remove_attachment', 'true');
              }

              // Handle links
              const linkRows = document.querySelectorAll('#linksContainer .link-row');
              const links = [];
              linkRows.forEach(row => {
                const urlInput = row.querySelector('[data-link-url]');
                const labelInput = row.querySelector('[data-link-label]');
                const url = urlInput ? urlInput.value.trim() : '';
                const label = labelInput ? labelInput.value.trim() : '';
                if (url && label) {
                  links.push({ url, label });
                }
              });
              
              if (links.length > 0) {
                formData.append('links', JSON.stringify(links));
              }

              const response = await fetch('edit_post.php', {
                method: 'POST',
                body: formData
              });

              const data = await response.json();

              if (!data.success) {
                throw new Error(data.message || 'Failed to update post');
              }

              return data;
            } catch (error) {
              Swal.showValidationMessage('Error: ' + error.message);
            }
          },
          allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
          if (result.isConfirmed) {
            Swal.fire({
              title: 'Success!',
              text: 'Post updated successfully',
              icon: 'success'
            }).then(() => {
              loadNewsfeed();
            });
          }
        });
      })
      .catch(error => {
        Swal.fire('Error', 'Failed to load post data: ' + error.message, 'error');
      });
  }

  // Helper function to add new link rows
  function addLinkRow() {
    const container = document.getElementById('linksContainer');
    const linkRow = document.createElement('div');
    linkRow.className = 'link-row';
    linkRow.style.cssText = 'display: flex; gap: 10px; margin: 5px 0;';
    linkRow.innerHTML = `
      <input type="text" placeholder="Link URL" 
             style="flex: 2; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" 
             data-link-url="new">
      <input type="text" placeholder="Link Label" 
             style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" 
             data-link-label="new">
      <button type="button" onclick="this.parentElement.remove()" 
              style="background: #f44336; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer;">×</button>
    `;
    container.appendChild(linkRow);
  }

  // Helper function to add new link rows for create form
  function addCreateLinkRow() {
    const container = document.getElementById('createLinksContainer');
    const linkRow = document.createElement('div');
    linkRow.className = 'link-row';
    linkRow.style.cssText = 'display: flex; gap: 10px; margin: 5px 0;';
    linkRow.innerHTML = `
      <input type="text" placeholder="Link URL" 
             style="flex: 2; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" 
             data-create-link-url="new">
      <input type="text" placeholder="Link Label" 
             style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" 
             data-create-link-label="new">
      <button type="button" onclick="this.parentElement.remove()" 
              style="background: #f44336; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer;">×</button>
    `;
    container.appendChild(linkRow);
    updateLinksToggleState();
  }

  // Function to toggle links section visibility
  function toggleLinksSection() {
    const linksSection = document.getElementById('linksSection');
    const linksIcon = document.getElementById('linksIcon');
    const linksText = document.getElementById('linksText');
    const linksChevron = document.getElementById('linksChevron');
    
    if (linksSection.style.display === 'none') {
      linksSection.style.display = 'block';
      linksIcon.style.color = '#4CAF50';
      linksText.style.color = '#4CAF50';
      linksChevron.style.transform = 'rotate(180deg)';
    } else {
      linksSection.style.display = 'none';
      updateLinksToggleState();
    }
  }

  // Function to update the toggle button state based on links count
  function updateLinksToggleState() {
    const linkRows = document.querySelectorAll('#createLinksContainer .link-row');
    const linksIcon = document.getElementById('linksIcon');
    const linksText = document.getElementById('linksText');
    const linksChevron = document.getElementById('linksChevron');
    
    if (linkRows.length > 0) {
      linksIcon.style.color = '#4CAF50';
      linksText.textContent = `${linkRows.length} link${linkRows.length > 1 ? 's' : ''} added`;
      linksText.style.color = '#4CAF50';
      linksChevron.style.transform = 'rotate(0deg)';
    } else {
      linksIcon.style.color = '#666';
      linksText.textContent = 'Add links';
      linksText.style.color = '#666';
      linksChevron.style.transform = 'rotate(0deg)';
    }
  }

  // Function to toggle drive link section visibility
  function toggleDriveLinkSection() {
    const driveSection = document.getElementById('driveLinkSection');
    const driveIcon = document.getElementById('driveIcon');
    const driveText = document.getElementById('driveText');
    const driveChevron = document.getElementById('driveChevron');
    
    if (driveSection.style.display === 'none') {
      driveSection.style.display = 'block';
      driveIcon.style.color = '#ff9800';
      driveText.style.color = '#ff9800';
      driveChevron.style.transform = 'rotate(180deg)';
    } else {
      driveSection.style.display = 'none';
      driveIcon.style.color = '#666';
      driveText.style.color = '#666';
      driveChevron.style.transform = 'rotate(0deg)';
    }
  }

  function deletePost(postId) {
    Swal.fire({
      title: 'Are you sure?',
      text: 'This will permanently delete your post and any associated files.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Yes, delete it!'
    }).then(async (result) => {
      if (result.isConfirmed) {
        try {
          const formData = new FormData();
          formData.append('post_id', postId);
          
          const response = await fetch('delete_post.php', {
            method: 'POST',
            body: formData
          });
          
          const data = await response.json();
          
          if (data.success) {
            Swal.fire({
              title: 'Deleted!',
              text: 'Your post has been deleted.',
              icon: 'success'
            }).then(() => {
              // Reload the newsfeed to remove deleted post
              loadNewsfeed();
            });
          } else {
            throw new Error(data.message || 'Failed to delete post');
          }
        } catch (error) {
          Swal.fire('Error!', 'Failed to delete post: ' + error.message, 'error');
        }
      }
    });
  }
</script>

<!-- User Guide Modal -->
<div id="userGuideModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.8); z-index: 10000; overflow-y: auto;">
  <div style="max-width: 1100px; margin: 50px auto; background: white; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); display: flex;">
    <!-- Navigation Sidebar -->
    <div id="userGuideNav" style="width: 250px; border-right: 1px solid #eee; background: #f8f9fa; border-radius: 10px 0 0 10px; display: none;">
      <div style="padding: 20px; border-bottom: 1px solid #e9ecef;">
        <h3 style="margin: 0; color: var(--brand); font-size: 16px; display: flex; align-items: center; gap: 8px;">
          <i class="fas fa-list"></i> Quick Navigation
        </h3>
      </div>
      <div id="userGuideNavContent" style="padding: 15px 0; max-height: 60vh; overflow-y: auto;">
        <!-- Navigation links will be populated here -->
      </div>
    </div>
    
    <!-- Main Content Area -->
    <div style="flex: 1;">
      <div style="padding: 20px 30px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
        <h2 style="margin: 0; color: var(--brand); font-size: 24px;">📖 KP-HUB User Guide</h2>
        <div style="display: flex; align-items: center; gap: 15px;">
          <button id="toggleNavBtn" onclick="toggleUserGuideNav()" style="background: var(--brand); color: white; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; font-size: 14px; display: flex; align-items: center; gap: 5px;">
            <i class="fas fa-bars"></i> Menu
          </button>
          <button onclick="closeUserGuide()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666; padding: 0;">&times;</button>
        </div>
      </div>
      <div id="userGuideContent" style="padding: 30px; max-height: 70vh; overflow-y: auto; line-height: 1.6;">
        <div style="text-align: center; padding: 40px;">
          <div style="font-size: 18px; color: #666; margin-bottom: 20px;">Loading user guide...</div>
          <div style="width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid var(--brand); border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto;"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  function showUserGuide(event) {
    event.preventDefault();
    
    const modal = document.getElementById('userGuideModal');
    const content = document.getElementById('userGuideContent');
    const nav = document.getElementById('userGuideNav');
    const toggleBtn = document.getElementById('toggleNavBtn');
    
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
    
    // Show navigation on larger screens by default, hide on mobile
    if (window.innerWidth > 768) {
      nav.classList.add('show-nav');
      toggleBtn.classList.add('active');
      toggleBtn.innerHTML = '<i class="fas fa-times"></i> Hide';
    } else {
      nav.classList.remove('show-nav');
      toggleBtn.classList.remove('active');
      toggleBtn.innerHTML = '<i class="fas fa-bars"></i> Menu';
    }
    
    // Fetch and display the README content
    fetch('README-USER.md')
      .then(response => {
        if (!response.ok) {
          throw new Error('Failed to load user guide');
        }
        return response.text();
      })
      .then(markdown => {
        // Simple markdown to HTML conversion for basic formatting
        let html = markdown
          // Headers with anchor IDs
          .replace(/^### (.*$)/gim, (match, title) => {
            const id = title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
            return `<h3 id="${id}" style="color: var(--brand); margin-top: 30px; margin-bottom: 15px; font-size: 18px;">${title}</h3>`;
          })
          .replace(/^## (.*$)/gim, (match, title) => {
            const id = title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
            return `<h2 id="${id}" style="color: var(--brand); margin-top: 40px; margin-bottom: 20px; font-size: 22px; border-bottom: 2px solid #eee; padding-bottom: 10px;">${title}</h2>`;
          })
          .replace(/^# (.*$)/gim, (match, title) => {
            const id = title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
            return `<h1 id="${id}" style="color: var(--brand); margin-top: 0; margin-bottom: 25px; font-size: 28px; text-align: center;">${title}</h1>`;
          })
          
          // Images
          .replace(/!\[([^\]]*)\]\(([^)]+)\)/g, '<img alt="$1" src="$2" style="max-width: 100%; height: auto; border-radius: 8px; margin: 15px 0; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">')
          
          // Bold and italic
          .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
          .replace(/\*(.*?)\*/g, '<em>$1</em>')
          
          // Lists
          .replace(/^\- (.*$)/gim, '<li style="margin-bottom: 8px;">$1</li>')
          .replace(/^\d+\. (.*$)/gim, '<li style="margin-bottom: 8px;">$1</li>')
          
          // Code blocks (simple)
          .replace(/```([\s\S]*?)```/g, '<pre style="background: #f8f9fa; padding: 15px; border-radius: 6px; border: 1px solid #e9ecef; margin: 15px 0; overflow-x: auto;"><code>$1</code></pre>')
          .replace(/`([^`]+)`/g, '<code style="background: #f1f3f4; padding: 2px 6px; border-radius: 3px; font-family: monospace;">$1</code>')
          
          // Links
          .replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank" style="color: var(--brand); text-decoration: none;">$1</a>')
          
          // Horizontal rules
          .replace(/^---$/gm, '<hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">')
          
          // Paragraphs (convert line breaks to paragraphs)
          .split('\n\n')
          .map(paragraph => {
            if (paragraph.trim() === '') return '';
            if (paragraph.includes('<h') || paragraph.includes('<li') || paragraph.includes('<pre') || paragraph.includes('<hr') || paragraph.includes('<img')) {
              return paragraph;
            }
            return '<p style="margin-bottom: 15px; line-height: 1.6;">' + paragraph.replace(/\n/g, '<br>') + '</p>';
          })
          .join('');
        
        content.innerHTML = html;
        
        // Generate navigation menu
        generateUserGuideNavigation();
        
        // Set up smooth scrolling for navigation links
        setupNavigationScroll();
      })
      .catch(error => {
        content.innerHTML = `
          <div style="text-align: center; padding: 40px; color: #666;">
            <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #f44336; margin-bottom: 20px;"></i>
            <h3 style="color: #f44336; margin-bottom: 15px;">Unable to Load User Guide</h3>
            <p>Sorry, we couldn't load the user guide at this time. Please try again later or contact your administrator.</p>
            <p style="font-size: 14px; color: #999; margin-top: 20px;">Error: ${error.message}</p>
          </div>
        `;
      });
  }
  
  function closeUserGuide() {
    const modal = document.getElementById('userGuideModal');
    modal.style.display = 'none';
    document.body.style.overflow = ''; // Restore scrolling
  }
  
  function toggleUserGuideNav() {
    const nav = document.getElementById('userGuideNav');
    const toggleBtn = document.getElementById('toggleNavBtn');
    
    if (nav.classList.contains('show-nav')) {
      nav.classList.remove('show-nav');
      toggleBtn.classList.remove('active');
      toggleBtn.innerHTML = '<i class="fas fa-bars"></i> Menu';
    } else {
      nav.classList.add('show-nav');
      toggleBtn.classList.add('active');
      toggleBtn.innerHTML = '<i class="fas fa-times"></i> Hide';
    }
  }
  
  function generateUserGuideNavigation() {
    const content = document.getElementById('userGuideContent');
    const navContent = document.getElementById('userGuideNavContent');
    
    // Define navigation sections with their icons and titles
    const navSections = [
      {
        title: 'Getting Started',
        items: [
          { id: 'quick-start', title: 'Quick Start', icon: 'fas fa-rocket' },
          { id: 'logging-in', title: 'Logging In', icon: 'fas fa-sign-in-alt' }
        ]
      },
      {
        title: 'Creating Content',
        items: [
          { id: 'creating-a-post-step-by-step', title: 'Creating a Post', icon: 'fas fa-edit' },
          { id: 'attaching-files', title: 'Attaching Files', icon: 'fas fa-paperclip' },
          { id: 'adding-links', title: 'Adding Links', icon: 'fas fa-link' }
        ]
      },
      {
        title: 'Managing Content',
        items: [
          { id: 'editing-deleting-posts', title: 'Edit/Delete Posts', icon: 'fas fa-cogs' },
          { id: 'reactions', title: 'Reactions', icon: 'fas fa-heart' }
        ]
      },
      {
        title: 'Navigation & Features',
        items: [
          { id: 'searching-pagination', title: 'Search & Pagination', icon: 'fas fa-search' },
          { id: 'notifications', title: 'Notifications', icon: 'fas fa-bell' }
        ]
      },
      {
        title: 'File Management',
        items: [
          { id: 'viewing-uploading-to-folders', title: 'Folders & Uploads', icon: 'fas fa-folder-open' },
          { id: 'sharing-files-folders-to-newsfeed', title: 'Sharing to Newsfeed', icon: 'fas fa-share-alt' }
        ]
      },
      {
        title: 'Account & Support',
        items: [
          { id: 'profile-management', title: 'Profile Management', icon: 'fas fa-user' },
          { id: 'troubleshooting-user-focused', title: 'Troubleshooting', icon: 'fas fa-question-circle' }
        ]
      }
    ];
    
    // Generate navigation HTML
    let navHtml = '';
    navSections.forEach(section => {
      navHtml += `<div class="nav-section">`;
      navHtml += `<div class="nav-section-title">${section.title}</div>`;
      section.items.forEach(item => {
        navHtml += `<a href="#${item.id}" class="nav-link" onclick="scrollToSection('${item.id}')">`;
        navHtml += `<i class="${item.icon}"></i>${item.title}`;
        navHtml += `</a>`;
      });
      navHtml += `</div>`;
    });
    
    navContent.innerHTML = navHtml;
  }
  
  function setupNavigationScroll() {
    // Add scroll event listener to highlight active section
    const content = document.getElementById('userGuideContent');
    const navLinks = document.querySelectorAll('.nav-link');
    
    content.addEventListener('scroll', function() {
      const scrollPosition = content.scrollTop + 100; // Offset for header
      
      // Find the current section
      const sections = content.querySelectorAll('h1, h2, h3');
      let currentSection = null;
      
      sections.forEach(section => {
        if (section.offsetTop <= scrollPosition) {
          currentSection = section;
        }
      });
      
      // Update active nav link
      navLinks.forEach(link => {
        link.classList.remove('active');
        if (currentSection && link.getAttribute('href') === '#' + currentSection.id) {
          link.classList.add('active');
        }
      });
    });
  }
  
  function scrollToSection(sectionId) {
    const content = document.getElementById('userGuideContent');
    const targetElement = document.getElementById(sectionId);
    
    if (targetElement) {
      const offsetTop = targetElement.offsetTop - 20; // Small offset from top
      content.scrollTo({
        top: offsetTop,
        behavior: 'smooth'
      });
      
      // Update active nav link
      document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === '#' + sectionId) {
          link.classList.add('active');
        }
      });
    }
  }
  
  // Close modal when clicking outside
  document.getElementById('userGuideModal').addEventListener('click', function(event) {
    if (event.target === this) {
      closeUserGuide();
    }
  });
  
  // Close modal with Escape key
  document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape' && document.getElementById('userGuideModal').style.display === 'block') {
      closeUserGuide();
    }
  });
</script>

<?php include 'feedback_widget.php'; ?>

</body>
</html>
