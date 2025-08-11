# Résolution du problème "MySQL server has gone away"

## Diagnostic

L'erreur "MySQL server has gone away" (code 2006) indique que la connexion MySQL s'est fermée de manière inattendue. Causes principales :

1. **Timeout de connexion** - MySQL ferme les connexions inactives
2. **Serveur surchargé** - Redémarrage ou manque de ressources  
3. **Requêtes trop longues** - Dépassement des limites de temps
4. **Pool de connexions** - Connexions obsolètes

## Solutions implémentées

### 1. Détection automatique des connexions mortes
- Vérification `SELECT 1` avant réutilisation
- Suppression automatique des connexions fermées
- Logs détaillés des reconnexions

### 2. Reconnexion automatique
- Retry automatique sur erreurs 2006/2013
- Méthode `executeWithRetry()` avec 2 tentatives
- Nettoyage du pool en cas d'échec

### 3. Configuration PDO optimisée
- `MYSQL_ATTR_USE_BUFFERED_QUERY => false`
- `MYSQL_ATTR_FOUND_ROWS => true` 
- Timeout configurables via `QUERY_TIMEOUT`

## Configuration serveur MySQL recommandée

```sql
-- Dans my.cnf ou mysql.conf
[mysqld]
# Timeout des connexions inactives (8 heures par défaut)
wait_timeout = 28800
interactive_timeout = 28800

# Timeout des requêtes longues  
max_execution_time = 30000

# Buffers pour éviter les déconnexions
max_allowed_packet = 64M
net_read_timeout = 30
net_write_timeout = 30

# Pool de connexions
max_connections = 200
```

## Variables d'environnement

```bash
# Timeout des requêtes PHP (secondes)
QUERY_TIMEOUT=30

# Taille du pool de connexions
CONNECTION_POOL_SIZE=5

# Niveau de log pour le debug
LOG_LEVEL=INFO
```

## Tests de validation

```bash
# Test de connexion de base
php tests/test_connection.php

# Test avec variables d'environnement
MYSQL_HOST=127.0.0.1 MYSQL_PORT=3306 php tests/test_mcp_server.php

# Test de charge (optionnel)
for i in {1..10}; do php tests/test_connection.php; done
```

## Monitoring

Les logs incluent maintenant :
- Détection des connexions mortes
- Tentatives de reconnexion
- Nettoyage du pool de connexions
- Statistiques des retry

## Cas d'usage spéciaux

### Base de données distante
Augmentez les timeouts pour les connexions réseau :
```bash
QUERY_TIMEOUT=60
```

### Requêtes lourdes  
Pour l'analyse de grandes tables :
```bash
QUERY_TIMEOUT=300  # 5 minutes
```

### Environnement de développement
Pool plus petit pour économiser les ressources :
```bash
CONNECTION_POOL_SIZE=2
```