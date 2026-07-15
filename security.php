<?php
class SecurityManager {
    private static $rateLimits = [];
    private static $blockedIPs = [];
    private static $maxRequests = 100; // Max requests per hour
    private static $blockDuration = 3600; // 1 hour block
    
    public static function checkRateLimit($identifier = null) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = $identifier ? $identifier : $ip;
        $currentTime = time();
        
        // Clean old entries
        self::cleanOldEntries();
        
        // Check if IP is blocked
        if (isset(self::$blockedIPs[$ip]) && self::$blockedIPs[$ip] > $currentTime) {
            http_response_code(429);
            header('Retry-After: ' . (self::$blockedIPs[$ip] - $currentTime));
            die(json_encode(['error' => 'Too many requests. IP temporarily blocked.']));
        }
        
        // Initialize or update rate limit tracking
        if (!isset(self::$rateLimits[$key])) {
            self::$rateLimits[$key] = [];
        }
        
        // Count recent requests (last hour)
        $recentRequests = 0;
        $hourAgo = $currentTime - 3600;
        
        foreach (self::$rateLimits[$key] as $timestamp) {
            if ($timestamp > $hourAgo) {
                $recentRequests++;
            }
        }
        
        // Check if limit exceeded
        if ($recentRequests >= self::$maxRequests) {
            // Block IP for 1 hour
            self::$blockedIPs[$ip] = $currentTime + self::$blockDuration;
            
            // Log the incident
            error_log("Rate limit exceeded for IP: $ip, Key: $key");
            
            http_response_code(429);
            header('Retry-After: ' . self::$blockDuration);
            die(json_encode(['error' => 'Rate limit exceeded. Try again later.']));
        }
        
        // Add current request
        self::$rateLimits[$key][] = $currentTime;
        
        return true;
    }
    
    private static function cleanOldEntries() {
        $currentTime = time();
        $hourAgo = $currentTime - 3600;
        
        // Clean rate limits
        foreach (self::$rateLimits as $key => $requests) {
            self::$rateLimits[$key] = array_filter($requests, function($timestamp) use ($hourAgo) {
                return $timestamp > $hourAgo;
            });
            
            if (empty(self::$rateLimits[$key])) {
                unset(self::$rateLimits[$key]);
            }
        }
        
        // Clean blocked IPs
        foreach (self::$blockedIPs as $ip => $blockTime) {
            if ($blockTime <= $currentTime) {
                unset(self::$blockedIPs[$ip]);
            }
        }
    }
    
    public static function validateInput($input, $type = 'general', $maxLength = 1000) {
        // Basic validation
        if (!is_string($input)) {
            throw new InvalidArgumentException('Input must be a string');
        }
        
        // Length check
        if (strlen($input) > $maxLength) {
            throw new InvalidArgumentException('Input too long');
        }
        
        // SQL injection patterns
        $sqlPatterns = [
            '/(\bUNION\b.*\bSELECT\b)/i',
            '/(\bSELECT\b.*\bFROM\b)/i',
            '/(\bINSERT\b.*\bINTO\b)/i',
            '/(\bUPDATE\b.*\bSET\b)/i',
            '/(\bDELETE\b.*\bFROM\b)/i',
            '/(\bDROP\b.*\bTABLE\b)/i',
            '/(\bCREATE\b.*\bTABLE\b)/i',
            '/(\bALTER\b.*\bTABLE\b)/i',
            '/(\bEXEC\b|\bEXECUTE\b)/i',
            '/(--|\#|\/\*)/i',
            '/(\bOR\b\s+\d+\s*=\s*\d+)/i',
            '/(\bAND\b\s+\d+\s*=\s*\d+)/i',
            '/(\'\s*OR\s*\'\s*1\s*=\s*1)/i',
            '/(\'\s*;\s*DROP)/i'
        ];
        
        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                error_log("Potential SQL injection attempt from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . " - Input: " . $input);
                throw new SecurityException('Invalid input detected');
            }
        }
        
        // XSS protection
        $xssPatterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i',
            '/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/i',
            '/javascript:/i',
            '/on\w+\s*=/i'
        ];
        
        foreach ($xssPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                error_log("Potential XSS attempt from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . " - Input: " . $input);
                throw new SecurityException('Invalid input detected');
            }
        }
        
        // Type-specific validation
        switch ($type) {
            case 'email':
                if (!filter_var($input, FILTER_VALIDATE_EMAIL)) {
                    throw new InvalidArgumentException('Invalid email format');
                }
                break;
                
            case 'integer':
                if (!filter_var($input, FILTER_VALIDATE_INT)) {
                    throw new InvalidArgumentException('Invalid integer');
                }
                break;
                
            case 'filename':
                if (!preg_match('/^[a-zA-Z0-9._-]+$/', $input)) {
                    throw new InvalidArgumentException('Invalid filename');
                }
                break;
        }
        
        return trim($input);
    }
    
    public static function sanitizeOutput($output) {
        return htmlspecialchars($output, ENT_QUOTES, 'UTF-8');
    }
    
    public static function checkCSRF() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_SESSION)) {
                session_start();
            }
            
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            
            if (!$token || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
                http_response_code(403);
                die(json_encode(['error' => 'Invalid CSRF token']));
            }
        }
    }
    
    public static function generateCSRFToken() {
        if (!isset($_SESSION)) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    public static function validateUserAgent() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Block known malicious user agents
        $blockedAgents = [
            'sqlmap',
            'nikto',
            'nmap',
            'masscan',
            'nessus',
            'openvas',
            'python-requests',
            'curl' // Be careful with this one
        ];
        
        foreach ($blockedAgents as $blocked) {
            if (stripos($userAgent, $blocked) !== false) {
                error_log("Blocked suspicious user agent from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . " - UA: " . $userAgent);
                http_response_code(403);
                die(json_encode(['error' => 'Access denied']));
            }
        }
        
        return true;
    }
    
    public static function logSecurityEvent($event, $details = '') {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $timestamp = date('Y-m-d H:i:s');
        
        $logEntry = "[$timestamp] Security Event: $event - IP: $ip - UA: $userAgent";
        if ($details) {
            $logEntry .= " - Details: $details";
        }
        
        error_log($logEntry);
    }
}

class SecurityException extends Exception {}
?>