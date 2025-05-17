# Database Configuration and Installer Strategy

## Goal

The goal is to enable the Staff Directory application to be installed on a MySQL server with an existing database that cannot be changed, by:

1. Using a custom database name instead of the default "staff_dir"
2. Adding a custom prefix to all application tables

This will allow the application's tables to coexist with existing tables in the shared database without conflicts, while also providing flexibility to connect to any existing database.

## Current Database Structure

The Staff Directory application currently uses the following tables:

1. `companies` - Stores company information
2. `departments` - Stores department information
3. `staff_members` - Stores staff member information
4. `app_settings` - Stores application settings

These tables are currently accessed directly in SQL queries throughout the codebase without any prefix.

## Implementation Strategy

### 1. Database Configuration Enhancement

#### 1.1 Update Environment Configuration

Modify the environment configuration to include database name and table prefix settings:

```
# In staff_dir_env/.env
DB_HOST=localhost
DB_USER=your_username
DB_PASS=your_password
DB_NAME=existing_database_name  # Name of the existing database
DB_TABLE_PREFIX=sd_             # Prefix for all application tables
```

#### 1.2 Update Database Configuration

Update `config/database.php` to define constants for the database name and table prefix:

```php
// Define database name from environment variable or use default
define('DB_NAME', isset($_ENV['DB_NAME']) ? $_ENV['DB_NAME'] : 'staff_dir');

// Define table prefix from environment variable or use empty string as default
define('DB_TABLE_PREFIX', isset($_ENV['DB_TABLE_PREFIX']) ? $_ENV['DB_TABLE_PREFIX'] : '');
```

### 2. Implementation Approaches

There are three potential approaches to implement table prefixing:

#### Approach A: Direct SQL Query Modification

- **Description**: Modify all SQL queries in the codebase to include the prefix.
- **Pros**: Simple to understand, direct approach.
- **Cons**: Error-prone, requires changing many files, difficult to maintain.

#### Approach B: Table Name Constants

- **Description**: Define constants for each table name and use these constants in SQL queries.
- **Pros**: Centralizes table name definitions, easier to maintain.
- **Cons**: Still requires modifying all SQL queries, but with less risk of errors.

#### Approach C: Database Access Layer

- **Description**: Create a simple database access layer that automatically adds prefixes to table names.
- **Pros**: Most maintainable, minimal changes to existing code, future-proof.
- **Cons**: Requires more initial implementation effort.

### 3. Recommended Approach: Table Name Constants (Approach B)

This approach offers a good balance between implementation effort and maintainability:

#### 3.1 Create Table Name Constants

Add the following to `config/database.php`:

```php
// Define table names with prefixes
define('TABLE_COMPANIES', DB_TABLE_PREFIX . 'companies');
define('TABLE_DEPARTMENTS', DB_TABLE_PREFIX . 'departments');
define('TABLE_STAFF_MEMBERS', DB_TABLE_PREFIX . 'staff_members');
define('TABLE_APP_SETTINGS', DB_TABLE_PREFIX . 'app_settings');
```

#### 3.2 Update SQL Queries

Replace all direct table references in SQL queries with the constants:

```php
// Before
$sql = "SELECT * FROM companies WHERE id = ?";

// After
$sql = "SELECT * FROM " . TABLE_COMPANIES . " WHERE id = ?";
```

#### 3.3 Update SQL Schema Files

Modify the database schema files to use database name and prefix placeholders:

```sql
-- Before
CREATE DATABASE IF NOT EXISTS `staff_dir`;
USE `staff_dir`;

CREATE TABLE IF NOT EXISTS `companies` (
  -- columns
);

-- After
-- Database creation is now optional and configurable
-- {DB_CREATE}
CREATE DATABASE IF NOT EXISTS `{DB_NAME}`;
-- {/DB_CREATE}
USE `{DB_NAME}`;

CREATE TABLE IF NOT EXISTS `{PREFIX}companies` (
  -- columns
);
```

