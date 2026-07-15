<?php
/**
 * KP-HUB Production Deployment Fix
 * Fixes missing files and directories for production deployment
 */

class ProductionFix {
    private $errors = [];
    private $warnings = [];

    public function fixProductionDeployment() {
        $this->log("🔧 KP-HUB Production Deployment Fix");
        $this->log("===================================");

        $this->createMissingDirectories();
        $this->createDefaultProfilePicture();
        $this->fixFilePermissions();
        $this->verifySetup();

        $this->reportResults();

        return empty($this->errors);
    }

    private function createMissingDirectories() {
        $this->log("\n📂 Creating Missing Directories...");

        $directories = [
            'uploads/thumbnails',
            'uploads/posts',
            'logs'
        ];

        foreach ($directories as $dir) {
            $path = __DIR__ . '/' . $dir;
            if (!is_dir($path)) {
                if (mkdir($path, 0755, true)) {
                    $this->log("✅ Created directory: $dir");
                } else {
                    $this->errors[] = "Failed to create directory: $dir";
                }
            } else {
                $this->log("ℹ️  Directory already exists: $dir");
            }
        }
    }

    private function createDefaultProfilePicture() {
        $this->log("\n🖼️  Creating Default Profile Picture...");

        $defaultPath = __DIR__ . '/uploads/default.png';

        if (file_exists($defaultPath)) {
            $this->log("✅ Default profile picture already exists");
            return;
        }

        // Create a simple default profile picture (200x200 gray circle with user icon)
        $image = imagecreatetruecolor(200, 200);

        // Colors
        $lightGray = imagecolorallocate($image, 240, 240, 240);
        $darkGray = imagecolorallocate($image, 100, 100, 100);
        $white = imagecolorallocate($image, 255, 255, 255);

        // Fill background
        imagefill($image, 0, 0, $lightGray);

        // Draw circle background
        imagefilledellipse($image, 100, 100, 180, 180, $white);

        // Draw simple user icon (head and shoulders)
        // Head
        imagefilledellipse($image, 100, 80, 60, 60, $darkGray);
        // Body/shoulders
        imagefilledellipse($image, 100, 140, 80, 40, $darkGray);

        if (imagepng($image, $defaultPath, 9)) {
            $this->log("✅ Created default profile picture: uploads/default.png");
        } else {
            $this->errors[] = "Failed to create default profile picture";
        }

        imagedestroy($image);
    }

    private function fixFilePermissions() {
        $this->log("\n🔐 Fixing File Permissions...");

        $directories = [
            'uploads' => 0755,
            'uploads/thumbnails' => 0755,
            'uploads/posts' => 0755
        ];

        foreach ($directories as $dir => $perms) {
            $path = __DIR__ . '/' . $dir;
            if (is_dir($path)) {
                if (chmod($path, $perms)) {
                    $this->log("✅ Set permissions on directory: $dir");
                } else {
                    $this->warnings[] = "Failed to set permissions on directory: $dir";
                }
            }
        }

        // Fix permissions on default.png
        $defaultPath = __DIR__ . '/uploads/default.png';
        if (file_exists($defaultPath)) {
            if (chmod($defaultPath, 0644)) {
                $this->log("✅ Set permissions on default.png");
            } else {
                $this->warnings[] = "Failed to set permissions on default.png";
            }
        }
    }

    private function verifySetup() {
        $this->log("\n🔍 Verifying Production Setup...");

        $checks = [
            'uploads/default.png' => 'Default profile picture',
            'uploads/thumbnails/' => 'Thumbnails directory',
            'uploads/posts/' => 'Posts directory'
        ];

        foreach ($checks as $path => $description) {
            $fullPath = __DIR__ . '/' . $path;
            if (substr($path, -1) === '/') {
                // Directory check
                if (is_dir($fullPath) && is_writable($fullPath)) {
                    $this->log("✅ $description ready");
                } else {
                    $this->errors[] = "$description not writable";
                }
            } else {
                // File check
                if (file_exists($fullPath)) {
                    $this->log("✅ $description found");
                } else {
                    $this->errors[] = "$description missing";
                }
            }
        }

        // Test image processing
        if (function_exists('imagecreatetruecolor')) {
            $this->log("✅ GD image processing available");
        } else {
            $this->errors[] = "GD image processing not available";
        }
    }

    private function reportResults() {
        $this->log("\n📊 Production Fix Results");
        $this->log("=========================");

        if (empty($this->errors)) {
            $this->log("🎉 SUCCESS: Production deployment fixed!");
            $this->log("\n📋 What was fixed:");
            $this->log("✅ Created missing directories (thumbnails, posts, logs)");
            $this->log("✅ Created default profile picture (default.png)");
            $this->log("✅ Set proper file permissions");
            $this->log("✅ Verified image processing capabilities");
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

        $this->log("\n🚀 Next Steps:");
        $this->log("1. Run database migration: php db_migrate.php");
        $this->log("2. Run post-deployment checks: php post_deploy.php");
        $this->log("3. Test profile picture upload functionality");
        $this->log("4. Remove this fix script from production");
    }

    private function log($message) {
        echo $message . PHP_EOL;
    }
}

// Run the production fix
$fix = new ProductionFix();
$success = $fix->fixProductionDeployment();

exit($success ? 0 : 1);
?>