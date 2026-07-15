<?php
/**
 * KP-HUB Database Migration Script
 * Creates and updates database schema for production deployment
 */

class DatabaseMigration {
    private $pdo;
    private $migrations = [];
    private $errors = [];

    public function __construct() {
        $this->connectDatabase();
        $this->defineMigrations();
    }

    private function connectDatabase() {
        try {
            require_once __DIR__ . '/db.php';
            $this->pdo = $pdo;
            $this->log("✅ Database connection successful");
        } catch (Exception $e) {
            $this->errors[] = "Database connection failed: " . $e->getMessage();
        }
    }

    private function defineMigrations() {
        $this->migrations = [
            'create_schema_migrations_table' => [
                'description' => 'Create schema_migrations table to track migrations',
                'sql' => "
                    CREATE TABLE IF NOT EXISTS schema_migrations (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        migration_name VARCHAR(191) NOT NULL UNIQUE,
                        applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        status ENUM('success', 'failed') DEFAULT 'success',
                        error_message TEXT NULL,
                        INDEX idx_applied_at (applied_at)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                "
            ],

            'create_users_table' => [
                'description' => 'Create users table with all required columns',
                'sql' => "
                    CREATE TABLE IF NOT EXISTS users (
                        user_id INT PRIMARY KEY AUTO_INCREMENT,
                        email VARCHAR(255) NOT NULL UNIQUE,
                        password VARCHAR(255) NOT NULL,
                        facility VARCHAR(255) NOT NULL,
                        role ENUM('Admin', 'Head', 'Member') NOT NULL,
                        can_create_folder TINYINT(1) DEFAULT 1,
                        can_delete TINYINT(1) DEFAULT 1,
                        can_generate_report TINYINT(1) DEFAULT 1,
                        profile_picture VARCHAR(255) NULL,
                        folder_id VARCHAR(255) NULL,
                        can_delete_files TINYINT(1) DEFAULT 1,
                        INDEX idx_email (email)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                "
            ],

            'update_users_table_schema' => [
                'description' => 'Add missing columns to existing users table',
                'sql' => [
                    "ALTER TABLE users ADD COLUMN can_create_folder TINYINT(1) DEFAULT 1;",
                    "ALTER TABLE users ADD COLUMN can_delete TINYINT(1) DEFAULT 1;",
                    "ALTER TABLE users ADD COLUMN can_generate_report TINYINT(1) DEFAULT 1;",
                    "ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) NULL;",
                    "ALTER TABLE users ADD COLUMN folder_id VARCHAR(255) NULL;",
                    "ALTER TABLE users ADD COLUMN can_delete_files TINYINT(1) DEFAULT 1;"
                ]
            ],

            'create_posts_table' => [
                'description' => 'Create posts table for content management',
                'sql' => "
                    CREATE TABLE IF NOT EXISTS posts (
                        post_id INT PRIMARY KEY AUTO_INCREMENT,
                        user_id INT NOT NULL,
                        title VARCHAR(255) NOT NULL DEFAULT 'Untitled Post',
                        post_type ENUM('text', 'file', 'folder') NOT NULL,
                        content TEXT NULL,
                        file_name VARCHAR(255) NULL,
                        file_url VARCHAR(500) NULL,
                        file_type VARCHAR(50) NULL,
                        links TEXT NULL,
                        file_id VARCHAR(255) NULL,
                        folder_id VARCHAR(255) NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        INDEX idx_user_id (user_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                "
            ],

            'create_newsfeed_sections_table' => [
                'description' => 'Create newsfeed_sections table for facility accordion sections',
                'sql' => "
                    CREATE TABLE IF NOT EXISTS newsfeed_sections (
                        section_id INT PRIMARY KEY AUTO_INCREMENT,
                        facility VARCHAR(255) NOT NULL,
                        title VARCHAR(255) NOT NULL,
                        sort_order INT NOT NULL DEFAULT 0,
                        created_by INT NULL,
                        updated_by INT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        INDEX idx_facility_sort (facility, sort_order),
                        INDEX idx_created_by (created_by)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                "
            ],

            'create_newsfeed_section_links_table' => [
                'description' => 'Create newsfeed_section_links table for accordion link items',
                'sql' => "
                    CREATE TABLE IF NOT EXISTS newsfeed_section_links (
                        link_id INT PRIMARY KEY AUTO_INCREMENT,
                        section_id INT NOT NULL,
                        label VARCHAR(255) NOT NULL,
                        url VARCHAR(500) NOT NULL,
                        sort_order INT NOT NULL DEFAULT 0,
                        created_by INT NULL,
                        updated_by INT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        INDEX idx_section_sort (section_id, sort_order),
                        INDEX idx_section_id (section_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                "
            ],

            'create_post_attachments_table' => [
                'description' => 'Create post_attachments table for file attachments',
                'sql' => "
                    CREATE TABLE IF NOT EXISTS post_attachments (
                        attachment_id INT PRIMARY KEY AUTO_INCREMENT,
                        post_id INT NOT NULL,
                        file_name VARCHAR(255) NOT NULL,
                        file_url VARCHAR(500) NOT NULL,
                        file_type VARCHAR(50) NOT NULL,
                        file_size INT DEFAULT 0,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_post_id (post_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                "
            ],

            'create_post_reactions_table' => [
                'description' => 'Create post_reactions table for user reactions',
                'sql' => "
                    CREATE TABLE IF NOT EXISTS post_reactions (
                        reaction_id INT PRIMARY KEY AUTO_INCREMENT,
                        post_id INT NOT NULL,
                        user_id INT NOT NULL,
                        reaction_type ENUM('like', 'love', 'celebrate', 'insightful') NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        UNIQUE KEY unique_user_post (post_id, user_id),
                        INDEX idx_user_id (user_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                "
            ],

            'create_notifications_table' => [
                'description' => 'Create notifications table for user notifications',
                'sql' => "
                    CREATE TABLE IF NOT EXISTS notifications (
                        notification_id INT PRIMARY KEY AUTO_INCREMENT,
                        user_id INT NOT NULL,
                        type ENUM('new_post', 'post_reaction') NOT NULL,
                        message TEXT NOT NULL,
                        post_id INT NULL,
                        from_user_id INT NULL,
                        is_read TINYINT(1) DEFAULT 0,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_user_id (user_id),
                        INDEX idx_post_id (post_id),
                        INDEX idx_from_user_id (from_user_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                "
            ],

            'create_upload_logs_table' => [
                'description' => 'Create upload_logs table for tracking file uploads',
                'sql' => "
                    CREATE TABLE IF NOT EXISTS upload_logs (
                        log_id INT PRIMARY KEY AUTO_INCREMENT,
                        user_id INT NOT NULL,
                        filename VARCHAR(255) NOT NULL,
                        file_size BIGINT NULL,
                        file_type VARCHAR(50) NULL,
                        upload_location VARCHAR(500) NULL,
                        upload_type ENUM('post_attachment', 'profile_picture', 'folder_upload') DEFAULT 'post_attachment',
                        ip_address VARCHAR(45) NULL,
                        user_agent TEXT NULL,
                        upload_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_upload_time (upload_time),
                        INDEX idx_user_id (user_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                "
            ],

            'create_file_downloads_table' => [
                'description' => 'Create file_downloads table for tracking downloads',
                'sql' => "
                    CREATE TABLE IF NOT EXISTS file_downloads (
                        download_id INT PRIMARY KEY AUTO_INCREMENT,
                        post_id INT NOT NULL,
                        user_id INT NULL,
                        download_count INT DEFAULT 1,
                        last_downloaded TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        UNIQUE KEY unique_post_user (post_id, user_id),
                        INDEX idx_user_id (user_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                "
            ],

            'create_feedback_table' => [
                'description' => 'Create feedback table for user feedback',
                'sql' => "
                    CREATE TABLE IF NOT EXISTS feedback (
                        feedback_id INT PRIMARY KEY AUTO_INCREMENT,
                        user_id INT NULL,
                        name VARCHAR(100) NULL,
                        email VARCHAR(100) NULL,
                        rating INT NOT NULL,
                        category VARCHAR(50) NOT NULL,
                        subject VARCHAR(200) NOT NULL,
                        message TEXT NOT NULL,
                        page VARCHAR(100) NULL,
                        ip_address VARCHAR(45) NULL,
                        user_agent TEXT NULL,
                        status ENUM('pending', 'reviewed', 'resolved', 'dismissed') DEFAULT 'pending',
                        admin_response TEXT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        INDEX idx_rating (rating),
                        INDEX idx_category (category),
                        INDEX idx_status (status),
                        INDEX idx_created_at (created_at),
                        INDEX idx_user_id (user_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                "
            ],

            'create_public_visits_table' => [
                'description' => 'Create public_visits table for tracking public page visits',
                'sql' => "
                    CREATE TABLE IF NOT EXISTS public_visits (
                        visit_id INT PRIMARY KEY AUTO_INCREMENT,
                        page VARCHAR(100) NOT NULL,
                        ip_address VARCHAR(45) NULL,
                        user_agent TEXT NULL,
                        visit_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_page (page),
                        INDEX idx_visit_time (visit_time),
                        INDEX idx_ip_address (ip_address)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                "
            ],

            'create_page_visits_table' => [
                'description' => 'Create page_visits table for internal page tracking',
                'sql' => "
                    CREATE TABLE IF NOT EXISTS page_visits (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        page VARCHAR(100) NOT NULL UNIQUE,
                        visit_count INT DEFAULT 0,
                        last_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        UNIQUE KEY unique_page (page)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                "
            ]
        ];
    }

    public function runMigrations() {
        if (!$this->pdo) {
            $this->log("❌ Cannot run migrations - no database connection");
            return false;
        }

        $this->log("🚀 Starting Database Migration");
        $this->log("==============================");

        $successCount = 0;
        $totalCount = count($this->migrations);

        foreach ($this->migrations as $migrationName => $migration) {
            if ($this->isMigrationApplied($migrationName)) {
                $this->log("⏭️  Skipping: $migrationName (already applied)");
                $successCount++;
                continue;
            }

            $this->log("📋 Running: {$migration['description']}");

            if ($this->executeMigration($migrationName, $migration)) {
                $successCount++;
                $this->log("✅ Success: $migrationName");
            } else {
                $this->log("❌ Failed: $migrationName");
            }
        }

        $this->log("\n📊 Migration Results");
        $this->log("===================");
        $this->log("Total migrations: $totalCount");
        $this->log("Successful: $successCount");
        $this->log("Failed: " . ($totalCount - $successCount));

        if (!empty($this->errors)) {
            $this->log("\n❌ Errors:");
            foreach ($this->errors as $error) {
                $this->log("   - $error");
            }
        }

        return empty($this->errors);
    }

    private function isMigrationApplied($migrationName) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id FROM schema_migrations
                WHERE migration_name = ? AND status = 'success'
            ");
            $stmt->execute([$migrationName]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            // If schema_migrations table doesn't exist yet, assume not applied
            return false;
        }
    }

    private function executeMigration($migrationName, $migration) {
        // Check if this is a schema update migration (doesn't use transactions)
        $isSchemaUpdate = strpos($migrationName, 'update_') === 0 || strpos($migrationName, 'alter_') === 0;

        $transactionStarted = false;
        $allSkipped = true;

        try {
            if (!$isSchemaUpdate) {
                $this->pdo->beginTransaction();
                $transactionStarted = true;
            }

            // Execute the migration SQL (can be string or array)
            $sqlStatements = is_array($migration['sql']) ? $migration['sql'] : [$migration['sql']];

            foreach ($sqlStatements as $sql) {
                $sql = trim($sql);
                if (!empty($sql)) {
                    try {
                        $this->pdo->exec($sql);
                        $allSkipped = false; // At least one statement executed successfully
                    } catch (Exception $sqlError) {
                        // Check if it's a "column already exists" error, which we can ignore
                        if (strpos($sqlError->getMessage(), 'Duplicate column name') !== false ||
                            strpos($sqlError->getMessage(), 'already exists') !== false) {
                            $this->log("   ℹ️  Column already exists, skipping: " . substr($sql, 0, 50) . "...");
                            continue;
                        }
                        // Re-throw other errors
                        throw $sqlError;
                    }
                }
            }

            // If all statements were skipped (columns already exist), still mark as successful
            if ($allSkipped) {
                $this->log("   ℹ️  All columns already exist, migration successful");
            }

            // Record the migration as successful
            $stmt = $this->pdo->prepare("
                INSERT INTO schema_migrations (migration_name, status)
                VALUES (?, 'success')
                ON DUPLICATE KEY UPDATE status = 'success', applied_at = CURRENT_TIMESTAMP
            ");
            $stmt->execute([$migrationName]);

            if ($transactionStarted) {
                $this->pdo->commit();
            }

            return true;

        } catch (Exception $e) {
            if ($transactionStarted) {
                try {
                    $this->pdo->rollBack();
                } catch (Exception $rollbackError) {
                    // Ignore rollback errors - transaction might have been auto-committed
                }
            }

            // Record the migration as failed
            try {
                $stmt = $this->pdo->prepare("
                    INSERT INTO schema_migrations (migration_name, status, error_message)
                    VALUES (?, 'failed', ?)
                    ON DUPLICATE KEY UPDATE status = 'failed', error_message = ?, applied_at = CURRENT_TIMESTAMP
                ");
                $stmt->execute([$migrationName, $e->getMessage(), $e->getMessage()]);
            } catch (Exception $recordError) {
                // If we can't even record the error, add it to our error list
                $this->errors[] = "Failed to record migration error: " . $recordError->getMessage();
            }

            $this->errors[] = "Migration '$migrationName' failed: " . $e->getMessage();
            return false;
        }
    }

    public function createAdminUser() {
        $this->log("\n👤 Creating Default Admin User");

        try {
            // Check if admin user already exists
            $stmt = $this->pdo->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->execute(['admin@kphub.com']);
            if ($stmt->rowCount() > 0) {
                $this->log("ℹ️  Admin user already exists");
                return true;
            }

            // Create default admin user
            $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("
                INSERT INTO users (
                    email, password, facility, role,
                    can_create_folder, can_delete, can_generate_report, can_delete_files
                ) VALUES (?, ?, ?, ?, 1, 1, 1, 1)
            ");
            $stmt->execute([
                'admin@kphub.com',
                $hashedPassword,
                'KP-HUB Administration',
                'Admin'
            ]);

            $this->log("✅ Default admin user created");
            $this->log("   Email: admin@kphub.com");
            $this->log("   Password: admin123");
            $this->log("   ⚠️  CHANGE THIS PASSWORD AFTER FIRST LOGIN!");

            return true;

        } catch (Exception $e) {
            $this->errors[] = "Failed to create admin user: " . $e->getMessage();
            return false;
        }
    }

    public function verifySchema() {
        $this->log("\n🔍 Verifying Database Schema");

        $requiredTables = [
            'users', 'posts', 'post_attachments', 'post_reactions',
            'notifications', 'upload_logs', 'file_downloads',
            'feedback', 'public_visits', 'page_visits', 'schema_migrations'
        ];

        $missingTables = [];

        foreach ($requiredTables as $table) {
            try {
                $stmt = $this->pdo->query("SELECT 1 FROM `$table` LIMIT 1");
                $this->log("✅ Table exists: $table");

                // Check table structure for critical tables
                if ($table === 'users') {
                    $this->verifyUsersTableStructure();
                }

            } catch (Exception $e) {
                $missingTables[] = $table;
                $this->log("❌ Table missing: $table");
            }
        }

        if (!empty($missingTables)) {
            $this->errors[] = "Missing tables: " . implode(', ', $missingTables);
            return false;
        }

        $this->log("✅ All required tables exist");
        return true;
    }

    private function verifyUsersTableStructure() {
        try {
            $stmt = $this->pdo->query("DESCRIBE users");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $existingColumns = array_column($columns, 'Field');

            $requiredColumns = [
                'user_id', 'email', 'password', 'facility', 'role'
            ];

            $recommendedColumns = [
                'can_create_folder', 'can_delete', 'can_generate_report',
                'profile_picture', 'folder_id', 'can_delete_files'
            ];

            $missingRequired = array_diff($requiredColumns, $existingColumns);
            $missingRecommended = array_diff($recommendedColumns, $existingColumns);

            if (!empty($missingRequired)) {
                $this->errors[] = "Users table missing required columns: " . implode(', ', $missingRequired);
            }

            if (!empty($missingRecommended)) {
                $this->log("⚠️  Users table missing recommended columns: " . implode(', ', $missingRecommended));
                $this->log("   These will be added by the schema update migration");
            } else {
                $this->log("✅ Users table has all recommended columns");
            }

        } catch (Exception $e) {
            $this->errors[] = "Failed to verify users table structure: " . $e->getMessage();
        }
    }

    private function log($message) {
        echo $message . PHP_EOL;
    }
}

// Run the database migration
$migration = new DatabaseMigration();

if ($migration->runMigrations()) {
    $migration->createAdminUser();
    $migration->verifySchema();

    echo "\n🎉 DATABASE MIGRATION COMPLETED SUCCESSFULLY!\n";
    echo "📋 Next steps:\n";
    echo "1. Update database connection settings in production\n";
    echo "2. Test the application functionality\n";
    echo "3. Remove this migration script from production\n";
    exit(0);
} else {
    echo "\n❌ DATABASE MIGRATION FAILED!\n";
    echo "Check the errors above and fix them before proceeding.\n";
    exit(1);
}
?>