Create a script to process the SQL files and replace `{DB_NAME}` and `{PREFIX}` placeholders with actual values before execution. The `{DB_CREATE}` section can be conditionally included or excluded based on whether a new database should be created.

### 4. Implementation Plan

1. **Configuration Updates**:
   - Add database name and table prefix settings to environment configuration
   - Update database configuration to define database name and table name constants

2. **SQL Schema Updates**:
   - Modify `database/staff_dir.sql` and `database/staff_dir_clean.sql` to use `{DB_NAME}` and `{PREFIX}` placeholders
   - Create a script to process SQL files before execution

3. **Code Updates**:
   - Identify all SQL queries in the codebase
   - Replace direct table references with constants
   - Update foreign key constraints to use prefixed table names

4. **Installer Creation**:
   - Create `public/install.php` for automated installation
   - Implement form for configuring database settings
   - Add functionality to process and execute SQL schema
   - Include option to remove installer after successful setup

5. **Testing**:
   - Test with default database name and empty prefix to ensure backward compatibility
   - Test with custom database name and prefix to ensure proper functionality
   - Test installer with various configuration options

6. **Documentation**:
   - Update deployment documentation to include database name and prefix configuration
   - Document the installer usage and options
   - Update README.md with information about the database configuration and installer
   - Update devbook.md with technical details of the implementation
   - Document the process for future developers

## Files to Modify

### Configuration Files
- `staff_dir_env/.env_example` - Add DB_NAME and DB_TABLE_PREFIX variables
- `config/database.php` - Add database name and table prefix constants

### Database Schema Files
- `database/staff_dir.sql` - Update database name and table names with placeholders
- `database/staff_dir_clean.sql` - Update database name and table names with placeholders
- `database/process_sql.php` - Create new script to process SQL files

### PHP Files with SQL Queries
- `public/includes/functions.php` - Contains most database queries
- `public/admin/add.php` - Contains INSERT queries
- `public/admin/edit.php` - Contains UPDATE queries
- `public/admin/delete.php` - Contains DELETE queries
- `public/admin/departments.php` - Contains department-related queries
- `public/admin/companies.php` - Contains company-related queries
- `public/admin/settings.php` - Contains settings-related queries

### Documentation Files
- `README.md` - Update with database configuration and installer information
- `documentation/devbook.md` - Update with technical implementation details
- `documentation/FTP_Deployment_Guide.md` - Update deployment instructions

### New Files to Create
- `public/install.php` - Web-based installer (public-facing)
- `config/installer.php` - Installer helper functions (non-public)

## Potential Challenges

1. **Foreign Key Constraints**: When using prefixed table names, foreign key constraints must be updated to reference the correct prefixed tables.

2. **SQL Joins**: Queries with table joins need careful updating to ensure all table references include the prefix.

3. **Database Migration**: Existing installations will need a migration path to rename tables with the new prefix.

4. **Backward Compatibility**: The solution should work with both default and custom configurations.

5. **Installer Security**: The installer must be secured against unauthorized access and removed after installation.

6. **Database Permissions**: The installer needs to handle scenarios where the user may not have permissions to create databases.

## Installer Features

The web-based installer (`public/install.php`) will provide the following features:

1. **Configuration Form**:
   - Database connection settings (host, username, password)
   - Database name (existing database to use)
   - Table prefix option
   - Admin username/password setup

2. **Environment Setup**:
   - Write configuration to `.env` file
   - Test database connection

3. **Database Installation**:
   - Process SQL schema with correct database name and table prefixes
   - Create tables in the specified database
   - Initialize with default data

4. **Installation Verification**:
   - Check if all tables were created successfully
   - Verify database connection and permissions

5. **Security**:
   - Option to self-delete after successful installation
   - Basic authentication to prevent unauthorized access

## Conclusion

Implementing custom database configuration with table prefixing and a web-based installer will allow the Staff Directory application to be easily deployed in various environments, including shared database servers. The recommended approach using table name constants provides a good balance between implementation effort and maintainability.

By centralizing database configuration and providing an installer, we can ensure that the application works correctly regardless of the database name or table prefix configuration, while also simplifying the deployment process for end users.
