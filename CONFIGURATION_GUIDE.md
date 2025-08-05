# 🔧 Guide de Configuration Multi-Projets

Guide complet pour configurer le serveur MCP MySQL dans différents projets avec des bases de données spécifiques.

## 🎯 Méthodes de Configuration

### 1. ⭐ **Méthode Recommandée : Variables d'Environnement MCP**

La méthode la plus simple et native au protocole MCP.

#### Configuration Claude Code

```json
{
    "mcpServers": {
        "mysql-project-a": {
            "command": "php",
            "args": ["/chemin/vers/customMcp/mysql/server.php"],
            "env": {
                "MYSQL_HOST": "127.0.0.1",
                "MYSQL_PORT": "3306",
                "MYSQL_USER": "project_a_user",
                "MYSQL_PASS": "project_a_password",
                "MYSQL_DB": "project_a_database",
                "ALLOW_INSERT_OPERATION": "true",
                "ALLOW_UPDATE_OPERATION": "true",
                "ALLOW_DELETE_OPERATION": "false",
                "MAX_RESULTS": "500",
                "LOG_LEVEL": "INFO"
            }
        },
        "mysql-project-b": {
            "command": "php",
            "args": ["/chemin/vers/customMcp/mysql/server.php"],
            "env": {
                "MYSQL_HOST": "192.168.1.100",
                "MYSQL_PORT": "3306",
                "MYSQL_USER": "project_b_user",
                "MYSQL_PASS": "project_b_password",
                "MYSQL_DB": "project_b_database",
                "ALLOW_INSERT_OPERATION": "false",
                "ALLOW_UPDATE_OPERATION": "false",
                "ALLOW_DELETE_OPERATION": "false",
                "MAX_RESULTS": "100",
                "LOG_LEVEL": "WARN"
            }
        }
    }
}
```

#### ✅ Avantages
- ✅ Natif au protocole MCP
- ✅ Configuration centralisée
- ✅ Simple à mettre en place
- ✅ Pas de fichiers supplémentaires

#### ❌ Inconvénients  
- ❌ Mots de passe visibles dans la config
- ❌ Config peut devenir longue

---

### 2. 🔒 **Méthode Sécurisée : Fichiers .env par Projet**

Idéale pour séparer la configuration et sécuriser les mots de passe.

#### Structure Projet
```
project-a/
├── .env.mysql              # Configuration MySQL spécifique
├── .cursor/
│   └── mcp.json            # Configuration MCP
└── src/...

project-b/
├── .env.mysql
├── .cursor/
│   └── mcp.json
└── src/...
```

#### Fichier .env.mysql (project-a)
```bash
# Configuration MySQL pour Project A
MYSQL_HOST=127.0.0.1
MYSQL_PORT=3306
MYSQL_USER=project_a_user
MYSQL_PASS=secure_password_a
MYSQL_DB=project_a_database

# Permissions spécifiques au projet
ALLOW_INSERT_OPERATION=true
ALLOW_UPDATE_OPERATION=true
ALLOW_DELETE_OPERATION=false

# Configuration personnalisée
QUERY_TIMEOUT=30
MAX_RESULTS=1000
ALLOWED_SCHEMAS=project_a_database,project_a_logs
LOG_LEVEL=INFO
```

#### Configuration MCP (project-a/.cursor/mcp.json)
```json
{
    "mcpServers": {
        "mysql": {
            "command": "php",
            "args": [
                "/chemin/vers/customMcp/mysql/server-wrapper.php",
                "/chemin/absolu/vers/project-a/.env.mysql"
            ]
        }
    }
}
```

#### ✅ Avantages
- ✅ Sécurité : mots de passe hors config MCP
- ✅ Réutilisable : fichiers .env versionnables
- ✅ Flexible : configuration complète par projet
- ✅ Isolation : chaque projet a sa config

#### ❌ Inconvénients
- ❌ Plus complexe à configurer
- ❌ Nécessite le wrapper

---

### 3. 🛠️ **Méthode CLI : Arguments Directs**

Pratique pour les tests et développement.

