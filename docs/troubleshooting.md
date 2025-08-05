# 🔧 Guide de Résolution de Problèmes

Solutions aux problèmes les plus courants avec le serveur MCP MySQL.

## 🚨 Problèmes de Connexion

### ❌ Erreur : "No such file or directory"

**Symptômes :**
```
SQLSTATE[HY000] [2002] No such file or directory
```

**Causes possibles :**
1. Utilisation de `localhost` au lieu d'une adresse IP
2. Socket Unix introuvable
3. MySQL non démarré

**Solutions :**

#### 1. Forcer la connexion TCP
```bash
# Dans .env, remplacez :
MYSQL_HOST=localhost

# Par :
MYSQL_HOST=127.0.0.1
```

#### 2. Vérifier le service MySQL
```bash
# Vérifier si MySQL fonctionne
sudo systemctl status mysql
# ou
sudo systemctl status mariadb

# Démarrer si nécessaire
sudo systemctl start mysql
```

#### 3. Test de connexion directe
```bash
# Tester la connexion avec le client MySQL
mysql -h 127.0.0.1 -P 3306 -u your_user -p your_database
```

### ❌ Erreur : "Access denied for user"

**Symptômes :**
```
SQLSTATE[28000] [1045] Access denied for user 'user'@'host' (using password: YES)
```

**Solutions :**

#### 1. Vérifier les identifiants
```bash
# Test direct
mysql -h 127.0.0.1 -P 3306 -u your_user -p
```

#### 2. Vérifier les permissions MySQL
```sql
-- Connectez-vous en tant qu'admin MySQL
SELECT User, Host FROM mysql.user WHERE User = 'your_user';
SHOW GRANTS FOR 'your_user'@'localhost';
```

#### 3. Créer/corriger l'utilisateur
```sql
-- Créer un utilisateur
CREATE USER 'mcp_user'@'%' IDENTIFIED BY 'secure_password';
GRANT SELECT, INSERT, UPDATE ON your_database.* TO 'mcp_user'@'%';
FLUSH PRIVILEGES;
```

### ❌ Erreur : "Connection refused"

**Symptômes :**
```
SQLSTATE[HY000] [2002] Connection refused
```

**Solutions :**

#### 1. Vérifier le port MySQL
```bash
# Voir sur quel port MySQL écoute
sudo netstat -tlnp | grep mysql
# ou
sudo ss -tlnp | grep mysql
```

#### 2. Vérifier la configuration MySQL
```bash
# Vérifier la configuration MySQL
sudo cat /etc/mysql/mysql.conf.d/mysqld.cnf | grep bind-address
# Doit être 0.0.0.0 ou 127.0.0.1, pas 127.0.0.1
```

#### 3. Redémarrer MySQL
```bash
sudo systemctl restart mysql
```

---

## 🔧 Problèmes de Configuration

### ❌ Serveur MCP ne démarre pas

**Symptômes :**
- Pas de réponse du serveur
- Claude Code ne voit pas le serveur
- Erreurs dans les logs

**Diagnostic :**

#### 1. Test en mode debug
```bash
LOG_LEVEL=DEBUG php server.php
```

#### 2. Vérifier la configuration
```bash
php test_mcp_server.php
```

#### 3. Vérifier les permissions des fichiers
```bash
chmod +x server.php server-cli.php server-wrapper.php
```

### ❌ Variables d'environnement non chargées

**Symptômes :**
```
Using default values for MySQL connection
```

**Solutions :**

#### 1. Vérifier le fichier .env
```bash
# Le fichier existe-t-il ?
ls -la .env

# Format correct ?
cat .env | grep -v '^#' | grep '='
```

#### 2. Test de chargement des variables
```bash
# Créer un script de test
cat > test_env.php << 'EOF'
<?php
require_once __DIR__ . '/src/MySqlServer.php';
$server = new \MySqlMcp\MySqlServer();
print_r($server->getConfig());
EOF

php test_env.php
```

#### 3. Forcer les variables
```bash
# Test avec variables explicites
MYSQL_HOST=127.0.0.1 MYSQL_USER=test php test_mcp_server.php
```

### ❌ Erreur : "Class not found"

**Symptômes :**
```
Fatal error: Class 'MySqlMcp\MySqlServer' not found
```

**Solutions :**

#### 1. Réinstaller les dépendances
```bash
rm -rf vendor/
composer install
```

#### 2. Vérifier l'autoloader
```bash
# Régénérer l'autoloader
composer dump-autoload
```

#### 3. Vérifier les chemins dans composer.json
```json
{
    "autoload": {
        "psr-4": {
            "MySqlMcp\\": "src/"
        }
    }
}
```

---

## 🔒 Problèmes de Permissions

### ❌ Erreur : "Operation not allowed"

**Symptômes :**
```
SecurityException: Opération INSERT non autorisée par la configuration
```

**Solutions :**

#### 1. Vérifier les permissions dans .env
```bash
# Activer les opérations nécessaires
ALLOW_INSERT_OPERATION=true
ALLOW_UPDATE_OPERATION=true
ALLOW_DELETE_OPERATION=true
```

