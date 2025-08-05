# 🏢 Configuration Multi-Projets

Guide complet pour configurer le serveur MCP MySQL dans plusieurs projets avec des bases de données et permissions différentes.

## 🎯 Philosophie Multi-Projets

### Principe de Base
**Un serveur MCP MySQL = Un serveur réutilisable + Configurations spécifiques par projet**

### Avantages
- ✅ **Code unique** : Un seul serveur à maintenir
- ✅ **Configuration flexible** : Chaque projet a ses paramètres
- ✅ **Sécurité granulaire** : Permissions par environnement
- ✅ **Isolation** : Projets indépendants
- ✅ **Évolutivité** : Facile d'ajouter de nouveaux projets

---

## 🏗️ Architectures Recommandées

### 🎯 Architecture 1 : Serveur Centralisé

**Structure :**
```
~/.local/mcp-servers/
└── mysql/                    # Serveur unique
    ├── server.php
    ├── server-cli.php
    ├── server-wrapper.php
    └── src/...

projects/
├── project-a/
│   ├── .env.mysql           # Config spécifique A
│   └── .cursor/mcp.json     # Référence vers serveur central
├── project-b/
│   ├── .env.mysql           # Config spécifique B
│   └── .cursor/mcp.json
└── project-c/
    ├── .env.mysql           # Config spécifique C
    └── .cursor/mcp.json
```

**Avantages :**
- ✅ Maintenance centralisée
- ✅ Mises à jour faciles
- ✅ Économie d'espace disque

**Configuration MCP :**
```json
{
    "mcpServers": {
        "mysql": {
            "command": "php",
            "args": [
                "~/.local/mcp-servers/mysql/server-wrapper.php",
                "/chemin/absolu/vers/project/.env.mysql"
            ]
        }
    }
}
```

### 🏢 Architecture 2 : Serveur par Projet

**Structure :**
```
projects/
├── project-a/
│   ├── mcp-servers/mysql/   # Copie dédiée
│   ├── .cursor/mcp.json
│   └── src/...
├── project-b/
│   ├── mcp-servers/mysql/   # Copie dédiée
│   ├── .cursor/mcp.json
│   └── src/...
```

**Avantages :**
- ✅ Isolation complète
- ✅ Versions différentes possibles
- ✅ Pas de dépendances externes

**Configuration MCP :**
```json
{
    "mcpServers": {
        "mysql": {
            "command": "php",
            "args": ["./mcp-servers/mysql/server.php"]
        }
    }
}
```

---

## 🔧 Méthodes de Configuration

### 1. ⭐ Variables d'Environnement MCP (Recommandée)

Configuration directe dans le fichier MCP, idéale pour des configurations simples.

#### Exemple : Agence avec 3 Clients

```json
{
    "mcpServers": {
        "mysql-client-a": {
            "command": "php",
            "args": ["/chemin/vers/mysql/server.php"],
            "env": {
                "MYSQL_HOST": "client-a-db.example.com",
                "MYSQL_PORT": "3306",
                "MYSQL_USER": "client_a_app",
                "MYSQL_PASS": "secure_pass_a",
                "MYSQL_DB": "client_a_production",
                "ALLOW_INSERT_OPERATION": "false",
                "ALLOW_UPDATE_OPERATION": "false",
                "ALLOW_DELETE_OPERATION": "false",
                "MAX_RESULTS": "100",
                "LOG_LEVEL": "WARN"
            }
        },
        "mysql-client-b": {
            "command": "php",
            "args": ["/chemin/vers/mysql/server.php"],
            "env": {
                "MYSQL_HOST": "client-b-db.example.com", 
                "MYSQL_PORT": "3306",
                "MYSQL_USER": "client_b_app",
                "MYSQL_PASS": "secure_pass_b",
                "MYSQL_DB": "client_b_production",
                "ALLOW_INSERT_OPERATION": "true",
                "ALLOW_UPDATE_OPERATION": "true",
                "ALLOW_DELETE_OPERATION": "false",
                "MAX_RESULTS": "500",
                "LOG_LEVEL": "INFO"
            }
        },
        "mysql-client-c": {
            "command": "php",
            "args": ["/chemin/vers/mysql/server.php"],
            "env": {
                "MYSQL_HOST": "127.0.0.1",
                "MYSQL_PORT": "3306", 
                "MYSQL_USER": "client_c_dev",
                "MYSQL_PASS": "dev_password",
                "MYSQL_DB": "client_c_staging",
                "ALLOW_INSERT_OPERATION": "true",
                "ALLOW_UPDATE_OPERATION": "true",
                "ALLOW_DELETE_OPERATION": "true",
                "MAX_RESULTS": "1000",
                "LOG_LEVEL": "DEBUG"
            }
        }
    }
}
```

