<?php
/**
 * Deployment Script for KP-HUB
 *
 * This script handles database migrations and other deployment tasks
 * Run this after deploying new code to ensure database is up to date
 */

require 'db.php';
require 'migrate.php';

echo "🚀 Starting KP-HUB Deployment\n";
echo "===============================\n\n";

// Initialize migrator
$migrator = new DatabaseMigrator($pdo);

try {
    // Check current migration status
    echo "Checking database migration status...\n";
    $pending = $migrator->getPendingMigrations();
    $applied = $migrator->getAppliedMigrations();

    echo "Applied migrations: " . count($applied) . "\n";
    echo "Pending migrations: " . count($pending) . "\n\n";

    if (!empty($pending)) {
        echo "Applying pending migrations...\n";
        $migrator->migrate();
    } else {
        echo "✅ Database is up to date\n";
    }

    // Additional deployment checks can be added here
    echo "\nRunning deployment checks...\n";

    // Check if required tables exist
    $requiredTables = ['users', 'posts', 'post_reactions', 'file_downloads', 'feedback', 'public_visits', 'upload_logs', 'newsfeed_sections', 'newsfeed_section_links'];
    foreach ($requiredTables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() == 0) {
            echo "⚠️  Warning: Table '$table' does not exist\n";
        } else {
            echo "✅ Table '$table' exists\n";
        }
    }

    // Check if required columns exist in posts table
    $requiredColumns = ['title', 'links'];
    foreach ($requiredColumns as $column) {
        $stmt = $pdo->query("SHOW COLUMNS FROM posts LIKE '$column'");
        if ($stmt->rowCount() == 0) {
            echo "⚠️  Warning: Column '$column' does not exist in posts table\n";
        } else {
            echo "✅ Column '$column' exists in posts table\n";
        }
    }

    echo "\n✅ Deployment completed successfully!\n";
    echo "Your KP-HUB application is ready to use.\n";

} catch (Exception $e) {
    echo "\n❌ Deployment failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>