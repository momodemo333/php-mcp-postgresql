# 🚀 Guide d'Installation

Ce guide vous accompagne dans l'installation et la configuration initiale du serveur MCP MySQL.

## 📋 Prérequis

### Système
- **PHP** >= 8.1
- **MySQL/MariaDB** >= 5.7
- **Composer** pour la gestion des dépendances

### Extensions PHP Requises
```bash
# Vérifiez que ces extensions sont installées
php -m | grep -E "(pdo|pdo_mysql|json|mbstring)"
```

Si des extensions manquent :
```bash
# Ubuntu/Debian
sudo apt install php-pdo php-mysql php-json php-mbstring

# CentOS/RHEL
sudo yum install php-pdo php-mysqlnd php-json php-mbstring

# macOS avec Homebrew
brew install php
```

## 📦 Installation

### Méthode 1 : Clone/Téléchargement Direct

1. **Récupérer le serveur** :
   ```bash
   # Cloner ou copier le répertoire mysql/
   cp -r /chemin/source/mysql /votre/projet/mcp-servers/mysql
   cd /votre/projet/mcp-servers/mysql
   ```

2. **Installer les dépendances** :
   ```bash
   composer install
   ```

3. **Rendre exécutable** :
   ```bash
   chmod +x server.php server-cli.php server-wrapper.php
   ```

### Méthode 2 : Installation Centralisée

Si vous voulez utiliser le même serveur pour plusieurs projets :

```bash
# Installation dans un répertoire central
mkdir -p ~/.local/mcp-servers
cp -r mysql ~/.local/mcp-servers/
cd ~/.local/mcp-servers/mysql
composer install
chmod +x *.php
```

## ⚙️ Configuration Initiale

### 1. Configuration de Base

Copiez le fichier d'exemple :
```bash
cp .env.example .env
```

Éditez `.env` avec vos paramètres :
```bash
# Connexion MySQL
MYSQL_HOST=127.0.0.1
MYSQL_PORT=3306
MYSQL_USER=your_user
MYSQL_PASS=your_password
MYSQL_DB=your_database

# Permissions (à ajuster selon vos besoins)
ALLOW_INSERT_OPERATION=false
ALLOW_UPDATE_OPERATION=false
ALLOW_DELETE_OPERATION=false
```

### 2. Test de Connexion

Testez votre configuration :
```bash
php test_connection.php
```

Vous devriez voir :
```
🔍 Test de connexion MySQL...
DSN: mysql:host=127.0.0.1;port=3306;dbname=your_database;charset=utf8mb4
✅ Connexion PDO réussie!
MySQL version: 8.0.x
Base de données courante: your_database
```

### 3. Test du Serveur MCP

Testez le serveur complet :
```bash
php test_mcp_server.php
```

## 🔧 Configuration Claude Code

### Configuration Basique

Ajoutez dans votre fichier de configuration MCP (`.cursor/mcp.json` ou équivalent) :

```json
{
    "mcpServers": {
        "mysql": {
            "command": "php",
            "args": ["/chemin/absolu/vers/mysql/server.php"]
        }
    }
}
```

### Configuration avec Variables d'Environnement

Pour surcharger la configuration par projet :

```json
{
    "mcpServers": {
        "mysql": {
            "command": "php",
            "args": ["/chemin/absolu/vers/mysql/server.php"],
            "env": {
                "MYSQL_HOST": "127.0.0.1",
                "MYSQL_PORT": "3306",
                "MYSQL_USER": "project_user",
                "MYSQL_PASS": "project_password",
                "MYSQL_DB": "project_database"
            }
        }
    }
}
```

## 📊 Création de Données de Test

Pour tester le serveur avec des données d'exemple :

```bash
php setup_test_data.php
```

Cela créera :
- **users** : 5 utilisateurs test
- **orders** : 9 commandes liées aux utilisateurs  
- **categories** : 6 catégories avec hiérarchie

## ✅ Vérification de l'Installation

### 1. Test des Outils MCP

Une fois Claude Code configuré, testez :

```
Liste les bases de données disponibles
→ Devrait utiliser mysql_list_databases

Montre-moi la structure de la table users
→ Devrait utiliser mysql_describe_table
```

### 2. Vérification des Logs

Consultez les logs d'erreur :
```bash
# Logs du serveur (STDERR)
tail -f /var/log/php_errors.log

# Ou lancez le serveur en mode debug
LOG_LEVEL=DEBUG php server.php
```

## 🔐 Configuration Sécurisée

### Permissions Minimales

Pour un environnement de production, commencez par :

```bash
# .env pour production
ALLOW_INSERT_OPERATION=false
ALLOW_UPDATE_OPERATION=false  
ALLOW_DELETE_OPERATION=false
ALLOW_TRUNCATE_OPERATION=false
BLOCK_DANGEROUS_KEYWORDS=true
MAX_RESULTS=100
QUERY_TIMEOUT=10
```

### Utilisateur MySQL Dédié

Créez un utilisateur MySQL spécifique :

```sql
-- Utilisateur en lecture seule
CREATE USER 'mcp_readonly'@'localhost' IDENTIFIED BY 'secure_password';
GRANT SELECT ON your_database.* TO 'mcp_readonly'@'localhost';

-- Utilisateur avec permissions limitées
CREATE USER 'mcp_limited'@'localhost' IDENTIFIED BY 'secure_password';
GRANT SELECT, INSERT, UPDATE ON your_database.* TO 'mcp_limited'@'localhost';

FLUSH PRIVILEGES;
```

## 🚨 Résolution de Problèmes Courants

### Erreur : "No such file or directory"

```bash
# Vérifiez que vous utilisez 127.0.0.1 au lieu de localhost
MYSQL_HOST=127.0.0.1
```

### Erreur : "Access denied"

```bash
# Vérifiez les identifiants MySQL
mysql -h 127.0.0.1 -P 3306 -u your_user -p your_database
```

### Erreur : "Extension not found"

```bash
# Installez les extensions manquantes
sudo apt install php-pdo php-mysql
```

### Serveur MCP ne démarre pas

```bash
# Test avec logs détaillés
LOG_LEVEL=DEBUG php server.php
```

## 📁 Structure Post-Installation

Après installation réussie :

```
mysql/
├── server.php              ✅ Serveur principal
├── server-cli.php          ✅ Serveur avec arguments CLI
├── server-wrapper.php      ✅ Wrapper pour fichiers .env
├── .env                    ✅ Configuration locale
├── test_connection.php     ✅ Test de connexion
├── test_mcp_server.php     ✅ Test du serveur
├── setup_test_data.php     ✅ Données de test
├── src/                    ✅ Code source
├── vendor/                 ✅ Dépendances
├── composer.json           ✅ Configuration Composer
└── docs/                   ✅ Documentation
```

## 🎯 Prochaines Étapes

Une fois l'installation terminée :

1. **Configuration avancée** : Consultez [Variables d'Environnement](./environment-variables.md)
2. **Sécurité** : Lisez le guide [Sécurité](./security.md)  
3. **Usage** : Voir [Exemples d'Usage](./examples.md)
4. **Multi-projets** : Guide [Configuration Multi-Projets](./multi-project-setup.md)

## 💬 Support

Si vous rencontrez des problèmes :

1. Consultez [Troubleshooting](./troubleshooting.md)
2. Vérifiez les logs avec `LOG_LEVEL=DEBUG`
3. Testez la connexion MySQL directement
4. Ouvrez une issue avec les détails de votre configuration

---

**Installation terminée ? Passez au [Premier Démarrage](./quick-start.md) !** 🎉