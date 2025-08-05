# 🔧 Configuration MCP (Model Context Protocol)

Guide complet pour configurer le serveur MySQL MCP avec différents clients et transports.

## 📡 Comprendre les Transports MCP

Le **Model Context Protocol (MCP)** supporte plusieurs méthodes de communication entre le client et le serveur :

### 🔌 **Type: `stdio` (Standard Input/Output)**
- **Usage** : Communication via stdin/stdout
- **Cas d'usage** : Serveurs locaux, scripts, applications desktop
- **Avantages** : Simple, léger, idéal pour développement
- **Clients** : Claude Code, Cursor, IDEs locaux

```json
{
    "type": "stdio",
    "command": "php",
    "args": ["/path/to/server.php"]
}
```

### 🌐 **Type: `http` (HTTP REST API)**
- **Usage** : Communication via requêtes HTTP
- **Cas d'usage** : Serveurs web, APIs distantes, microservices
- **Avantages** : Scalable, standard web, load balancing
- **Clients** : Applications web, services cloud

```json
{
    "type": "http",
    "url": "https://your-domain.com/mcp-mysql",
    "headers": {
        "Authorization": "Bearer your-token"
    }
}
```

### ⚡ **Type: `websocket` (WebSocket)**
- **Usage** : Communication bidirectionnelle temps réel
- **Cas d'usage** : Applications interactives, streaming de données
- **Avantages** : Temps réel, persistent connection, efficace
- **Clients** : Applications web modernes, dashboards

```json
{
    "type": "websocket",
    "url": "wss://your-domain.com/mcp-mysql-ws"
}
```

## 🛠️ Configuration par Client MCP

### Claude Code (.claude/config.json)
```json
{
    "mcpServers": {
        "mysql-server": {
            "type": "stdio",
            "command": "php",
            "args": ["/absolute/path/to/php-mcp-mysql/bin/server.php"],
            "env": {
                "MYSQL_HOST": "localhost",
                "MYSQL_PORT": "3306",
                "MYSQL_USER": "username",
                "MYSQL_PASS": "password",
                "MYSQL_DB": "database"
            }
        }
    }
}
```

### Cursor (.cursor/mcp.json)
```json
{
    "mcpServers": {
        "mysql-server": {
            "type": "stdio",
            "command": "php",
            "args": ["/absolute/path/to/php-mcp-mysql/bin/server.php"],
            "env": {
                "MYSQL_HOST": "localhost",
                "MYSQL_PORT": "3306",
                "MYSQL_USER": "username",
                "MYSQL_PASS": "password",
                "MYSQL_DB": "database"
            }
        }
    }
}
```

### Zed (.zed/settings.json)
```json
{
    "context_servers": {
        "mysql-server": {
            "type": "stdio",
            "command": "php",
            "args": ["/absolute/path/to/php-mcp-mysql/bin/server.php"],
            "env": {
                "MYSQL_HOST": "localhost",
                "MYSQL_PORT": "3306",
                "MYSQL_USER": "username",
                "MYSQL_PASS": "password",
                "MYSQL_DB": "database"
            }
        }
    }
}
```

## 📋 Paramètres Obligatoires vs Optionnels

### ✅ **Obligatoires**
```json
{
    "type": "stdio",           // Transport à utiliser
    "command": "php",          // Commande pour lancer le serveur
    "args": ["path/to/server.php"]  // Chemin vers le script serveur
}
```

### ⚙️ **Variables d'environnement MySQL**
```json
{
    "env": {
        // Connexion (obligatoires)
        "MYSQL_HOST": "127.0.0.1",
        "MYSQL_PORT": "3306",
        "MYSQL_USER": "your_user",
        "MYSQL_PASS": "your_password",
        "MYSQL_DB": "your_database",
        
        // Permissions (optionnelles)
        "ALLOW_INSERT_OPERATION": "false",
        "ALLOW_UPDATE_OPERATION": "false", 
        "ALLOW_DELETE_OPERATION": "false",
        "ALLOW_TRUNCATE_OPERATION": "false",
        
        // Limites (optionnelles)
        "MAX_RESULTS": "1000",
        "QUERY_TIMEOUT": "30",
        "ALLOWED_SCHEMAS": "",
        
        // Sécurité (optionnelles)
        "BLOCK_DANGEROUS_KEYWORDS": "true",
        "ENABLE_QUERY_LOGGING": "true",
        
        // Performance (optionnelles)
        "CONNECTION_POOL_SIZE": "5",
        "CACHE_TTL": "300",
        "ENABLE_QUERY_CACHE": "true",
        
        // Debug (optionnelles)
        "LOG_LEVEL": "INFO",
        "LOG_FILE": ""
    }
}
```

## 🔍 Exemples de Configuration Complets