#### 2. Vérifier les permissions MySQL
```sql
SHOW GRANTS FOR CURRENT_USER();
```

#### 3. Test avec permissions maximales
```bash
# Temporairement pour tester
ALLOW_INSERT_OPERATION=true ALLOW_UPDATE_OPERATION=true ALLOW_DELETE_OPERATION=true php test_mcp_server.php
```

### ❌ Erreur : "Schema not allowed"

**Symptômes :**
```
SecurityException: Schéma non autorisé: other_database
```

**Solutions :**

#### 1. Configurer les schémas autorisés
```bash
# Dans .env
ALLOWED_SCHEMAS=database1,database2,database3
# ou laisser vide pour autoriser tous
ALLOWED_SCHEMAS=
```

#### 2. Utiliser le schéma configuré
```bash
# Vérifier le schéma configuré
grep MYSQL_DB .env
```

---

## 📊 Problèmes de Performance

### ⚠️ Requêtes lentes

**Symptômes :**
- `execution_time_ms` élevé (>1000ms)
- Timeouts fréquents

**Solutions :**

#### 1. Analyser les requêtes lentes
```bash
# Activer le log des requêtes lentes MySQL
sudo mysql -e "SET GLOBAL slow_query_log = 'ON';"
sudo mysql -e "SET GLOBAL long_query_time = 1;"
```

#### 2. Utiliser EXPLAIN
```sql
EXPLAIN SELECT * FROM users WHERE email = 'test@example.com';
```

#### 3. Ajouter des index
```sql
-- Créer des index sur les colonnes filtrées
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_orders_user_id ON orders(user_id);
```

#### 4. Limiter les résultats
```bash
# Dans .env
MAX_RESULTS=100
QUERY_TIMEOUT=10
```

### ⚠️ Trop de connexions

**Symptômes :**
```
MySqlMcpException: Pool de connexions saturé. Maximum: 5
```

**Solutions :**

#### 1. Augmenter la taille du pool
```bash
# Dans .env
CONNECTION_POOL_SIZE=10
```

#### 2. Vérifier les connexions MySQL
```sql
SHOW STATUS LIKE 'Threads_connected';
SHOW PROCESSLIST;
```

#### 3. Optimiser l'utilisation
- Utiliser des requêtes plus efficaces
- Éviter les requêtes dans des boucles
- Fermer les connexions inutilisées

---

## 🐛 Problèmes Claude Code

### ❌ Claude Code ne voit pas le serveur

**Symptômes :**
- Serveur non listé dans Claude Code
- Outils MCP non disponibles

**Solutions :**

#### 1. Vérifier la configuration MCP
```json
{
    "mcpServers": {
        "mysql": {
            "command": "php",
            "args": ["/chemin/ABSOLU/vers/server.php"]
        }
    }
}
```

#### 2. Vérifier les chemins
```bash
# Le chemin doit être absolu
which php
# /usr/bin/php

# Chemin vers le serveur
realpath server.php
# /home/user/project/mysql/server.php
```

#### 3. Redémarrer Claude Code
Après modification de la configuration MCP, toujours redémarrer Claude Code.

#### 4. Vérifier les logs de Claude Code
Consulter les logs d'erreur de Claude Code pour des détails.

### ❌ Erreurs "Tool not found"

**Symptômes :**
```
Tool 'mysql_list_databases' not found
```

**Solutions :**

#### 1. Vérifier l'enregistrement des outils
```bash
# Test du serveur
php test_mcp_server.php
```

#### 2. Vérifier les logs
```bash
LOG_LEVEL=DEBUG php server.php 2>&1 | grep -i tool
```

#### 3. Re-découvrir les outils
Redémarrer le serveur MCP via Claude Code.

---

## 🔍 Outils de Diagnostic

### Script de Diagnostic Complet

```bash
#!/bin/bash
# diagnostic.sh

echo "🔍 Diagnostic du serveur MCP MySQL"
echo "================================="

# 1. Vérification de l'environnement
echo "📋 Environnement :"
php --version | head -1
mysql --version 2>/dev/null || echo "❌ Client MySQL non trouvé"

# 2. Vérification des extensions PHP
echo -e "\n🔧 Extensions PHP :"
php -m | grep -E "(pdo|pdo_mysql|json|mbstring)" || echo "❌ Extensions manquantes"

# 3. Vérification des fichiers
echo -e "\n📁 Fichiers :"
[ -f "server.php" ] && echo "✅ server.php" || echo "❌ server.php manquant"
[ -f ".env" ] && echo "✅ .env" || echo "❌ .env manquant"
[ -d "vendor" ] && echo "✅ vendor/" || echo "❌ vendor/ manquant - lancer 'composer install'"

# 4. Test de configuration
echo -e "\n⚙️ Configuration :"
if [ -f ".env" ]; then
    source .env
    echo "Host: ${MYSQL_HOST:-non défini}"
    echo "Port: ${MYSQL_PORT:-non défini}"  
    echo "User: ${MYSQL_USER:-non défini}"
    echo "Database: ${MYSQL_DB:-non défini}"
else
    echo "❌ Fichier .env non trouvé"
fi

# 5. Test de connexion MySQL
echo -e "\n🔌 Test de connexion :"
if [ -n "$MYSQL_HOST" ] && [ -n "$MYSQL_USER" ] && [ -n "$MYSQL_PASS" ]; then
    mysql -h "$MYSQL_HOST" -P "${MYSQL_PORT:-3306}" -u "$MYSQL_USER" -p"$MYSQL_PASS" "${MYSQL_DB}" -e "SELECT 1" 2>/dev/null
    if [ $? -eq 0 ]; then
        echo "✅ Connexion MySQL OK"
    else
        echo "❌ Connexion MySQL échouée"
    fi
else
    echo "⚠️ Variables de connexion manquantes"
fi

# 6. Test du serveur MCP
echo -e "\n🚀 Test du serveur MCP :"
if [ -f "test_mcp_server.php" ]; then
    timeout 10 php test_mcp_server.php >/dev/null 2>&1
    if [ $? -eq 0 ]; then
        echo "✅ Serveur MCP OK"
    else
        echo "❌ Serveur MCP échoué"
    fi
else
    echo "⚠️ test_mcp_server.php non trouvé"
fi

echo -e "\n🎯 Diagnostic terminé !"
```

