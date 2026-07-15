<?php
/**
 * KP-HUB Production Migration Script
 * Ensures all components are properly set up for production deployment
 */

class KPHubMigration {
    private $pdo;
    private $errors = [];
    private $warnings = [];

    public function __construct() {
        $this->connectDatabase();
    }

    private function connectDatabase() {
        try {
            // Load config if available
            $configFile = __DIR__ . '/config.php';
            if (file_exists($configFile)) {
                require_once $configFile;
            }

            // Try to connect to database
            require_once __DIR__ . '/db.php';

            // Test connection
            $stmt = $pdo->query("SELECT 1");
            if ($stmt) {
                $this->pdo = $pdo;
                $this->log("✅ Database connection successful");
            }
        } catch (Exception $e) {
            $this->errors[] = "Database connection failed: " . $e->getMessage();
        }
    }

    public function runMigration() {
        $this->log("🚀 Starting KP-HUB Production Migration");
        $this->log("=====================================");

        // Run all checks
        $this->checkPHPExtensions();
        $this->checkDirectories();
        $this->checkDatabaseStructure();
        $this->checkFilePermissions();
        $this->validateConfiguration();
        $this->testCoreFunctionality();

        // Report results
        $this->reportResults();

        return empty($this->errors);
    }

    private function checkPHPExtensions() {
        $this->log("\n📦 Checking PHP Extensions...");

        $required = ['pdo', 'pdo_mysql', 'gd', 'json', 'session', 'fileinfo'];
        $recommended = ['mbstring', 'openssl'];

        foreach ($required as $ext) {
            if (extension_loaded($ext)) {
                $this->log("✅ $ext extension loaded");
            } else {
                $this->errors[] = "Required PHP extension '$ext' is not loaded";
            }
        }

        foreach ($recommended as $ext) {
            if (extension_loaded($ext)) {
                $this->log("✅ $ext extension loaded (recommended)");
            } else {
                $this->warnings[] = "Recommended PHP extension '$ext' is not loaded";
            }
        }

        // Check GD version
        if (extension_loaded('gd')) {
            $gdInfo = gd_info();
            $this->log("ℹ️  GD Version: " . ($gdInfo['GD Version'] ?? 'Unknown'));
        }
    }

    private function checkDirectories() {
        $this->log("\n📁 Checking Directory Structure...");

        $directories = [
            'uploads',
            'uploads/thumbnails',
            'config',
            'migrations',
            'api_feedback',
            'api_public_visits',
            'api_upload_logs',
            'api_users'
        ];

        foreach ($directories as $dir) {
            $fullPath = __DIR__ . '/' . $dir;
            if (is_dir($fullPath)) {
                $this->log("✅ Directory exists: $dir");
            } else {
                // Try to create directory
                if (mkdir($fullPath, 0755, true)) {
                    $this->log("✅ Created directory: $dir");
                } else {
                    $this->errors[] = "Failed to create directory: $dir";
                }
            }
        }
    }

    private function checkDatabaseStructure() {
        if (!$this->pdo) {
            $this->errors[] = "Cannot check database structure - no database connection";
            return;
        }

        $this->log("\n🗄️  Checking Database Structure...");

        try {
            // Check if users table exists
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'users'");
            if ($stmt->rowCount() > 0) {
                $this->log("✅ Users table exists");

                // Check users table structure
                $this->checkUsersTableStructure();
            } else {
                $this->errors[] = "Users table does not exist";
            }

            // Check if upload_logs table exists
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'upload_logs'");
            if ($stmt->rowCount() > 0) {
                $this->log("✅ Upload logs table exists");
            } else {
                $this->warnings[] = "Upload logs table does not exist (optional)";
            }

        } catch (Exception $e) {
            $this->errors[] = "Database structure check failed: " . $e->getMessage();
        }
    }

    private function checkUsersTableStructure() {
        try {
            $stmt = $this->pdo->query("DESCRIBE users");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $requiredColumns = [
                'user_id' => 'int',
                'email' => 'varchar',
                'password' => 'varchar',
                'facility' => 'varchar',
                'role' => 'enum',
                'profile_picture' => 'varchar'
            ];

            $existingColumns = array_column($columns, 'Type', 'Field');

            foreach ($requiredColumns as $col => $type) {
                if (isset($existingColumns[$col])) {
                    $this->log("✅ Column exists: $col");
                } else {
                    $this->errors[] = "Required column missing: $col";
                }
            }

            // Check for optional columns
            $optionalColumns = ['can_create_folder', 'can_delete', 'can_delete_files', 'can_generate_report'];
            foreach ($optionalColumns as $col) {
                if (isset($existingColumns[$col])) {
                    $this->log("✅ Optional column exists: $col");
                } else {
                    $this->warnings[] = "Optional column missing: $col";
                }
            }

        } catch (Exception $e) {
            $this->errors[] = "Users table structure check failed: " . $e->getMessage();
        }
    }

