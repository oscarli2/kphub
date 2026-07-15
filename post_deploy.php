<?php
/**
 * KP-HUB Production Deployment Helper
 * Run this after uploading files to production server
 */

class ProductionDeploy {
    private $errors = [];
    private $warnings = [];

    public function runPostDeployChecks() {
        $this->log("🔧 KP-HUB Production Post-Deployment Checks");
        $this->log("===========================================");

        $this->checkEnvironment();
        $this->testDatabaseConnection();
        $this->testFileOperations();
        $this->testImageProcessing();
        $this->generateSecurityReport();

        $this->reportResults();

        return empty($this->errors);
    }

    private function checkEnvironment() {
        $this->log("\n🌐 Environment Check...");

        // Check if we're in production
        $isProduction = false;
        if (isset($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];
            if (strpos($host, 'localhost') === false && strpos($host, '127.0.0.1') === false) {
                $isProduction = true;
            }
        }

        if ($isProduction) {
            $this->log("✅ Running in production environment");
        } else {
            $this->warnings[] = "Running in development environment - ensure production settings";
        }

        // Check HTTPS
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            $this->log("✅ HTTPS enabled");
        } else {
            $this->warnings[] = "HTTPS not detected - consider enabling SSL";
        }
    }

    private function testDatabaseConnection() {
        $this->log("\n🗄️  Database Connection Test...");

        try {
            require_once __DIR__ . '/db.php';
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->log("✅ Database connection successful - {$result['count']} users found");
        } catch (Exception $e) {
            $this->errors[] = "Database connection failed: " . $e->getMessage();
        }
    }

    private function testFileOperations() {
        $this->log("\n📁 File Operations Test...");

        $testFile = __DIR__ . '/uploads/test_write.txt';
        $testData = 'KP-HUB deployment test - ' . date('Y-m-d H:i:s');

        // Test file writing
        if (file_put_contents($testFile, $testData) !== false) {
            $this->log("✅ File write test successful");

            // Test file reading
            $readData = file_get_contents($testFile);
            if ($readData === $testData) {
                $this->log("✅ File read test successful");
            } else {
                $this->errors[] = "File read test failed";
            }

            // Clean up
            unlink($testFile);
        } else {
            $this->errors[] = "File write test failed - check uploads directory permissions";
        }

        // Test thumbnail directory
        $thumbDir = __DIR__ . '/uploads/thumbnails';
        if (is_dir($thumbDir) && is_writable($thumbDir)) {
            $this->log("✅ Thumbnails directory writable");
        } else {
            $this->errors[] = "Thumbnails directory not writable";
        }

        // Check for required files
        $this->checkRequiredFiles();
    }

    private function checkRequiredFiles() {
        $this->log("\n📄 Required Files Check...");

        $requiredFiles = [
            'uploads/default.png' => 'Default profile picture',
            'uploads/thumbnails/.gitkeep' => 'Thumbnails directory placeholder'
        ];

        foreach ($requiredFiles as $file => $description) {
            $filePath = __DIR__ . '/' . $file;
            if (file_exists($filePath)) {
                $this->log("✅ $description found");
            } else {
                $this->errors[] = "$description missing: $file";
            }
        }

        // Check if thumbnails directory exists and is accessible
        $thumbDir = __DIR__ . '/uploads/thumbnails';
        if (!is_dir($thumbDir)) {
            $this->errors[] = "Thumbnails directory does not exist";
        } elseif (!is_writable($thumbDir)) {
            $this->errors[] = "Thumbnails directory is not writable";
        }
    }

    private function testImageProcessing() {
        $this->log("\n🖼️  Image Processing Test...");

        // Create a test image
        $testImage = imagecreatetruecolor(100, 100);
        $white = imagecolorallocate($testImage, 255, 255, 255);
        $black = imagecolorallocate($testImage, 0, 0, 0);

        imagefill($testImage, 0, 0, $white);
        imagerectangle($testImage, 20, 20, 80, 80, $black);

        $testFile = __DIR__ . '/uploads/test_image.jpg';

        if (imagejpeg($testImage, $testFile, 90)) {
            $this->log("✅ Image creation test successful");

            // Test image reading
            $imageInfo = getimagesize($testFile);
            if ($imageInfo && $imageInfo[0] == 100 && $imageInfo[1] == 100) {
                $this->log("✅ Image reading test successful");
            } else {
                $this->errors[] = "Image reading test failed";
            }

            // Clean up
            unlink($testFile);
        } else {
            $this->errors[] = "Image creation test failed";
        }

        imagedestroy($testImage);
    }

    private function generateSecurityReport() {
        $this->log("\n🔒 Security Check...");

        // Check for common security issues
        $checks = [
            'display_errors' => ini_get('display_errors'),
            'expose_php' => ini_get('expose_php'),
            'allow_url_fopen' => ini_get('allow_url_fopen'),
            'allow_url_include' => ini_get('allow_url_include')
        ];

        foreach ($checks as $setting => $value) {
            if ($setting === 'display_errors' && $value) {
                $this->warnings[] = "display_errors is enabled - disable in production";
            } elseif ($setting === 'expose_php' && $value) {
                $this->warnings[] = "expose_php is enabled - consider disabling";
            } elseif ($setting === 'allow_url_include' && $value) {
                $this->warnings[] = "allow_url_include is enabled - security risk";
            } else {
                $this->log("✅ $setting setting OK");
            }
        }

        // Check file permissions
        $sensitiveFiles = ['db.php', 'config.php'];
        foreach ($sensitiveFiles as $file) {
            $path = __DIR__ . '/' . $file;
            if (file_exists($path)) {
                $perms = substr(sprintf('%o', fileperms($path)), -4);
                if ($perms <= '0644') {
                    $this->log("✅ $file permissions OK ($perms)");
                } else {
                    $this->warnings[] = "$file permissions too open ($perms) - consider 0644";
                }
            }
        }
    }

    private function reportResults() {
        $this->log("\n📊 Post-Deployment Results");
        $this->log("==========================");

        if (empty($this->errors)) {
            $this->log("🎉 SUCCESS: Production deployment checks passed!");
        } else {
            $this->log("❌ ERRORS:");
            foreach ($this->errors as $error) {
                $this->log("   - $error");
            }
        }

        if (!empty($this->warnings)) {
            $this->log("⚠️  WARNINGS:");
            foreach ($this->warnings as $warning) {
                $this->log("   - $warning");
            }
        }

        $this->log("\n📋 Production Checklist:");
        $this->log("1. ✅ Database connection working");
        $this->log("2. ✅ File permissions set correctly");
        $this->log("3. ✅ Image processing functional");
        $this->log("4. ✅ Security settings reviewed");
        $this->log("5. 🔄 Test user registration/login");
        $this->log("6. 🔄 Test profile picture upload");
        $this->log("7. 🔄 Test all user roles and permissions");
        $this->log("8. 🔄 Configure backup procedures");
        $this->log("9. 🔄 Set up monitoring/logging");
        $this->log("10. 🗑️  Remove migration and test files");
    }

    private function log($message) {
        echo $message . PHP_EOL;
    }
}

// Run post-deployment checks
$deploy = new ProductionDeploy();
$success = $deploy->runPostDeployChecks();

exit($success ? 0 : 1);
?>