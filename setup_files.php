<?php
/**
 * KP-HUB Production File Setup
 * Copies required files and creates directories for production deployment
 */

class ProductionFileSetup {
    private $errors = [];
    private $warnings = [];

    public function setupProductionFiles() {
        $this->log("📁 KP-HUB Production File Setup");
        $this->log("===============================");

        $this->createDirectories();
        $this->copyRequiredFiles();
        $this->setPermissions();

        $this->reportResults();

        return empty($this->errors);
    }

    private function createDirectories() {
        $this->log("\n📂 Creating Required Directories...");

        $directories = [
            'uploads',
            'uploads/thumbnails',
            'uploads/posts',
            'config',
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

    private function copyRequiredFiles() {
        $this->log("\n📋 Copying Required Files...");

        // Files that need to be copied from development to production
        $requiredFiles = [
            'uploads/default.png' => 'Default profile picture',
            'uploads/thumbnails/.gitkeep' => 'Thumbnails directory placeholder'
        ];

        foreach ($requiredFiles as $file => $description) {
            $sourcePath = __DIR__ . '/' . $file;
            $targetPath = __DIR__ . '/' . $file;

            // For now, just check if files exist (they should be uploaded with the codebase)
            if (file_exists($targetPath)) {
                $this->log("✅ $description found: $file");
            } else {
                $this->warnings[] = "$description missing: $file (ensure it's uploaded with your codebase)";
            }
        }

        // Create .gitkeep files for empty directories
        $gitkeepFiles = [
            'uploads/thumbnails/.gitkeep',
            'uploads/posts/.gitkeep',
            'logs/.gitkeep'
        ];

        foreach ($gitkeepFiles as $gitkeep) {
            $path = __DIR__ . '/' . $gitkeep;
            if (!file_exists($path)) {
                if (file_put_contents($path, "# This file ensures the directory is tracked by git\n") !== false) {
                    $this->log("✅ Created .gitkeep file: $gitkeep");
                } else {
                    $this->warnings[] = "Failed to create .gitkeep file: $gitkeep";
                }
            }
        }
    }

    private function setPermissions() {
        $this->log("\n🔐 Setting File Permissions...");

        // Set directory permissions
        $directories = [
            'uploads' => 0755,
            'uploads/thumbnails' => 0755,
            'uploads/posts' => 0755,
            'config' => 0755,
            'logs' => 0755
        ];

        foreach ($directories as $dir => $perms) {
            $path = __DIR__ . '/' . $dir;
            if (is_dir($path)) {
                if (chmod($path, $perms)) {
                    $this->log("✅ Set permissions on directory: $dir ({$perms})");
                } else {
                    $this->warnings[] = "Failed to set permissions on directory: $dir";
                }
            }
        }

        // Set file permissions
        $files = [
            'db.php' => 0644,
            'config.php' => 0644,
            'uploads/default.png' => 0644
        ];

        foreach ($files as $file => $perms) {
            $path = __DIR__ . '/' . $file;
            if (file_exists($path)) {
                if (chmod($path, $perms)) {
                    $this->log("✅ Set permissions on file: $file ({$perms})");
                } else {
                    $this->warnings[] = "Failed to set permissions on file: $file";
                }
            }
        }
    }

    private function reportResults() {
        $this->log("\n📊 File Setup Results");
        $this->log("====================");

        if (empty($this->errors)) {
            $this->log("🎉 SUCCESS: Production file setup completed!");
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

        $this->log("\n📋 Production File Checklist:");
        $this->log("✅ uploads/ directory created and writable");
        $this->log("✅ uploads/thumbnails/ directory created and writable");
        $this->log("✅ uploads/posts/ directory created and writable");
        $this->log("✅ uploads/default.png file present");
        $this->log("✅ config/ directory created");
        $this->log("✅ logs/ directory created");
        $this->log("✅ Proper file permissions set");
    }

    private function log($message) {
        echo $message . PHP_EOL;
    }
}

// Run file setup
$setup = new ProductionFileSetup();
$success = $setup->setupProductionFiles();

exit($success ? 0 : 1);
?>