#### Configuration MCP
```json
{
    "mcpServers": {
        "mysql-dev": {
            "command": "php",
            "args": [
                "/chemin/vers/customMcp/mysql/server-cli.php",
                "--host=127.0.0.1",
                "--port=3306",
                "--user=dev_user",
                "--pass=dev_password",
                "--db=dev_database",
                "--allow-insert=true",
                "--allow-update=true",
                "--allow-delete=false",
                "--log-level=DEBUG"
            ]
        }
    }
}
```

#### ✅ Avantages
- ✅ Configuration explicite et visible
- ✅ Facile à déboguer
- ✅ Flexible pour les tests
- ✅ Pas de fichiers supplémentaires

#### ❌ Inconvénients
- ❌ Configuration très longue
- ❌ Mots de passe visibles
- ❌ Moins pratique pour la production

---

## 🏗️ Exemples Pratiques par Cas d'Usage

### 📊 **Cas 1 : Startup avec Une Seule Base**

Simple et direct, tout en local.

```json
{
    "mcpServers": {
        "mysql": {
            "command": "php",
            "args": ["/chemin/vers/customMcp/mysql/server.php"],
            "env": {
                "MYSQL_HOST": "127.0.0.1",
                "MYSQL_PORT": "3306",
                "MYSQL_USER": "myapp",
                "MYSQL_PASS": "myapp_password",
                "MYSQL_DB": "myapp",
                "ALLOW_INSERT_OPERATION": "true",
                "ALLOW_UPDATE_OPERATION": "true",
                "ALLOW_DELETE_OPERATION": "true"
            }
        }
    }
}
```

### 🏢 **Cas 2 : Agence avec Multiples Clients**

Chaque client a sa propre base et ses permissions.

```json
{
    "mcpServers": {
        "mysql-client-a": {
            "command": "php",
            "args": ["/chemin/vers/customMcp/mysql/server-wrapper.php", "/projets/client-a/.env.mysql"]
        },
        "mysql-client-b": {
            "command": "php", 
            "args": ["/chemin/vers/customMcp/mysql/server-wrapper.php", "/projets/client-b/.env.mysql"]
        },
        "mysql-client-c": {
            "command": "php",
            "args": ["/chemin/vers/customMcp/mysql/server-wrapper.php", "/projets/client-c/.env.mysql"]
        }
    }
}
```

### 🔧 **Cas 3 : Développement Multi-Environnements**

Dev, staging, production avec permissions différentes.

```json
{
    "mcpServers": {
        "mysql-dev": {
            "command": "php",
            "args": ["/chemin/vers/customMcp/mysql/server.php"],
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
        "mysql-staging": {
            "command": "php",
            "args": ["/chemin/vers/customMcp/mysql/server.php"],
            "env": {
                "MYSQL_HOST": "staging.example.com",
                "MYSQL_USER": "staging_user",
                "MYSQL_PASS": "staging_pass",
                "MYSQL_DB": "myapp_staging",
                "ALLOW_INSERT_OPERATION": "false",
                "ALLOW_UPDATE_OPERATION": "false",
                "ALLOW_DELETE_OPERATION": "false",
                "LOG_LEVEL": "INFO"
            }
        },
        "mysql-prod": {
            "command": "php",
            "args": ["/chemin/vers/customMcp/mysql/server.php"],
            "env": {
                "MYSQL_HOST": "prod.example.com",
                "MYSQL_USER": "readonly_user",
                "MYSQL_PASS": "readonly_pass",
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

## 🔐 Sécurisation des Mots de Passe

### Méthode 1 : Variables d'Environnement Système

```bash
# Dans votre .bashrc ou .zshrc
export MYSQL_PASS_PROJECT_A="super_secret_password"
export MYSQL_PASS_PROJECT_B="another_secret_password"
```

Configuration MCP :
```json
{
    "env": {
        "MYSQL_PASS": "${MYSQL_PASS_PROJECT_A}"
    }
}
```

### Méthode 2 : Fichiers .env avec Gitignore

```bash
# Dans .gitignore
.env.mysql
.env.local
*.secret
```

### Méthode 3 : Chiffrement avec GPG

```bash
# Chiffrer le fichier .env
gpg -c .env.mysql