### 2. 🔒 Fichiers .env par Projet (Plus Sécurisé)

Configuration via fichiers .env séparés, idéale pour la sécurité et la maintenance.

#### Structure par Projet

```bash
# project-a/.env.mysql
MYSQL_HOST=prod-db-01.internal
MYSQL_PORT=3306
MYSQL_USER=app_readonly
MYSQL_PASS=super_secure_password_a
MYSQL_DB=ecommerce_prod

# Permissions restrictives pour production
ALLOW_INSERT_OPERATION=false
ALLOW_UPDATE_OPERATION=false
ALLOW_DELETE_OPERATION=false
ALLOW_TRUNCATE_OPERATION=false

# Sécurité renforcée
MAX_RESULTS=50
QUERY_TIMEOUT=10
ALLOWED_SCHEMAS=ecommerce_prod
BLOCK_DANGEROUS_KEYWORDS=true
LOG_LEVEL=ERROR
```

```bash
# project-b/.env.mysql  
MYSQL_HOST=127.0.0.1
MYSQL_PORT=3306
MYSQL_USER=dev_user
MYSQL_PASS=dev_password_123
MYSQL_DB=crm_development

# Permissions complètes pour développement
ALLOW_INSERT_OPERATION=true
ALLOW_UPDATE_OPERATION=true
ALLOW_DELETE_OPERATION=true

# Configuration développement
MAX_RESULTS=1000
QUERY_TIMEOUT=30
LOG_LEVEL=DEBUG
ENABLE_QUERY_LOGGING=true
```

#### Configuration MCP Correspondante

```json
{
    "mcpServers": {
        "mysql-prod": {
            "command": "php",
            "args": [
                "/chemin/vers/mysql/server-wrapper.php",
                "/chemin/absolu/vers/project-a/.env.mysql"
            ]
        },
        "mysql-dev": {
            "command": "php", 
            "args": [
                "/chemin/vers/mysql/server-wrapper.php",
                "/chemin/absolu/vers/project-b/.env.mysql"
            ]
        }
    }
}
```

### 3. 🛠️ Arguments CLI (Développement/Tests)

Configuration via arguments de ligne de commande, pratique pour les tests rapides.

```json
{
    "mcpServers": {
        "mysql-test": {
            "command": "php",
            "args": [
                "/chemin/vers/mysql/server-cli.php",
                "--host=127.0.0.1",
                "--port=3306",
                "--user=test_user",
                "--pass=test_password", 
                "--db=test_database",
                "--allow-insert=true",
                "--allow-update=true",
                "--allow-delete=true",
                "--log-level=DEBUG"
            ]
        }
    }
}
```

---

## 🎨 Cas d'Usage Concrets

### 🚀 Startup : Dev → Staging → Production

#### Architecture
```
startup-app/
├── .cursor/
│   └── mcp.json             # 3 serveurs (dev/staging/prod)
├── config/
│   ├── .env.mysql.dev       # Base locale
│   ├── .env.mysql.staging   # Serveur de test
│   └── .env.mysql.prod      # Production (readonly)
└── src/...
```

#### Configuration MCP
```json
{
    "mcpServers": {
        "mysql-dev": {
            "command": "php",
            "args": [
                "~/.local/mcp-servers/mysql/server-wrapper.php",
                "./config/.env.mysql.dev"
            ]
        },
        "mysql-staging": {
            "command": "php",
            "args": [
                "~/.local/mcp-servers/mysql/server-wrapper.php", 
                "./config/.env.mysql.staging"
            ]
        },
        "mysql-prod": {
            "command": "php",
            "args": [
                "~/.local/mcp-servers/mysql/server-wrapper.php",
                "./config/.env.mysql.prod"
            ]
        }
    }
}
```

#### Permissions par Environnement

**Développement (.env.mysql.dev) :**
```bash
# Permissions maximales pour développement
ALLOW_INSERT_OPERATION=true
ALLOW_UPDATE_OPERATION=true  
ALLOW_DELETE_OPERATION=true
MAX_RESULTS=5000
LOG_LEVEL=DEBUG
```

**Staging (.env.mysql.staging) :**
```bash
# Tests sans modifications destructives
ALLOW_INSERT_OPERATION=true
ALLOW_UPDATE_OPERATION=true
ALLOW_DELETE_OPERATION=false
MAX_RESULTS=1000
LOG_LEVEL=INFO
```

