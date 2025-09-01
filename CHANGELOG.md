# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.2] - 2025-09-01

### 🐛 Fixed
- **Critical DDL Configuration Bug** - Fixed permissions DDL not working despite configuration (Mirror of php-mcp-mysql Issue #3)
  - `ALLOW_DDL_OPERATIONS=true` now actually enables CREATE, ALTER, DROP operations
  - `ALLOW_ALL_OPERATIONS=true` correctly overrides `BLOCK_DANGEROUS_KEYWORDS`
  - Missing configuration variables `ALLOW_DDL_OPERATIONS` and `ALLOW_ALL_OPERATIONS` added to `PostgreSqlServer.php`
  - Fixed security service logic where `ALLOW_ALL_OPERATIONS` didn't properly override dangerous keyword blocking
- ✅ **Resolution** - No more "Mot-clé non autorisé détecté: CREATE/ALTER/DROP" errors when properly configured

### 🧪 Added
- **Comprehensive DDL Test Suite** - Added `DDLPermissionsTest.php` with PostgreSQL-specific test cases
  - Tests for CREATE, ALTER, DROP operations with proper permissions
  - Tests for PostgreSQL-specific dangerous keywords (COPY, VACUUM, ANALYZE, CLUSTER, REINDEX)
  - Validation of ALLOW_ALL_OPERATIONS override behavior
  - Word boundary tests to prevent false positives

### 📊 Enhanced
- **Server Capabilities** - Updated `/server/capabilities` endpoint to show DDL and ALL operation status
- **Documentation** - Updated `CLAUDE.md` with detailed bug fix information and PostgreSQL-specific features

### Technical Details
- **Root Causes Fixed**:
  1. Configuration loading incomplete - DDL permission variables not loaded from environment
  2. Logic bug - ALLOW_ALL_OPERATIONS didn't override BLOCK_DANGEROUS_KEYWORDS properly
- **PostgreSQL-Specific Keywords**: GRANT, REVOKE, COPY, VACUUM, ANALYZE, CLUSTER, REINDEX, CHECKPOINT, SET ROLE, SET SESSION AUTHORIZATION
- **Commits**: [e2dfebd](https://github.com/momodemo333/php-mcp-postgresql/commit/e2dfebd)

### Migration
No action required for existing users. If you had `ALLOW_DDL_OPERATIONS=true` configured, it will now work correctly!

## [1.0.1] - 2025-08-12

### 🐛 Fixed
- **Critical SQL Syntax Fix** - Resolved issue #2: PostgreSQL queries now use correct double-quote syntax instead of MySQL backticks
  - Fixed INSERT operations failing with syntax errors
  - Fixed UPDATE operations with incorrect identifier quoting  
  - Fixed DELETE operations using wrong SQL syntax
  - All database modification operations now work correctly with PostgreSQL

### 🧹 Cleaned Up
- **MySQL References Removal** - Removed all obsolete MySQL references from PostgreSQL project
  - Removed `MIGRATION_PLAN.md` and `IMPLEMENTATION_STATUS.md` (migration artifacts)
  - Removed `docs/mysql-timeout-troubleshooting.md` (MySQL-specific documentation)
  - Updated all configuration examples from MySQL to PostgreSQL syntax
  - Updated environment variables from `MYSQL_*` to `PGSQL_*` throughout project
  - Updated `CLAUDE.md` with PostgreSQL-specific technical information

### ⚡ Improved
- **Project Consistency** - All configuration files now consistently use PostgreSQL standards
  - Configuration examples use port 5432 and PostgreSQL-specific settings
  - Scripts and test configurations updated to PostgreSQL namespace and variables
  - Documentation aligned with PostgreSQL project identity

## [1.0.0] - 2025-01-12

### 🎉 Initial Release

First stable release of PHP MCP PostgreSQL Server for Claude Code.

### Added
- **Core Features**
  - Full PostgreSQL database integration via Model Context Protocol (MCP)
  - Secure connection management with connection pooling
  - Comprehensive MCP tools for database operations
  - Support for PostgreSQL-specific features (JSONB, arrays, CTEs, window functions)

- **MCP Tools**
  - `pgsql_list_databases` - List all databases
  - `pgsql_list_tables` - List tables with metadata
  - `pgsql_describe_table` - Describe table structure with columns, indexes, and foreign keys
  - `pgsql_server_status` - Get PostgreSQL server status and health
  - `pgsql_select` - Execute secure SELECT queries
  - `pgsql_insert` - Insert data with validation
  - `pgsql_update` - Update data with mandatory conditions
  - `pgsql_delete` - Delete data with safety limits
  - `pgsql_execute_query` - Execute custom SQL queries

- **Security Features**
  - SQL injection protection with prepared statements
  - Granular operation permissions (INSERT, UPDATE, DELETE)
  - Query validation and dangerous keyword blocking
  - Result limiting and query timeouts
  - Schema access restrictions

- **DevOps & Testing**
  - Docker support with optimized Alpine-based image
  - Docker Compose configuration for testing
  - GitHub Actions CI/CD pipeline
  - Comprehensive test suite
  - Health checks and monitoring

- **Documentation**
  - Complete README with installation and usage instructions
  - Configuration examples for Claude Code
  - Security best practices guide
  - PostgreSQL-specific features documentation
  - Testing documentation

### Technical Details
- Built on php-mcp/server v3.3 framework
- PHP 8.1+ support (tested with 8.1 and 8.3)
- PostgreSQL 13+ support (tested with 13, 14, 15)
- PSR-4 autoloading
- Environment-based configuration

### Contributors
- @momodemo333 - Initial implementation
- Claude (Anthropic) - Development assistance

### License
MIT License - See [LICENSE](LICENSE) file for details

---

**Full Changelog**: https://github.com/momodemo333/php-mcp-postgresql/commits/v1.0.0