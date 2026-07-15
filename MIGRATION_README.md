# KP-HUB Database Migration System

This system ensures your database schema stays in sync with your application code during deployments, while safely preserving all existing data.

## How It Works

The migration system uses timestamped migration files that contain `up()` and `down()` methods:
- `up()` - Applies the schema changes
- `down()` - Reverts the schema changes (for rollbacks)

## Deployment Process

### For New Deployments

1. **Deploy your code** to the server
2. **Run the deployment script**:
   ```bash
   php deploy.php
   ```

This will automatically apply any pending migrations and verify the database is ready.

### For Development

#### Creating New Migrations

When you need to make database schema changes:

```bash
php migrate.php create add_new_feature_to_table
```

This creates a new migration file in the `migrations/` directory with the current timestamp.

Edit the generated file to add your schema changes in the `up()` method and rollback logic in the `down()` method.

#### Applying Migrations

```bash
# Apply all pending migrations
php migrate.php migrate

# Check migration status
php migrate.php status
```

#### Rolling Back Migrations

```bash
# Rollback last migration
php migrate.php rollback

# Rollback multiple migrations
php migrate.php rollback 3
```

## Migration File Structure

Each migration file follows this format:

```php
<?php

class AddNewFeatureToPostsMigration {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function up() {
        // Add your migration SQL here
        $this->pdo->exec("
            ALTER TABLE posts ADD COLUMN new_column VARCHAR(255) NULL
        ");
    }

    public function down() {
        // Add rollback SQL here
        $this->pdo->exec("
            ALTER TABLE posts DROP COLUMN new_column
        ");
    }
}
```

## Best Practices

1. **Always test migrations** on a copy of your production database first
2. **Include rollback logic** (`down()` method) for every migration
3. **Use descriptive names** for migration files
4. **Keep migrations small** - one logical change per migration
5. **Don't modify existing migrations** after they've been applied in production
6. **Backup your database** before running migrations in production

## Safety Features

- **Idempotent**: Running migrations multiple times is safe
- **Transactional**: Each migration runs in a transaction
- **Tracked**: Applied migrations are recorded to prevent re-running
- **Rollback**: Failed migrations can be rolled back automatically
- **Validation**: Deployment script checks for required tables/columns

## Example Migration Scenarios

### Adding a New Column
```php
public function up() {
    $this->pdo->exec("
        ALTER TABLE posts
        ADD COLUMN published_at TIMESTAMP NULL,
        ADD INDEX idx_published_at (published_at)
    ");
}

public function down() {
    $this->pdo->exec("
        ALTER TABLE posts
        DROP COLUMN published_at
    ");
}
```

### Creating a New Table
```php
public function up() {
    $this->pdo->exec("
        CREATE TABLE notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            message TEXT NOT NULL,
            read_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id)
        )
    ");
}

public function down() {
    $this->pdo->exec("DROP TABLE notifications");
}
```

### Data Migration
```php
public function up() {
    // First add the column
    $this->pdo->exec("ALTER TABLE posts ADD COLUMN slug VARCHAR(255) NULL");

    // Then populate it
    $this->pdo->exec("
        UPDATE posts
        SET slug = CONCAT('post-', post_id)
        WHERE slug IS NULL
    ");

    // Add constraints
    $this->pdo->exec("ALTER TABLE posts ADD UNIQUE KEY unique_slug (slug)");
}

public function down() {
    $this->pdo->exec("ALTER TABLE posts DROP COLUMN slug");
}
```

## Troubleshooting

### Migration Fails
- Check the error message in the console
- Review the migration SQL for syntax errors
- Ensure you have proper database permissions
- Check if the table/column already exists

### Rollback Issues
- Some operations (like DROP TABLE) cannot be perfectly rolled back
- Data loss may occur with destructive operations
- Always backup before rolling back in production

### Permission Issues
- Ensure the database user has ALTER, CREATE, DROP permissions
- Check file permissions on migration files