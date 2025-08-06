# 🗄️ PHP MCP MySQL Server

[![PHP](https://img.shields.io/badge/PHP->=8.1-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![MCP](https://img.shields.io/badge/MCP-3.3-purple.svg)](https://github.com/php-mcp/server)
[![Tests](https://github.com/momodemo333/php-mcp-mysql/workflows/Tests/badge.svg)](https://github.com/momodemo333/php-mcp-mysql/actions)
[![Packagist](https://img.shields.io/packagist/v/momodemo333/php-mcp-mysql.svg)](https://packagist.org/packages/momodemo333/php-mcp-mysql)
[![Downloads](https://img.shields.io/packagist/dt/momodemo333/php-mcp-mysql.svg)](https://packagist.org/packages/momodemo333/php-mcp-mysql)

MySQL MCP Server for Claude Code - Secure and configurable MySQL integration via Model Context Protocol.

## 🙏 Acknowledgments

This project is built on top of the excellent [php-mcp/server](https://github.com/php-mcp/server) library. Special thanks to the MCP community for providing the foundation that makes this integration possible.

## 🚀 Quick Installation

### Via Composer (Recommended)

```bash
composer require momodemo333/php-mcp-mysql
```

### Claude Code Configuration

Add to your `.cursor/mcp.json`:

```json
{
    "mcpServers": {
        "mysql": {
            "command": "php",
            "args": ["vendor/momodemo333/php-mcp-mysql/bin/server.php"],
            "env": {
                "MYSQL_HOST": "127.0.0.1",
                "MYSQL_PORT": "3306",
                "MYSQL_USER": "your_user",
                "MYSQL_PASS": "your_password",
                "MYSQL_DB": "your_database"
            }
        }
    }
}
```

### Quick Test

```bash
# Test connection
php vendor/momodemo333/php-mcp-mysql/tests/test_connection.php

# Test MCP server
php vendor/momodemo333/php-mcp-mysql/tests/test_mcp_server.php
```

**🎉 That's it! Your MySQL MCP Server is ready!**

---

## ✨ Features

### 🛠️ Available MCP Tools

- **`mysql_list_databases`** - List all databases
- **`mysql_list_tables`** - List tables in a database
- **`mysql_describe_table`** - Describe table structure (columns, indexes, foreign keys)
- **`mysql_server_status`** - Get MySQL server status and health
- **`mysql_select`** - Execute secure SELECT queries
- **`mysql_insert`** - Insert data with validation
- **`mysql_update`** - Update data with mandatory conditions
- **`mysql_delete`** - Delete data with safety limits
- **`mysql_execute_query`** - Execute custom SQL queries

### 🔒 Security Features

- **SQL Injection Protection** - All queries use prepared statements
- **Operation Permissions** - Granular control (INSERT, UPDATE, DELETE)
- **Query Validation** - Dangerous keyword blocking
- **Connection Pooling** - Efficient resource management
- **Result Limiting** - Configurable result set limits
- **Schema Restrictions** - Limit access to specific databases

### ⚙️ Configuration Options

**Environment Variables:**
- `MYSQL_HOST`, `MYSQL_PORT`, `MYSQL_USER`, `MYSQL_PASS`, `MYSQL_DB`
- `ALLOW_INSERT_OPERATION`, `ALLOW_UPDATE_OPERATION`, `ALLOW_DELETE_OPERATION`
- `ALLOW_DDL_OPERATIONS` ⭐ - **New!** Authorize CREATE, ALTER, DROP operations
- `ALLOW_ALL_OPERATIONS` ⭐ - **New!** Super admin mode (use with caution)
- `MAX_RESULTS`, `QUERY_TIMEOUT`, `LOG_LEVEL`
- `CONNECTION_POOL_SIZE`, `ENABLE_PREPARED_STATEMENTS`

**Configuration Methods:**
1. **Environment Variables** (via MCP config)
2. **`.env` Files** (per project)
3. **CLI Arguments** (for testing)

---

## 📖 Documentation

### 📚 Complete Guides

- **[Quick Start](docs/quick-start.md)** - Get running in 5 minutes
- **[MCP Configuration](docs/mcp-configuration.md)** - Understanding MCP transports (`stdio`, `http`, `websocket`)
- **[Installation Guide](docs/installation.md)** - Detailed setup instructions
- **[MCP Tools Reference](docs/mcp-tools.md)** - Complete tool documentation
- **[Usage Examples](docs/examples.md)** - Practical examples
- **[Multi-Project Setup](docs/multi-project-setup.md)** - Configure for multiple projects
- **[Troubleshooting](docs/troubleshooting.md)** - Common issues and solutions

### 🔧 Configuration Examples

**Simple Configuration:**
```json
{
    "mcpServers": {
        "mysql": {
            "type": "stdio",
            "command": "php",
            "args": ["vendor/momodemo333/php-mcp-mysql/bin/server.php"],
            "env": {
                "MYSQL_HOST": "127.0.0.1",
                "MYSQL_USER": "myapp",
                "MYSQL_PASS": "password",
                "MYSQL_DB": "myapp_db"
            }
        }
    }
}
```

> **💡 MCP Transport Types**: The `"type": "stdio"` parameter specifies the communication method between your MCP client and the server. See **[MCP Configuration Guide](docs/mcp-configuration.md)** for complete details on `stdio`, `http`, and `websocket` transports.

**Multi-Environment Configuration:**
```json
{
    "mcpServers": {
        "mysql-dev": {
            "command": "php",
            "args": ["vendor/momodemo333/php-mcp-mysql/bin/server.php"],
            "env": {
                "MYSQL_HOST": "127.0.0.1",
                "MYSQL_USER": "dev_user",
                "MYSQL_PASS": "dev_pass",
                "MYSQL_DB": "myapp_dev",
                "ALLOW_INSERT_OPERATION": "true",
                "ALLOW_UPDATE_OPERATION": "true",
                "ALLOW_DELETE_OPERATION": "true",
                "LOG_LEVEL": "DEBUG"
            }
        },
        "mysql-prod": {
            "command": "php",
            "args": ["vendor/momodemo333/php-mcp-mysql/bin/server.php"],
            "env": {
                "MYSQL_HOST": "prod.example.com",
                "MYSQL_USER": "readonly_user",
                "MYSQL_PASS": "prod_pass",
                "MYSQL_DB": "myapp_prod",
                "ALLOW_INSERT_OPERATION": "false",
                "ALLOW_UPDATE_OPERATION": "false",
                "ALLOW_DELETE_OPERATION": "false",
                "MAX_RESULTS": "50",
                "LOG_LEVEL": "ERROR"
            }
        }
    }
}
```

---

## 🛡️ Security & Best Practices

### 🔐 Security Recommendations

1. **Use Read-Only Users in Production**
   ```sql
   CREATE USER 'readonly_user'@'%' IDENTIFIED BY 'secure_password';
   GRANT SELECT ON production_db.* TO 'readonly_user'@'%';
   FLUSH PRIVILEGES;
   ```

2. **Limit Database Access**
   ```bash
   ALLOWED_SCHEMAS=myapp_prod,myapp_logs
   ```

3. **Set Result Limits**
   ```bash
   MAX_RESULTS=100
   QUERY_TIMEOUT=10
   ```

4. **Use Environment Variables for Passwords**
   ```bash
   export MYSQL_PASS_PROD="$(security find-generic-password -s 'mysql-prod' -w)"
   ```

### 🔒 DDL Permissions (v1.1.0+)

**Three-level permission system:**

1. **Level 1 - CRUD Operations:**
   ```bash
   ALLOW_INSERT_OPERATION=true    # INSERT statements
   ALLOW_UPDATE_OPERATION=true    # UPDATE statements  
   ALLOW_DELETE_OPERATION=true    # DELETE statements
   ALLOW_TRUNCATE_OPERATION=false # TRUNCATE statements
   ```

2. **Level 2 - Schema Operations (NEW!):**
   ```bash
   ALLOW_DDL_OPERATIONS=true      # CREATE, ALTER, DROP tables/indexes
   ```

3. **Level 3 - Super Admin (NEW!):**
   ```bash
   ALLOW_ALL_OPERATIONS=true      # All operations (use with extreme caution)
   ```

**Example - Enable schema modifications:**
```bash
# Fix "Mot-clé non autorisé détecté: ALTER" errors
ALLOW_DDL_OPERATIONS=true
```

### ✅ Production Checklist

- [ ] Use dedicated MySQL user with minimal permissions
- [ ] Set `ALLOW_*_OPERATION=false` for production (except SELECT)
- [ ] **Carefully consider `ALLOW_DDL_OPERATIONS=false`** in production ⚠️
- [ ] **Never use `ALLOW_ALL_OPERATIONS=true`** in production ❌
- [ ] Configure `MAX_RESULTS` and `QUERY_TIMEOUT`
- [ ] Use `LOG_LEVEL=ERROR` in production
- [ ] Restrict `ALLOWED_SCHEMAS` to necessary databases
- [ ] Store passwords securely (environment variables)
- [ ] Enable `BLOCK_DANGEROUS_KEYWORDS=true`

---

## 🧪 Development & Testing

### 🚀 New Test Suite (v1.1.0+)

**Professional testing infrastructure with Docker:**

```bash
# Quick start - all tests with Docker MySQL
make test

# Development commands  
make test-unit              # Fast unit tests (5s)
make test-integration       # Integration tests with MySQL
make test-coverage          # Generate HTML coverage report
make clean                  # Clean Docker resources

# Advanced testing
./tests/scripts/docker-test-complete.sh -v -c    # Verbose + coverage
```

**Test Coverage:**
- 🧪 **29+ tests** (unit + integration)
- 🎯 **>90% coverage** of critical services  
- 🐳 **Automated Docker** MySQL environment
- 🔄 **CI/CD ready** with GitHub Actions

**Documentation:**
- 📖 [Complete Testing Guide](docs/TESTING.md)
- 🚀 [Quick Testing README](tests/README.md)

### Legacy Testing

```bash
# Copy environment template
cp .env.example .env
# Edit .env with your MySQL settings

# Run connection test
php tests/test_connection.php

# Run full MCP server test
php tests/test_mcp_server.php

# Setup test data
php scripts/setup_test_data.php
```

### Development Setup

```bash
# Clone repository
git clone https://github.com/momodemo333/php-mcp-mysql.git
cd php-mcp-mysql

# Install dependencies
composer install

# Copy configuration
cp .env.example .env
# Edit .env with your settings

# Run tests
composer test
```

---

## 📊 Usage with Claude Code

### Natural Language Examples

**Database Exploration:**
```
"Show me all tables in the database"
"What's the structure of the users table?"
"How many orders are in the database?"
```

**Data Analysis:**
```
"Find all users created in the last 30 days"
"Show me the top 5 best-selling products"
"What's the average order value by month?"
```

**Business Intelligence:**
```
"Analyze customer behavior patterns"
"Show sales trends for the last quarter"
"Find inactive users who haven't ordered in 6 months"
```

**Data Management:**
```
"Add a new user with email john@example.com"
"Update the user's email address"
"Clean up old temporary data"
```

---

## 🤝 Contributing

Contributions are welcome! Please read our contributing guidelines and:

1. **Fork** the repository
2. **Create** your feature branch: `git checkout -b feature/amazing-feature`
3. **Commit** your changes: `git commit -m 'Add amazing feature'`
4. **Push** to branch: `git push origin feature/amazing-feature`
5. **Open** a Pull Request

### Development Guidelines

- Follow PSR-12 coding standards
- Add tests for new features
- Update documentation
- Ensure security best practices

---

## 📄 License

This project is licensed under the **MIT License** - see the [LICENSE](LICENSE) file for details.

**MIT License Summary:**
- ✅ Commercial use
- ✅ Modification
- ✅ Distribution
- ✅ Private use
- ❌ Liability
- ❌ Warranty

---

## 🆘 Support

- **📖 Documentation**: [docs/](docs/)
- **🐛 Issues**: [GitHub Issues](https://github.com/momodemo333/php-mcp-mysql/issues)
- **💡 Feature Requests**: [GitHub Discussions](https://github.com/momodemo333/php-mcp-mysql/discussions)

---

## 🎯 Roadmap

- [ ] PostgreSQL support
- [ ] Advanced query caching
- [ ] Connection encryption (SSL/TLS)
- [ ] Query performance analytics
- [ ] Multi-database connection management
- [ ] GraphQL-style query building

---

**Made with ❤️ for the Claude Code community**

*Powered by [php-mcp/server](https://github.com/php-mcp/server)*