# Créer un script de déchiffrement
echo "gpg -d .env.mysql.gpg > .env.mysql" > decrypt.sh
```

---

## 🚀 Scripts d'Installation Rapide

### Script de Configuration Automatique

```bash
#!/bin/bash
# setup-mysql-mcp.sh

echo "🔧 Configuration du serveur MCP MySQL pour votre projet"

read -p "Nom du projet: " PROJECT_NAME
read -p "Host MySQL: " MYSQL_HOST
read -p "Port MySQL [3306]: " MYSQL_PORT
MYSQL_PORT=${MYSQL_PORT:-3306}
read -p "Utilisateur MySQL: " MYSQL_USER
read -s -p "Mot de passe MySQL: " MYSQL_PASS
echo
read -p "Base de données: " MYSQL_DB

# Créer le fichier .env.mysql  
cat > .env.mysql << EOF
MYSQL_HOST=${MYSQL_HOST}
MYSQL_PORT=${MYSQL_PORT}
MYSQL_USER=${MYSQL_USER}
MYSQL_PASS=${MYSQL_PASS}
MYSQL_DB=${MYSQL_DB}

ALLOW_INSERT_OPERATION=true
ALLOW_UPDATE_OPERATION=true  
ALLOW_DELETE_OPERATION=false

MAX_RESULTS=1000
LOG_LEVEL=INFO
EOF

# Créer la configuration MCP
mkdir -p .cursor
cat > .cursor/mcp.json << EOF
{
    "mcpServers": {
        "mysql-${PROJECT_NAME}": {
            "command": "php",
            "args": [
                "/chemin/vers/customMcp/mysql/server-wrapper.php",
                "$(pwd)/.env.mysql"
            ]
        }
    }
}
EOF

echo "✅ Configuration créée !"
echo "📁 Fichiers créés :"
echo "   - .env.mysql"
echo "   - .cursor/mcp.json"
echo ""
echo "⚠️  N'oubliez pas d'ajouter .env.mysql à votre .gitignore"
```

---

## 🧪 Tests de Configuration

### Vérification Rapide

```bash
# Test de connexion
php /chemin/vers/customMcp/mysql/test_connection.php

# Test du serveur MCP
php /chemin/vers/customMcp/mysql/test_mcp_server.php

# Test avec configuration spécifique
MYSQL_HOST=127.0.0.1 MYSQL_PORT=3306 MYSQL_USER=test MYSQL_PASS=test MYSQL_DB=test php test_mcp_server.php
```

### Script de Test Automatisé

```bash
#!/bin/bash
# test-mysql-config.sh

echo "🧪 Test de configuration MySQL MCP"

if [ ! -f ".env.mysql" ]; then
    echo "❌ Fichier .env.mysql introuvable"
    exit 1
fi

# Charge les variables
source .env.mysql

# Test de connexion directe
mysql -h $MYSQL_HOST -P $MYSQL_PORT -u $MYSQL_USER -p$MYSQL_PASS $MYSQL_DB -e "SELECT 1 as test" 2>/dev/null

if [ $? -eq 0 ]; then
    echo "✅ Connexion MySQL validée"
else
    echo "❌ Échec de connexion MySQL"
    exit 1
fi

# Test du serveur MCP
php /chemin/vers/customMcp/mysql/test_mcp_server.php

echo "🎉 Configuration testée avec succès !"
```

---

## 📋 Tableau Récapitulatif

| Critère | Variables MCP | Fichiers .env | Arguments CLI |
|---------|---------------|---------------|---------------|
| **Simplicité** | ⭐⭐⭐ | ⭐⭐ | ⭐ |
| **Sécurité** | ⭐⭐ | ⭐⭐⭐ | ⭐ |
| **Flexibilité** | ⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐ |
| **Maintenance** | ⭐⭐⭐ | ⭐⭐ | ⭐ |
| **Multi-projets** | ⭐⭐ | ⭐⭐⭐ | ⭐⭐ |

## 🎯 Recommandations

- **Projets simples** → Variables d'environnement MCP
- **Projets multiples** → Fichiers .env avec wrapper  
- **Développement/Tests** → Arguments CLI
- **Production** → Fichiers .env + chiffrement

---

**🚀 Votre serveur MCP MySQL est maintenant configurable pour tous vos projets !**