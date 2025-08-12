# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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