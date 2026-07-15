<?php
/**
 * Database Migration System for KP-HUB
 *
 * This system provides version-controlled database schema updates
 * that can be safely applied during deployment while preserving data.
 */

require 'db.php';

class DatabaseMigrator {
    private $pdo;
    private $migrationsPath;

    public function __construct($pdo, $migrationsPath = 'migrations/') {
        $this->pdo = $pdo;
        $this->migrationsPath = $migrationsPath;
        $this->ensureMigrationsTable();
    }

    /**
     * Ensure the migrations tracking table exists
     */
    private function ensureMigrationsTable() {
        $sql = "
            CREATE TABLE IF NOT EXISTS schema_migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration_name VARCHAR(191) NOT NULL,
                applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                status ENUM('success', 'failed') DEFAULT 'success',
                error_message TEXT NULL,
                UNIQUE KEY unique_migration_name (migration_name),
                INDEX idx_applied_at (applied_at)
            )
        ";

        try {
            $this->pdo->exec($sql);
        } catch (PDOException $e) {
            die("Failed to create migrations table: " . $e->getMessage());
        }
    }

    /**
     * Get list of applied migrations
     */
    public function getAppliedMigrations() {
        $stmt = $this->pdo->query("SELECT migration_name FROM schema_migrations WHERE status = 'success' ORDER BY applied_at ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get list of pending migrations
     */
    public function getPendingMigrations() {
        $applied = $this->getAppliedMigrations();
        $allMigrations = $this->getAllMigrations();

        return array_diff($allMigrations, $applied);
    }

    /**
     * Get all available migration files
     */
    public function getAllMigrations() {
        $migrations = [];

        if (!is_dir($this->migrationsPath)) {
            return $migrations;
        }

        $files = glob($this->migrationsPath . '*.php');
        foreach ($files as $file) {
            $filename = basename($file, '.php');
            // Extract version and name (format: YYYYMMDDHHMMSS_description.php)
            if (preg_match('/^(\d{14})_(.+)$/', $filename, $matches)) {
                $migrations[] = $filename;
            }
        }

        sort($migrations);
        return $migrations;
    }

    /**
     * Apply a single migration
     */
    public function applyMigration($migrationName) {
        $file = $this->migrationsPath . $migrationName . '.php';

        if (!file_exists($file)) {
            throw new Exception("Migration file not found: $file");
        }

        require_once $file;

        // Migration class name should match the filename
        $className = $this->getMigrationClassName($migrationName);

        if (!class_exists($className)) {
            throw new Exception("Migration class not found: $className");
        }

        $migration = new $className($this->pdo);

        if (!method_exists($migration, 'up')) {
            throw new Exception("Migration class must have an 'up' method");
        }

        $this->pdo->beginTransaction();

        try {
            echo "Applying migration: $migrationName\n";

            // Run the migration
            $migration->up();

            // Record successful migration
            $stmt = $this->pdo->prepare("INSERT INTO schema_migrations (migration_name, status) VALUES (?, 'success')");
            $stmt->execute([$migrationName]);

            $this->pdo->commit();
            echo "✅ Migration applied successfully: $migrationName\n";

        } catch (Exception $e) {
            $this->pdo->rollBack();

            // Record failed migration
            $stmt = $this->pdo->prepare("INSERT INTO schema_migrations (migration_name, status, error_message) VALUES (?, 'failed', ?) ON DUPLICATE KEY UPDATE status = 'failed', error_message = ?");
            $stmt->execute([$migrationName, $e->getMessage(), $e->getMessage()]);

            throw $e;
        }
    }

    /**
     * Apply all pending migrations
     */
    public function migrate() {
        $pending = $this->getPendingMigrations();

        if (empty($pending)) {
            echo "✅ No pending migrations\n";
            return;
        }

        echo "Found " . count($pending) . " pending migrations\n";

        foreach ($pending as $migration) {
            $this->applyMigration($migration);
        }

        echo "✅ All migrations applied successfully\n";
    }

    /**
     * Rollback the last migration
     */
    public function rollback($steps = 1) {
        $applied = $this->getAppliedMigrations();

        if (empty($applied)) {
            echo "No migrations to rollback\n";
            return;
        }

        $toRollback = array_slice(array_reverse($applied), 0, $steps);

        foreach ($toRollback as $migrationName) {
            $this->rollbackMigration($migrationName);
        }
    }

    /**
     * Rollback a specific migration
     */
    private function rollbackMigration($migrationName) {
        $file = $this->migrationsPath . $migrationName . '.php';

        if (!file_exists($file)) {
            throw new Exception("Migration file not found: $file");
        }

        require_once $file;

        $className = $this->getMigrationClassName($migrationName);
        $migration = new $className($this->pdo);

        if (!method_exists($migration, 'down')) {
            echo "⚠️  Migration $migrationName has no rollback method, skipping\n";
            return;
        }

        $this->pdo->beginTransaction();

        try {
            echo "Rolling back migration: $migrationName\n";

            $migration->down();

            // Remove from migrations table
            $stmt = $this->pdo->prepare("DELETE FROM schema_migrations WHERE migration_name = ?");
            $stmt->execute([$migrationName]);

            $this->pdo->commit();
            echo "✅ Migration rolled back: $migrationName\n";

        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Get migration class name from filename
     */
    private function getMigrationClassName($migrationName) {
        // Convert snake_case to CamelCase
        $parts = explode('_', $migrationName);
        $className = '';

        foreach ($parts as $part) {
            if (is_numeric($part)) continue; // Skip timestamp parts
            $className .= ucfirst($part);
        }

        return $className . 'Migration';
    }

    /**
     * Create a new migration file
     */
    public function createMigration($name) {
        $timestamp = date('YmdHis');
        $filename = $timestamp . '_' . $name . '.php';
        $filepath = $this->migrationsPath . $filename;

        $className = $this->getMigrationClassName($timestamp . '_' . $name);

        $template = "<?php

class {$className} {
    private \$pdo;

    public function __construct(\$pdo) {
        \$this->pdo = \$pdo;
    }

    public function up() {
        // Add your migration SQL here
        // Example:
        // \$this->pdo->exec(\"
        //     ALTER TABLE posts ADD COLUMN new_column VARCHAR(255) NULL
        // \");
    }

    public function down() {
        // Add rollback SQL here
        // Example:
        // \$this->pdo->exec(\"
        //     ALTER TABLE posts DROP COLUMN new_column
        // \");
    }
}
";

        if (!is_dir($this->migrationsPath)) {
            mkdir($this->migrationsPath, 0755, true);
        }

        file_put_contents($filepath, $template);
        echo "Created migration: $filename\n";
        return $filename;
    }

    /**
     * Show migration status
     */
    public function status() {
        $applied = $this->getAppliedMigrations();
        $pending = $this->getPendingMigrations();
        $all = $this->getAllMigrations();

        echo "\nMigration Status:\n";
        echo "================\n\n";

        echo "Applied migrations (" . count($applied) . "):\n";
        foreach ($applied as $migration) {
            echo "  ✅ $migration\n";
        }

        echo "\nPending migrations (" . count($pending) . "):\n";
        foreach ($pending as $migration) {
            echo "  ⏳ $migration\n";
        }

        if (empty($applied) && empty($pending)) {
            echo "  No migrations found\n";
        }
    }
}

// CLI Interface
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $migrator = new DatabaseMigrator($pdo);

    $command = $argv[1] ?? 'status';
    $argument = $argv[2] ?? null;

    try {
        switch ($command) {
            case 'migrate':
                $migrator->migrate();
                break;

            case 'rollback':
                $steps = $argument ? (int)$argument : 1;
                $migrator->rollback($steps);
                break;

            case 'create':
                if (!$argument) {
                    echo "Usage: php migrate.php create <migration_name>\n";
                    exit(1);
                }
                $migrator->createMigration($argument);
                break;

            case 'status':
            default:
                $migrator->status();
                break;
        }
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>