**Production (.env.mysql.prod) :**
```bash
# Lecture seule stricte
ALLOW_INSERT_OPERATION=false
ALLOW_UPDATE_OPERATION=false
ALLOW_DELETE_OPERATION=false
MAX_RESULTS=100
LOG_LEVEL=ERROR
```

### 🏢 Agence : Projets Clients Multiples

#### Organisation
```
agence-web/
├── clients/
│   ├── client-restaurant/
│   │   ├── .env.mysql
│   │   └── .cursor/mcp.json
│   ├── client-ecommerce/
│   │   ├── .env.mysql
│   │   └── .cursor/mcp.json
│   └── client-blog/
│       ├── .env.mysql  
│       └── .cursor/mcp.json
└── shared/
    └── mcp-servers/mysql/   # Serveur partagé
```

#### Script d'Automatisation
```bash
#!/bin/bash
# setup-client.sh

CLIENT_NAME=$1
DB_HOST=$2
DB_NAME=$3
DB_USER=$4
DB_PASS=$5

# Créer le répertoire client
mkdir -p "clients/${CLIENT_NAME}"

# Générer .env.mysql
cat > "clients/${CLIENT_NAME}/.env.mysql" << EOF
MYSQL_HOST=${DB_HOST}
MYSQL_PORT=3306
MYSQL_USER=${DB_USER}
MYSQL_PASS=${DB_PASS}
MYSQL_DB=${DB_NAME}

# Permissions par défaut agence
ALLOW_INSERT_OPERATION=false
ALLOW_UPDATE_OPERATION=false
ALLOW_DELETE_OPERATION=false
MAX_RESULTS=200
LOG_LEVEL=WARN
EOF

# Générer configuration MCP
mkdir -p "clients/${CLIENT_NAME}/.cursor"
cat > "clients/${CLIENT_NAME}/.cursor/mcp.json" << EOF
{
    "mcpServers": {
        "mysql-${CLIENT_NAME}": {
            "command": "php",
            "args": [
                "$(pwd)/shared/mcp-servers/mysql/server-wrapper.php",
                "$(pwd)/clients/${CLIENT_NAME}/.env.mysql"
            ]
        }
    }
}
EOF

echo "✅ Client ${CLIENT_NAME} configuré !"
```

**Usage :**
```bash
./setup-client.sh restaurant db1.client.com resto_db resto_user secret123
./setup-client.sh ecommerce db2.client.com shop_db shop_user secret456
```

### 🏭 Entreprise : Microservices

#### Architecture Microservices
```
microservices-platform/
├── services/
│   ├── user-service/
│   │   └── .cursor/mcp.json → mysql-users
│   ├── order-service/
│   │   └── .cursor/mcp.json → mysql-orders  
│   ├── inventory-service/
│   │   └── .cursor/mcp.json → mysql-inventory
│   └── analytics-service/
│       └── .cursor/mcp.json → mysql-analytics, mysql-users (readonly)
└── config/
    ├── databases.yaml       # Configuration centralisée
    └── generate-mcp.py      # Générateur de configs
```

#### Configuration Centralisée (databases.yaml)
```yaml
databases:
  users:
    host: users-db.internal
    port: 3306
    database: users_prod
    user: users_app
    permissions:
      insert: true
      update: true
      delete: false
    max_results: 1000
    
  orders:
    host: orders-db.internal  
    port: 3306
    database: orders_prod
    user: orders_app
    permissions:
      insert: true
      update: true
      delete: true
    max_results: 500
    
  inventory:
    host: inventory-db.internal
    port: 3306
    database: inventory_prod 
    user: inventory_app
    permissions:
      insert: true
      update: true
      delete: false
    max_results: 2000

services:
  user-service:
    databases: [users]
    
  order-service:
    databases: [orders, users:readonly]
    
  inventory-service:
    databases: [inventory, orders:readonly]
    
  analytics-service:
    databases: [users:readonly, orders:readonly, inventory:readonly]
```

