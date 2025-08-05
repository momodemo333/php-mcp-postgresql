# 🗄️ MySQL MCP Server

[![PHP](https://img.shields.io/badge/PHP->=8.1-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![MCP](https://img.shields.io/badge/MCP-3.3-purple.svg)](https://github.com/php-mcp/server)

Serveur MCP (Model Context Protocol) pour MySQL, permettant à Claude Code d'interagir avec vos bases de données MySQL de manière sécurisée et configurable.

## 🚀 Installation Rapide

### Via Composer (Recommandé)

```bash
composer require morgan/mysql-mcp
```

### Configuration Claude Code

Ajoutez à votre `.cursor/mcp.json` :

```json
{
    "mcpServers": {
        "mysql": {
            "command": "php",
            "args": ["vendor/morgan/mysql-mcp/bin/server.php"],
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

### Test Rapide

```bash
# Test de connexion
php vendor/morgan/mysql-mcp/tests/test_connection.php

# Test du serveur MCP
php vendor/morgan/mysql-mcp/tests/test_mcp_server.php
```

**🎉 C'est tout ! Votre serveur MySQL MCP est prêt !**

---

## ✨ Fonctionnalités

### 🛠️ Outils MCP Disponibles

- **`mysql_list_databases`** - Liste toutes les bases de données
- **`mysql_list_tables`** - Liste les tables d'une base de données
- **`mysql_describe_table`** - Décrit la structure d'une table
- **`mysql_server_status`** - Statut et informations du serveur MySQL
- **`mysql_select`** - Exécution de requêtes SELECT sécurisées
- **`mysql_insert`** - Insertion de données avec validation
- **`mysql_update`** - Mise à jour avec conditions obligatoires
- **`mysql_delete`** - Suppression avec conditions obligatoires
- **`mysql_execute_query`** - Exécution de requêtes SQL personnalisées

### 📊 Ressources MCP

- **`mysql://connection/status`** - Statut de la connexion en temps réel
- **`mysql://server/capabilities`** - Capacités et limitations du serveur

### 🔒 Fonctionnalités de Sécurité

- ✅ Validation des requêtes SQL
- ✅ Protection contre l'injection SQL
- ✅ Permissions granulaires par opération (INSERT, UPDATE, DELETE)
- ✅ Limitation du nombre de résultats
- ✅ Timeout des requêtes configurables
- ✅ Filtrage des schémas autorisés
- ✅ Blocage des mots-clés dangereux
- ✅ Logging complet des opérations

### ⚡ Performance

- 🔄 Pool de connexions MySQL
- 📝 Logging intelligent avec niveaux configurables
- 🚀 Requêtes préparées pour la sécurité et performance
- 💾 Cache des connexions et métadonnées

## 📋 Prérequis

- **PHP** >= 8.1
- **Extensions**: `pdo`, `pdo_mysql`
- **MySQL/MariaDB** >= 5.7
- **Composer** pour la gestion des dépendances

## 🚀 Installation

1. Cloner ou copier le répertoire `mysql/`
2. Installer les dépendances :
   ```bash
   cd mysql/
   composer install
   ```

3. Configurer les variables d'environnement (voir section Configuration)

## ⚙️ Configuration

### Variables d'Environnement

Copiez `.env.example` vers `.env` et ajustez les valeurs :

```bash
# === CONNEXION MYSQL ===
MYSQL_HOST=127.0.0.1
MYSQL_PORT=3306
MYSQL_USER=your_user
MYSQL_PASS=your_password
MYSQL_DB=your_database              # Optionnel, laissez vide pour le mode multi-DB

# === PERMISSIONS CRUD ===
ALLOW_INSERT_OPERATION=false
ALLOW_UPDATE_OPERATION=false
ALLOW_DELETE_OPERATION=false
ALLOW_TRUNCATE_OPERATION=false      # Extra protection

# === SÉCURITÉ ===
QUERY_TIMEOUT=30                    # Timeout en secondes
MAX_RESULTS=1000                    # Limite de résultats par requête
ALLOWED_SCHEMAS=                    # Schémas autorisés (vide = tous)
BLOCK_DANGEROUS_KEYWORDS=true      # Bloquer DROP, TRUNCATE, etc.
ENABLE_QUERY_LOGGING=true          # Log des requêtes

# === PERFORMANCE ===
CONNECTION_POOL_SIZE=5              # Taille du pool de connexions
CACHE_TTL=300                       # TTL du cache (secondes)
ENABLE_QUERY_CACHE=true            # Cache des requêtes

# === FONCTIONNALITÉS ===
ENABLE_TRANSACTIONS=true            # Support des transactions
ENABLE_PREPARED_STATEMENTS=true    # Requêtes préparées
ENABLE_SCHEMA_INTROSPECTION=true   # Inspection des structures
ENABLE_EXPORT_TOOLS=true           # Outils d'export

# === LOGGING ===
LOG_LEVEL=INFO                      # DEBUG, INFO, WARN, ERROR
LOG_FILE=                           # Fichier de log (vide = STDERR)
```

## 🎯 Utilisation

### 1. Démarrage du Serveur

```bash
# Test de la configuration
php test_mcp_server.php

# Démarrage du serveur MCP (stdio)
./server.php
```

### 2. Configuration Claude Code

Ajoutez dans votre configuration MCP :

```json
{
    "mcpServers": {
        "mysql-server": {
            "command": "php",
            "args": ["/chemin/absolu/vers/mysql/server.php"]
        }
    }
}
```

### 3. Utilisation dans Claude Code

```
Peux-tu lister les bases de données disponibles ?
→ Utilise mysql_list_databases

Montre-moi la structure de la table users
→ Utilise mysql_describe_table avec table="users"

Récupère tous les utilisateurs de plus de 30 ans
→ Utilise mysql_select avec query="SELECT * FROM users WHERE age > 30"
```

## 📊 Exemples d'Utilisation

### Requêtes de Base

```sql
-- Lister les tables
mysql_list_tables

-- Décrire une table
mysql_describe_table(table="users")

-- Sélectionner des données
mysql_select(query="SELECT * FROM users WHERE age > 30")

-- Insérer des données (si ALLOW_INSERT_OPERATION=true)
mysql_insert(table="users", data={"name": "John", "email": "john@example.com", "age": 35})

-- Mettre à jour (si ALLOW_UPDATE_OPERATION=true)
mysql_update(table="users", data={"age": 36}, conditions={"id": 1})
```

### Requêtes Avancées

```sql
-- Jointures et agrégations
mysql_select(query="
    SELECT u.name, COUNT(o.id) as order_count 
    FROM users u 
    LEFT JOIN orders o ON u.id = o.user_id 
    GROUP BY u.id
")

-- Requêtes avec paramètres (sécurisé)
mysql_select(
    query="SELECT * FROM orders WHERE user_id = ? AND status = ?",
    params=[1, "completed"]
)
```

## 🧪 Tests et Données d'Exemple

### Création de Données de Test

```bash
# Crée des tables et données d'exemple
php setup_test_data.php
```

Cela crée :
- **users** (5 utilisateurs)
- **orders** (9 commandes) 
- **categories** (6 catégories avec hiérarchie)

### Tables Créées

- **`users`** : Utilisateurs avec nom, email, âge
- **`orders`** : Commandes liées aux utilisateurs
- **`categories`** : Catégories hiérarchiques

## 🔒 Sécurité

### Protections Intégrées

1. **Validation des Requêtes** : Analyse syntaxique et sémantique
2. **Injection SQL** : Protection via requêtes préparées et validation
3. **Permissions** : Contrôle granulaire des opérations CRUD
4. **Limits** : Timeout et limitation du nombre de résultats
5. **Whitelist** : Restriction aux schémas autorisés
6. **Mots-clés Dangereux** : Blocage de DROP, TRUNCATE, etc.
7. **Audit** : Logging complet des opérations

### Configuration de Production

```bash
# Production sécurisée
ALLOW_INSERT_OPERATION=false
ALLOW_UPDATE_OPERATION=false
ALLOW_DELETE_OPERATION=false
BLOCK_DANGEROUS_KEYWORDS=true
MAX_RESULTS=100
QUERY_TIMEOUT=10
ALLOWED_SCHEMAS=your_app_db
ENABLE_QUERY_LOGGING=true
LOG_LEVEL=WARN
```

## 🚀 Intégration dans vos Projets

### Structure Recommandée

```
your-project/
├── mcp-servers/
│   └── mysql/              # Ce serveur
├── .cursor/
│   └── mcp.json           # Configuration MCP
└── your-app-files...
```

### Configuration par Projet

Chaque projet peut avoir sa propre configuration MySQL :

```bash
# Projet A
MYSQL_DB=project_a_db
MYSQL_USER=project_a_user

# Projet B  
MYSQL_DB=project_b_db
MYSQL_USER=project_b_user
```

## 📈 Monitoring et Logs

### Niveaux de Log

- **DEBUG** : Toutes les opérations détaillées
- **INFO** : Opérations importantes et statistiques
- **WARN** : Avertissements de sécurité et performance
- **ERROR** : Erreurs et échecs de connexion

### Métriques Disponibles

- Nombre de connexions actives
- Temps d'exécution des requêtes  
- Statistiques d'utilisation par outil
- Erreurs et tentatives de sécurité

## 🛠️ Développement

### Architecture

```
src/
├── Elements/
│   ├── DatabaseTools.php     # Outils de gestion BDD
│   └── QueryTools.php        # Outils d'exécution requêtes
├── Services/
│   ├── ConnectionService.php # Pool de connexions
│   └── SecurityService.php   # Validation et sécurité
├── Exceptions/               # Exceptions spécifiques
└── MySqlServer.php          # Configuration principale
```

### Tests

```bash
# Test de configuration
php test_mcp_server.php

# Test de connexion  
php test_connection.php

# Création de données de test
php setup_test_data.php
```

## 🤝 Contribution

1. Fork le projet
2. Créer une branche pour votre fonctionnalité
3. Commiter vos changements
4. Créer une Pull Request

## 📄 Licence

Ce projet est sous licence MIT. Voir le fichier LICENSE pour plus de détails.

## 🆘 Support

Pour toute question ou problème :

1. Vérifiez la configuration dans `.env`
2. Testez la connexion avec `php test_connection.php`
3. Consultez les logs pour les erreurs détaillées
4. Ouvrez une issue avec les détails de votre configuration

---

**🎉 Serveur MCP MySQL prêt à l'emploi !** Intégrez facilement MySQL dans vos workflows Claude Code.