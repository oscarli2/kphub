<?php
require_once 'security.php';

// Basic security initialization for web pages
class PageSecurity {
    public static function initPageSecurity() {
        // Check basic rate limiting
        SecurityManager::checkRateLimit();
        
        // Validate user agent
        SecurityManager::validateUserAgent();
        
        // Set security headers
        self::setSecurityHeaders();
        
        // Start session securely
        self::secureSessionStart();
    }
    
    private static function setSecurityHeaders() {
        // Prevent clickjacking - allow framing for Google Drive embeds
        // header('X-Frame-Options: DENY'); // Removed to allow Google Drive iframe embeds
        
        // Prevent MIME sniffing
        header('X-Content-Type-Options: nosniff');
        
        // XSS protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Referrer policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Content Security Policy (updated for Google Drive embeds)
        header("Content-Security-Policy: default-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://cdn.ckeditor.com https://fonts.googleapis.com https://fonts.gstatic.com https://script.google.com https://script.googleusercontent.com https://drive.google.com https://docs.google.com https://accounts.google.com https://apis.google.com https://chart.js https://code.jquery.com data:; img-src 'self' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://cdn.ckeditor.com https://fonts.googleapis.com https://fonts.gstatic.com https://script.google.com https://script.googleusercontent.com https://drive.google.com https://docs.google.com https://accounts.google.com https://apis.google.com https://chart.js https://code.jquery.com https://lh3.googleusercontent.com data:; connect-src 'self' https://script.google.com https://script.googleusercontent.com https://cdn.jsdelivr.net https://cdn.ckeditor.com; frame-src 'self' https://drive.google.com https://docs.google.com https://accounts.google.com https://*.googleusercontent.com https://*.google.com; frame-ancestors 'self' https://drive.google.com https://docs.google.com https://*.google.com;");
    }
    
    private static function secureSessionStart() {
        if (session_status() === PHP_SESSION_NONE) {
            // Secure session configuration
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_samesite', 'Lax');
            
            session_start();
        }
    }
    
    public static function requireAdmin() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
            SecurityManager::logSecurityEvent("Unauthorized admin access attempt");
            header('Location: index.php');
            exit;
        }
    }
    
    public static function sanitizeOutput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeOutput'], $data);
        }
        return SecurityManager::sanitizeOutput($data);
    }
    
    public static function validateFileUpload($file) {
        // Check file size (max 10MB)
        if ($file['size'] > 10 * 1024 * 1024) {
            throw new SecurityException('File too large');
        }
        
        // Check file type
        $allowedTypes = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'application/pdf', 'text/plain', 'text/csv',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            throw new SecurityException('File type not allowed');
        }
        
        // Check for malicious content in filename
        $filename = SecurityManager::validateInput($file['name'], 'filename', 255);
        
        return true;
    }
}
?>