#### Générateur Automatique (generate-mcp.py)
```python
#!/usr/bin/env python3
import yaml
import json
import os

def generate_mcp_configs():
    with open('config/databases.yaml', 'r') as f:
        config = yaml.safe_load(f)
    
    for service_name, service_config in config['services'].items():
        mcp_servers = {}
        
        for db_spec in service_config['databases']:
            # Parse db_spec (format: "db_name" ou "db_name:readonly")
            if ':' in db_spec:
                db_name, mode = db_spec.split(':')
                readonly = (mode == 'readonly')
            else:
                db_name = db_spec
                readonly = False
            
            db_config = config['databases'][db_name]
            permissions = db_config['permissions']
            
            # Configuration MCP
            server_name = f"mysql-{db_name}"
            if readonly:
                server_name += "-readonly"
            
            mcp_servers[server_name] = {
                "command": "php",
                "args": ["../shared/mysql/server.php"],
                "env": {
                    "MYSQL_HOST": db_config['host'],
                    "MYSQL_PORT": str(db_config['port']),
                    "MYSQL_USER": db_config['user'],
                    "MYSQL_PASS": os.getenv(f"{db_name.upper()}_PASSWORD"),
                    "MYSQL_DB": db_config['database'],
                    "ALLOW_INSERT_OPERATION": str(permissions['insert'] and not readonly).lower(),
                    "ALLOW_UPDATE_OPERATION": str(permissions['update'] and not readonly).lower(),
                    "ALLOW_DELETE_OPERATION": str(permissions['delete'] and not readonly).lower(),
                    "MAX_RESULTS": str(db_config['max_results'])
                }
            }
        
        # Écrire la configuration MCP
        service_dir = f"services/{service_name}/.cursor"
        os.makedirs(service_dir, exist_ok=True)
        
        mcp_config = {"mcpServers": mcp_servers}
        with open(f"{service_dir}/mcp.json", 'w') as f:
            json.dump(mcp_config, f, indent=2)
        
        print(f"✅ Configuration générée pour {service_name}")

if __name__ == "__main__":
    generate_mcp_configs()
```

---

## 🔐 Sécurité Multi-Projets

### Isolation des Accès

#### Utilisateurs MySQL par Projet
```sql
-- Client A : Lecture seule
CREATE USER 'client_a_readonly'@'%' IDENTIFIED BY 'secure_password_a';
GRANT SELECT ON client_a_db.* TO 'client_a_readonly'@'%';

-- Client B : CRUD limité
CREATE USER 'client_b_app'@'%' IDENTIFIED BY 'secure_password_b';
GRANT SELECT, INSERT, UPDATE ON client_b_db.* TO 'client_b_app'@'%';
GRANT DELETE ON client_b_db.temp_tables TO 'client_b_app'@'%';

-- Développement : Accès complet
CREATE USER 'dev_full'@'localhost' IDENTIFIED BY 'dev_password';
GRANT ALL PRIVILEGES ON dev_*.* TO 'dev_full'@'localhost';

FLUSH PRIVILEGES;
```

#### Variables d'Environnement Sécurisées
```bash
# .bashrc ou .zshrc
export CLIENT_A_DB_PASSWORD="$(security find-generic-password -s 'client-a-db' -w)"
export CLIENT_B_DB_PASSWORD="$(pass show databases/client-b)"
export DEV_DB_PASSWORD="dev_local_password"
```

### Monitoring Centralisé

#### Script de Monitoring
```bash
#!/bin/bash
# monitor-mcp-servers.sh

echo "🔍 Monitoring des serveurs MCP MySQL"
echo "=================================="

for project in clients/*/; do
    project_name=$(basename "$project")
    env_file="${project}/.env.mysql"
    
    if [ -f "$env_file" ]; then
        source "$env_file"
        echo "📊 Projet: $project_name"
        
        # Test de connexion
        mysql -h "$MYSQL_HOST" -P "$MYSQL_PORT" -u "$MYSQL_USER" -p"$MYSQL_PASS" "$MYSQL_DB" -e "SELECT 1" &>/dev/null
        if [ $? -eq 0 ]; then
            echo "   ✅ Connexion MySQL OK"
        else
            echo "   ❌ Connexion MySQL FAILED"
        fi
        
        # Test du serveur MCP
        timeout 5 php shared/mcp-servers/mysql/test_connection.php &>/dev/null
        if [ $? -eq 0 ]; then
            echo "   ✅ Serveur MCP OK"
        else
            echo "   ❌ Serveur MCP FAILED"
        fi
        
        echo ""
    fi
done
```

---

## 🚀 Automatisation et Scripts

### Script de Déploiement Global

```bash
#!/bin/bash
# deploy-mcp-servers.sh

set -e

echo "🚀 Déploiement des serveurs MCP MySQL"

# 1. Mise à jour du serveur central
echo "📦 Mise à jour du serveur central..."
cd ~/.local/mcp-servers/mysql
git pull origin main
composer install --no-dev --optimize-autoloader

# 2. Tests de chaque projet
echo "🧪 Tests des configurations projets..."
for project_config in projects/*/.env.mysql; do
    project_name=$(basename $(dirname "$project_config"))
    echo "   Testing $project_name..."
    
    # Source de la config
    source "$project_config"
    
    # Test de connexion
    if ! mysql -h "$MYSQL_HOST" -P "$MYSQL_PORT" -u "$MYSQL_USER" -p"$MYSQL_PASS" "$MYSQL_DB" -e "SELECT 1" &>/dev/null; then
        echo "   ❌ $project_name: Connexion échouée"
        exit 1
    fi
    
    echo "   ✅ $project_name: OK"
done

echo "🎉 Déploiement terminé !"
```