### 🏠 **Développement Local**
```json
{
    "mcpServers": {
        "mysql-dev": {
            "type": "stdio",
            "command": "php",
            "args": ["vendor/momodemo333/php-mcp-mysql/bin/server.php"],
            "env": {
                "MYSQL_HOST": "127.0.0.1",
                "MYSQL_PORT": "3306",
                "MYSQL_USER": "dev_user",
                "MYSQL_PASS": "dev_password",
                "MYSQL_DB": "myapp_dev",
                "ALLOW_INSERT_OPERATION": "true",
                "ALLOW_UPDATE_OPERATION": "true",
                "ALLOW_DELETE_OPERATION": "true",
                "MAX_RESULTS": "500",
                "LOG_LEVEL": "DEBUG"
            }
        }
    }
}
```

### 🚀 **Production (Lecture Seule)**
```json
{
    "mcpServers": {
        "mysql-prod": {
            "type": "stdio",
            "command": "php",
            "args": ["vendor/momodemo333/php-mcp-mysql/bin/server.php"],
            "env": {
                "MYSQL_HOST": "prod.example.com",
                "MYSQL_PORT": "3306",
                "MYSQL_USER": "readonly_user",
                "MYSQL_PASS": "readonly_password",
                "MYSQL_DB": "myapp_prod",
                "ALLOW_INSERT_OPERATION": "false",
                "ALLOW_UPDATE_OPERATION": "false",
                "ALLOW_DELETE_OPERATION": "false",
                "MAX_RESULTS": "100",
                "QUERY_TIMEOUT": "15",
                "LOG_LEVEL": "ERROR"
            }
        }
    }
}
```

### 🔄 **Multi-Environnements**
```json
{
    "mcpServers": {
        "mysql-dev": {
            "type": "stdio",
            "command": "php",
            "args": ["vendor/morodemo333/php-mcp-mysql/bin/server.php"],
            "env": {
                "MYSQL_HOST": "127.0.0.1",
                "MYSQL_DB": "myapp_dev",
                "ALLOW_INSERT_OPERATION": "true",
                "LOG_LEVEL": "DEBUG"
            }
        },
        "mysql-staging": {
            "type": "stdio",
            "command": "php", 
            "args": ["vendor/momodemo333/php-mcp-mysql/bin/server.php"],
            "env": {
                "MYSQL_HOST": "staging.example.com",
                "MYSQL_DB": "myapp_staging",
                "ALLOW_INSERT_OPERATION": "false",
                "LOG_LEVEL": "INFO"
            }
        }
    }
}
```

## 🛡️ Bonnes Pratiques de Configuration

### ✅ **Sécurité**
- **Jamais de credentials en dur** dans les fichiers versionnés
- **Permissions minimales** : lecture seule en production
- **Variables d'environnement** pour les credentials sensibles
- **Logs sécurisés** : pas de mots de passe dans les logs

### ⚡ **Performance**
- **Connexion pools** adaptés à votre charge
- **Limites de résultats** raisonnables (100-1000)
- **Timeouts courts** pour éviter les blocages
- **Cache activé** pour les requêtes répétitives

### 🔧 **Maintenance**
- **Logs structurés** avec niveaux appropriés  
- **Noms descriptifs** pour les serveurs MCP
- **Configuration par environnement** séparée
- **Documentation** des paramètres spécifiques

## 🚨 Dépannage Fréquent

### ❌ **Erreur: "Unknown type"**
```json
// ❌ Incorrect
{
    "command": "php"  // Manque "type"
}

// ✅ Correct  
{
    "type": "stdio",
    "command": "php"
}
```

### ❌ **Erreur: "Cannot connect to MySQL"**
```json
// Vérifiez les variables d'environnement
{
    "env": {
        "MYSQL_HOST": "127.0.0.1",  // ✅ IP correcte
        "MYSQL_PORT": "3306",       // ✅ Port en string
        "MYSQL_USER": "username",   // ✅ Utilisateur existant
        "MYSQL_PASS": "password"    // ✅ Mot de passe correct
    }
}
```

### ❌ **Erreur: "Server not found"**
```bash
# Vérifiez le chemin absolu
"args": ["/full/absolute/path/to/server.php"]

# Pas de chemin relatif
"args": ["./server.php"]  # ❌ Risque d'erreur
```

## 📚 Références

- **[MCP Specification](https://spec.modelcontextprotocol.io/)** - Spécification officielle
- **[Claude Code MCP](https://docs.anthropic.com/claude/docs/mcp)** - Documentation Claude
- **[PHP MCP Server](https://github.com/php-mcp/server)** - Framework PHP utilisé

---

**💡 Conseil** : Commencez toujours par une configuration simple avec `stdio` avant d'explorer les autres transports !