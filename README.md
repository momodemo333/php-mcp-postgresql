# 🐘 PHP MCP PostgreSQL Server

[![PHP](https://img.shields.io/badge/PHP->=8.1-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![MCP](https://img.shields.io/badge/MCP-3.3-purple.svg)](https://github.com/php-mcp/server)
[![Status](https://img.shields.io/badge/Status-WIP-orange.svg)](IMPLEMENTATION_STATUS.md)

PostgreSQL MCP Server for Claude Code - Secure and configurable PostgreSQL integration via Model Context Protocol.

> 🎉 **Version 1.0.0-beta**: Core functionality complete! Production testing in progress.

## 🚀 Features

- 🔒 **Secure by Default** - Read-only operations by default, write operations require explicit permission
- 🎯 **PostgreSQL Native** - Full support for JSONB, arrays, CTEs, window functions, and more
- ⚡ **High Performance** - Connection pooling, prepared statements, query timeouts
- 📊 **Schema Introspection** - Explore databases, tables, columns, indexes
- 🔧 **Flexible Configuration** - Environment variables, .env files, multi-database support
- 🛡️ **Built-in Protection** - SQL injection prevention, dangerous keyword blocking, result limits

## 📦 Requirements

- PHP 8.1 or higher
- PostgreSQL 12 or higher  
- Composer
- PHP extensions: `ext-pdo`, `ext-pdo_pgsql`

## 🔧 Installation

### 1. Clone the Repository

```bash
git clone https://github.com/momodemo333/php-mcp-postgresql.git
cd php-mcp-postgresql
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Configure Environment

```bash
cp .env.example .env
# Edit .env with your PostgreSQL credentials
```

## ⚙️ Configuration

### Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|  
| `PGSQL_HOST` | PostgreSQL server host | localhost |
| `PGSQL_PORT` | PostgreSQL server port | 5432 |
| `PGSQL_USER` | Database username | postgres |
| `PGSQL_PASS` | Database password | (empty) |
| `PGSQL_DB` | Database name (optional for multi-db) | (empty) |
| `ALLOW_INSERT_OPERATION` | Enable INSERT queries | false |
| `ALLOW_UPDATE_OPERATION` | Enable UPDATE queries | false |
| `ALLOW_DELETE_OPERATION` | Enable DELETE queries | false |
| `QUERY_TIMEOUT` | Query timeout in seconds | 30 |
| `MAX_RESULTS` | Maximum rows returned | 1000 |
| `CONNECTION_POOL_SIZE` | Max concurrent connections | 5 |
| `LOG_LEVEL` | Logging level (DEBUG/INFO/WARN/ERROR) | INFO |

### Claude Code Configuration

Add to your Claude Code settings (`claude-code-settings.json`):

```json
{
  "mcpServers": {
    "postgresql": {
      "command": "php",
      "args": ["/absolute/path/to/php-mcp-postgresql/bin/server.php"],
      "type": "stdio",
      "env": {
        "PGSQL_HOST": "localhost",
        "PGSQL_PORT": "5432",
        "PGSQL_USER": "your_user",
        "PGSQL_PASS": "your_password",
        "PGSQL_DB": "your_database"
      }
    }
  }
}
```

See [examples/](examples/) for more configuration examples.

## 🛠️ Available Tools

### Database Management

- **`pgsql_list_databases`** - List all available databases
- **`pgsql_list_tables`** - List tables in a database
- **`pgsql_describe_table`** - Get detailed table structure
- **`pgsql_server_status`** - Get server status and statistics

### Query Execution

- **`pgsql_select`** - Execute SELECT queries safely
- **`pgsql_insert`** - Insert data (requires permission)
- **`pgsql_update`** - Update data (requires permission)  
- **`pgsql_delete`** - Delete data (requires permission)
- **`pgsql_execute_query`** - Execute custom queries (with validation)

## 🧪 Testing

### Quick Test

```bash
# Start test PostgreSQL database
./tests/start-test-db.sh

# Run all tests
./tests/run-tests.sh

# Stop test database
./tests/stop-test-db.sh
```

### Docker Test Environment

The project includes a complete Docker test environment:

```bash
# Start PostgreSQL container with test data
docker-compose -f docker-compose.test.yml up -d

# Run tests
php tests/test_connection.php
php tests/test_mcp_server.php

# Stop containers
docker-compose -f docker-compose.test.yml down
```

## 🔒 Security

### Default Security Features

- **Read-only by default** - All write operations disabled unless explicitly enabled
- **Prepared statements** - Prevents SQL injection attacks
- **Query validation** - Blocks dangerous keywords and operations
- **Result limits** - Prevents memory exhaustion from large result sets
- **Connection timeouts** - Prevents hanging queries
- **Schema restrictions** - Limit access to specific schemas

### Best Practices

1. Never enable write operations in production unless absolutely necessary
2. Use read-only database users when possible
3. Set appropriate `MAX_RESULTS` and `QUERY_TIMEOUT` values
4. Review logs regularly for suspicious activity
5. Keep the server updated with security patches

## 🎆 PostgreSQL-Specific Features

### JSONB Support

```sql
-- Query JSONB fields
SELECT * FROM users WHERE metadata->>'role' = 'admin';
SELECT * FROM products WHERE specifications @> '{"cpu": "Intel i7"}';
```

### Array Support

```sql
-- Query array fields
SELECT * FROM products WHERE 'electronics' = ANY(tags);
SELECT * FROM products WHERE tags && ARRAY['computers', 'portable'];
```

### Advanced Features

- Common Table Expressions (CTEs)
- Window functions
- RETURNING clause on INSERT/UPDATE/DELETE
- Materialized views
- Full-text search
- Custom types and domains

## 📝 Documentation

- [Installation Guide](docs/installation.md)
- [Configuration Reference](docs/mcp-configuration.md)
- [MCP Tools Documentation](docs/mcp-tools.md)
- [Troubleshooting](docs/troubleshooting.md)
- [Migration Plan](MIGRATION_PLAN.md)
- [Implementation Status](IMPLEMENTATION_STATUS.md)

## 🤝 Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Write tests for new functionality
4. Ensure all tests pass
5. Submit a pull request

## 📄 License

MIT License - see [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- Built with [php-mcp/server](https://github.com/php-mcp/server) framework
- Based on [php-mcp-mysql](https://github.com/momodemo333/php-mcp-mysql) architecture  
- Thanks to the MCP community for the protocol specification

## 💬 Support

- **Issues**: [GitHub Issues](https://github.com/momodemo333/php-mcp-postgresql/issues)
- **Discussions**: [GitHub Discussions](https://github.com/momodemo333/php-mcp-postgresql/discussions)

---

**Version**: 1.0.0-beta  
**Status**: Production Testing  
**Last Updated**: January 2025