### Backup des Configurations

```bash
#!/bin/bash
# backup-configs.sh

BACKUP_DIR="backups/$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

# Backup des configs MCP
find . -name "mcp.json" -exec cp --parents {} "$BACKUP_DIR/" \;

# Backup des configs MySQL (sans mots de passe)
find . -name ".env.mysql" | while read env_file; do
    target="$BACKUP_DIR/$(dirname "$env_file")/$(basename "$env_file")"
    mkdir -p "$(dirname "$target")"
    
    # Copie sans les mots de passe
    grep -v "MYSQL_PASS" "$env_file" > "$target"
    echo "MYSQL_PASS=***REDACTED***" >> "$target"
done

echo "✅ Configurations sauvegardées dans $BACKUP_DIR"
```

---

## 📊 Monitoring et Métriques

### Dashboard Multi-Projets

```python
# dashboard.py - Monitoring centralisé
import json
import subprocess
import time
from datetime import datetime

def check_project_health(project_path):
    """Vérifie la santé d'un projet MCP MySQL"""
    try:
        env_file = f"{project_path}/.env.mysql"
        mcp_file = f"{project_path}/.cursor/mcp.json"
        
        # Charge la config
        with open(mcp_file, 'r') as f:
            mcp_config = json.load(f)
        
        results = {}
        for server_name, server_config in mcp_config['mcpServers'].items():
            if 'mysql' in server_name:
                # Test de connexion
                cmd = ['php', 'test_connection.php']
                result = subprocess.run(cmd, capture_output=True, timeout=10)
                
                results[server_name] = {
                    'status': 'healthy' if result.returncode == 0 else 'unhealthy',
                    'response_time': time.time() - start_time,
                    'last_check': datetime.now().isoformat()
                }
        
        return results
    except Exception as e:
        return {'error': str(e)}

def generate_dashboard():
    """Génère un dashboard HTML"""
    projects = ['project-a', 'project-b', 'project-c']
    
    html = """
    <html>
    <head><title>MCP MySQL Dashboard</title></head>
    <body>
    <h1>🗄️ MCP MySQL Multi-Projects Dashboard</h1>
    <table border="1">
    <tr><th>Project</th><th>Server</th><th>Status</th><th>Response Time</th><th>Last Check</th></tr>
    """
    
    for project in projects:
        health = check_project_health(f"projects/{project}")
        for server, data in health.items():
            status_icon = "✅" if data['status'] == 'healthy' else "❌"
            html += f"""
            <tr>
                <td>{project}</td>
                <td>{server}</td>
                <td>{status_icon} {data['status']}</td>
                <td>{data.get('response_time', 'N/A')}ms</td>
                <td>{data['last_check']}</td>
            </tr>
            """
    
    html += "</table></body></html>"
    
    with open('dashboard.html', 'w') as f:
        f.write(html)
    
    print("✅ Dashboard généré : dashboard.html")

if __name__ == "__main__":
    generate_dashboard()
```

---

## 🎯 Recommandations Finales

### Choix de l'Architecture

| Contexte | Architecture Recommandée | Méthode Config |
|----------|-------------------------|----------------|
| **Startup (< 5 projets)** | Serveur centralisé | Variables MCP |
| **Agence (5-20 clients)** | Serveur centralisé | Fichiers .env |
| **Entreprise (> 20 services)** | Serveur centralisé | Génération auto |
| **Projets isolés** | Serveur par projet | Variables MCP |

### Sécurité par Environnement

| Environnement | Permissions | Monitoring | Backup |
|---------------|-------------|------------|--------|
| **Production** | Lecture seule | ✅ Complet | ✅ Quotidien |
| **Staging** | Lecture + Écriture | ✅ Basique | ✅ Hebdomadaire |
| **Développement** | Complet | ⚠️ Minimal | ❌ Optionnel |

### Maintenance

1. **Mises à jour** : Centralisée avec tests automatisés
2. **Monitoring** : Dashboard centralisé + alertes
3. **Backup** : Configurations + données critiques
4. **Documentation** : À jour avec les changements

---

**Prêt pour la production ? Consultez le guide [Déploiement Production](./production-deployment.md) !** 🚀