    private function checkFilePermissions() {
        $this->log("\n🔐 Checking File Permissions...");

        $criticalFiles = [
            'db.php',
            'config.php',
            'update_profile.php',
            'session.php'
        ];

        foreach ($criticalFiles as $file) {
            $path = __DIR__ . '/' . $file;
            if (file_exists($path)) {
                if (is_readable($path)) {
                    $this->log("✅ File readable: $file");
                } else {
                    $this->errors[] = "File not readable: $file";
                }
            } else {
                $this->warnings[] = "File does not exist: $file";
            }
        }

        // Check uploads directory permissions
        $uploadsDir = __DIR__ . '/uploads';
        if (is_dir($uploadsDir)) {
            if (is_writable($uploadsDir)) {
                $this->log("✅ Uploads directory writable");
            } else {
                $this->errors[] = "Uploads directory not writable";
            }
        }
    }

    private function validateConfiguration() {
        $this->log("\n⚙️  Validating Configuration...");

        // Check if config.php exists and is readable
        $configFile = __DIR__ . '/config.php';
        if (file_exists($configFile)) {
            if (is_readable($configFile)) {
                $this->log("✅ Config file exists and readable");
            } else {
                $this->errors[] = "Config file exists but not readable";
            }
        } else {
            $this->warnings[] = "Config file does not exist (using defaults)";
        }

        // Check PHP version
        $phpVersion = PHP_VERSION;
        $minVersion = '7.4.0';
        if (version_compare($phpVersion, $minVersion, '>=')) {
            $this->log("✅ PHP version: $phpVersion (minimum: $minVersion)");
        } else {
            $this->errors[] = "PHP version $phpVersion is below minimum $minVersion";
        }
    }

    private function testCoreFunctionality() {
        $this->log("\n🧪 Testing Core Functionality...");

        // Test image processing functions
        if (function_exists('imagecreatetruecolor')) {
            $this->log("✅ GD image functions available");
        } else {
            $this->errors[] = "GD image functions not available";
        }

        // Test session functionality
        if (function_exists('session_start')) {
            $this->log("✅ Session functions available");
        } else {
            $this->errors[] = "Session functions not available";
        }

        // Test file upload functionality
        if (ini_get('file_uploads')) {
            $this->log("✅ File uploads enabled");
        } else {
            $this->errors[] = "File uploads disabled in PHP configuration";
        }

        // Test thumbnail directory creation
        $thumbDir = __DIR__ . '/uploads/thumbnails';
        if (is_dir($thumbDir) && is_writable($thumbDir)) {
            $this->log("✅ Thumbnails directory ready");
        } else {
            $this->warnings[] = "Thumbnails directory not ready for writing";
        }
    }

    private function reportResults() {
        $this->log("\n📊 Migration Results");
        $this->log("===================");

        if (empty($this->errors) && empty($this->warnings)) {
            $this->log("🎉 SUCCESS: All checks passed! Your KP-HUB installation is ready for production.");
        } else {
            if (!empty($this->errors)) {
                $this->log("❌ ERRORS (" . count($this->errors) . "):");
                foreach ($this->errors as $error) {
                    $this->log("   - $error");
                }
            }

            if (!empty($this->warnings)) {
                $this->log("⚠️  WARNINGS (" . count($this->warnings) . "):");
                foreach ($this->warnings as $warning) {
                    $this->log("   - $warning");
                }
            }

            if (!empty($this->errors)) {
                $this->log("\n🔧 ACTION REQUIRED: Please fix the errors above before deploying to production.");
            } else {
                $this->log("\n✅ READY: Warnings are optional but recommended to fix for optimal performance.");
            }
        }

        $this->log("\n📋 Next Steps:");
        $this->log("1. Upload all files to your production server");
        $this->log("2. Update database connection settings in db.php or config.php");
        $this->log("3. Set proper file permissions (755 for directories, 644 for files)");
        $this->log("4. Configure your web server (Apache/Nginx) properly");
        $this->log("5. Test the application thoroughly");
        $this->log("6. Remove this migration script from production");
    }

    private function log($message) {
        echo $message . PHP_EOL;
    }
}

// Run the migration
$migration = new KPHubMigration();
$success = $migration->runMigration();

exit($success ? 0 : 1);
?>