### Logs et Debug

#### Activation des logs détaillés
```bash
# Dans .env
LOG_LEVEL=DEBUG
ENABLE_QUERY_LOGGING=true

# Puis lancer
php server.php 2>&1 | tee server.log
```

#### Analyse des logs
```bash
# Erreurs de connexion
grep -i "connection" server.log

# Erreurs de permissions
grep -i "permission\|security" server.log

# Requêtes lentes
grep -i "execution_time" server.log | awk '$NF > 1000'
```

### Monitoring en Temps Réel

```bash
#!/bin/bash
# monitor.sh

while true; do
    clear
    echo "🔍 Monitoring MCP MySQL - $(date)"
    echo "================================="
    
    # Statut du serveur
    if pgrep -f "server.php" > /dev/null; then
        echo "✅ Serveur MCP actif"
    else
        echo "❌ Serveur MCP inactif"
    fi
    
    # Connexions MySQL
    if [ -f ".env" ]; then
        source .env
        connections=$(mysql -h "$MYSQL_HOST" -P "${MYSQL_PORT:-3306}" -u "$MYSQL_USER" -p"$MYSQL_PASS" -e "SHOW STATUS LIKE 'Threads_connected'" 2>/dev/null | tail -1 | awk '{print $2}')
        echo "🔗 Connexions MySQL actives: ${connections:-N/A}"
    fi
    
    # Logs récents
    echo -e "\n📋 Logs récents :"
    tail -5 server.log 2>/dev/null || echo "Aucun log disponible"
    
    sleep 5
done
```

---

## 🆘 Support et Communauté

### Avant de Demander de l'Aide

1. **Lancez le diagnostic** : `bash diagnostic.sh`
2. **Vérifiez les logs** : `LOG_LEVEL=DEBUG php server.php`
3. **Testez la configuration** : `php test_mcp_server.php`
4. **Consultez cette documentation**

### Informations à Fournir

Lorsque vous demandez de l'aide, incluez :

```bash
# Informations système
php --version
mysql --version
composer --version

# Configuration (sans mots de passe)
grep -v "MYSQL_PASS" .env

# Logs d'erreur
tail -20 server.log

# Test de diagnostic
bash diagnostic.sh
```

### Templates d'Issues

#### Bug Report
```markdown
**Environnement :**
- OS: [Ubuntu 20.04, macOS, etc.]
- PHP: [version]
- MySQL: [version]
- Serveur MCP MySQL: [version/commit]

**Configuration :**
```bash
[Configuration .env sans mots de passe]
```

**Symptômes :**
[Description détaillée du problème]

**Logs :**
```
[Logs d'erreur pertinents]
```

**Étapes pour reproduire :**
1. [Étape 1]
2. [Étape 2]
3. [Voir l'erreur]

**Comportement attendu :**
[Ce qui devrait se passer]
```

---

## 🔧 Solutions Avancées

### Reset Complet

```bash
#!/bin/bash
# reset.sh - Reset complet du serveur

echo "🔄 Reset complet du serveur MCP MySQL"

# 1. Arrêter tous les processus
pkill -f "server.php"

# 2. Nettoyer les dépendances
rm -rf vendor/ composer.lock

# 3. Reinstaller
composer install

# 4. Reset de la configuration
cp .env.example .env
echo "⚠️ Éditez .env avec vos paramètres"

# 5. Test
php test_connection.php
```

### Migration de Version

```bash
#!/bin/bash
# migrate.sh - Migration vers nouvelle version

echo "📦 Migration du serveur MCP MySQL"

# Backup de la configuration
cp .env .env.backup
cp -r src/ src.backup/

# Mise à jour
git pull origin main
composer update

# Vérification
php test_mcp_server.php

echo "✅ Migration terminée"
echo "⚠️ Configurations sauvegardées dans *.backup"
```

---

**Problème non résolu ? Consultez la [documentation complète](./README.md) ou ouvrez une issue !** 🆘