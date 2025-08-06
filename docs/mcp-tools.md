# 🛠️ Référence des Outils MCP

Documentation complète de tous les outils MCP disponibles dans le serveur MySQL.

## 📋 Vue d'Ensemble

Le serveur MCP MySQL propose **10 outils** organisés en 4 catégories :

| Catégorie | Outils | Description |
|-----------|--------|-------------|
| **🗄️ Base de Données** | 5 outils | Gestion et exploration |
| **📊 Requêtes Lecture** | 1 outil | Exécution SELECT sécurisée |
| **✏️ Requêtes Écriture** | 3 outils | INSERT, UPDATE, DELETE |
| **🔧 Avancé** | 1 outil | Requêtes SQL personnalisées |

---

## 🗄️ Outils de Base de Données

### `mysql_list_databases`

Liste toutes les bases de données disponibles sur le serveur MySQL.

**📥 Paramètres :** Aucun

**📤 Retour :**
```json
{
    "databases": ["app_db", "logs_db", "analytics_db"],
    "system_databases": ["information_schema", "mysql", "performance_schema", "sys"],
    "total_count": 7
}
```

**💡 Exemple d'usage avec Claude :**
```
Peux-tu me montrer toutes les bases de données disponibles ?
```

---

### `mysql_list_table_names`

Liste uniquement les noms des tables (ultra-économe en tokens pour grandes bases).

**📥 Paramètres :**
- `database` (string, optionnel) : Nom de la base de données
- `limit` (integer, optionnel) : Limite de tables (défaut: 100, max: 1000)

**📤 Retour :**
```json
{
    "database": "app_db",
    "table_names": ["users", "orders", "products", "logs"],
    "count": 4,
    "total_count": 4,
    "truncated": false
}
```

**💡 Exemples d'usage avec Claude :**
```
Quels sont les noms des tables disponibles ?
Liste-moi les 20 premières tables
```

---

### `mysql_list_tables`

Liste les tables avec informations détaillées ou simplifiées (optimisé pour éviter le dépassement de tokens).

**📥 Paramètres :**
- `database` (string, optionnel) : Nom de la base de données
- `detailed` (boolean, optionnel) : Informations détaillées (défaut: false pour économiser tokens)
- `limit` (integer, optionnel) : Limite de tables (défaut: 50, max: 500)

**📤 Retour (mode simple - défaut) :**
```json
{
    "database": "app_db",
    "tables": [
        {"name": "users"},
        {"name": "orders"},
        {"name": "products"}
    ],
    "table_count": 3,
    "total_table_count": 3,
    "detailed": false,
    "limited_to": 50,
    "truncated": false
}
```

**📤 Retour (mode détaillé) :**
```json
{
    "database": "app_db",
    "tables": [
        {
            "name": "users",
            "engine": "InnoDB",
            "collation": "utf8mb4_unicode_ci",
            "row_count": 1250,
            "data_size": 65536,
            "index_size": 32768,
            "total_size": 98304
        }
    ],
    "table_count": 1,
    "total_table_count": 25,
    "detailed": true,
    "limited_to": 50,
    "truncated": true
}
```

**💡 Exemples d'usage avec Claude :**
```
Quelles tables sont disponibles ? (mode simple par défaut)
Montre-moi les tables avec tous les détails (mode détaillé)
Liste les 10 premières tables de analytics_db
```

**🚀 Performance :**
- **Mode simple** : ~10x moins de tokens, idéal pour exploration
- **Mode détaillé** : Informations complètes avec limite anti-dépassement
- **Limite automatique** : Évite les erreurs de dépassement de tokens

---

### `mysql_describe_table`

Décrit la structure complète d'une table (colonnes, index, clés étrangères).

**📥 Paramètres :**
- `table` (string, requis) : Nom de la table
- `database` (string, optionnel) : Nom de la base de données

**📤 Retour :**
```json
{
    "table": "users",
    "database": "app_db",
    "columns": [
        {
            "Field": "id",
            "Type": "int(11)",
            "Null": "NO",
            "Key": "PRI",
            "Default": null,
            "Extra": "auto_increment"
        }
    ],
    "indexes": [
        {
            "name": "PRIMARY",
            "unique": true,
            "type": "BTREE",
            "columns": [{"column": "id", "sequence": 1}]
        }
    ],
    "foreign_keys": [],
    "column_count": 6
}
```

**💡 Exemples d'usage avec Claude :**
```
Montre-moi la structure de la table users
Décris la table orders avec ses relations
```

---

### `mysql_server_status`

Retourne des informations sur le statut et la santé du serveur MySQL.

**📥 Paramètres :** Aucun

**📤 Retour :**
```json
{
    "mysql_version": "8.0.32",
    "uptime_seconds": 86400,
    "connection_pool_size": 5,
    "active_connections": 2,
    "total_connections": 3,
    "mysql_connections": 1250,
    "mysql_queries": 45000,
    "mysql_threads_connected": 8,
    "connection_test": true
}
```

**💡 Exemples d'usage avec Claude :**
```
Quel est l'état du serveur MySQL ?
Montre-moi les statistiques de connexion
```

---

## 📊 Outils de Requête Lecture

### `mysql_select`

Exécute des requêtes SELECT avec validation de sécurité et limitation des résultats.

**📥 Paramètres :**
- `query` (string, requis) : Requête SELECT à exécuter
- `params` (array, optionnel) : Paramètres pour requête préparée
- `limit` (integer, optionnel) : Limite de résultats (1-10000)

**📤 Retour :**
```json
{
    "query": "SELECT * FROM users WHERE age > ?",
    "results": [
        {"id": 1, "name": "Alice", "age": 28, "email": "alice@example.com"},
        {"id": 3, "name": "Charlie", "age": 42, "email": "charlie@example.com"}
    ],
    "row_count": 2,
    "execution_time_ms": 15.8,
    "has_more": false
}
```

**🔒 Sécurité :**
- Validation anti-injection SQL
- Limitation automatique des résultats
- Timeout configurable
- Logging des requêtes

**💡 Exemples d'usage avec Claude :**
```
Récupère tous les utilisateurs de plus de 30 ans
SELECT * FROM orders WHERE status = 'pending' ORDER BY order_date DESC
Montre-moi les 10 dernières commandes avec les infos utilisateur
```

**🚨 Requêtes avancées :**
```sql
-- Jointures
SELECT u.name, COUNT(o.id) as order_count 
FROM users u 
LEFT JOIN orders o ON u.id = o.user_id 
GROUP BY u.id

-- Avec paramètres sécurisés
SELECT * FROM orders WHERE user_id = ? AND status = ?
-- Params: [1, "completed"]

-- Agrégations
SELECT DATE(order_date) as date, SUM(price) as daily_revenue 
FROM orders 
WHERE order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(order_date)
```

---

## ✏️ Outils d'Écriture

### `mysql_insert`

Insère de nouvelles données dans une table de manière sécurisée.

**📥 Paramètres :**
- `table` (string, requis) : Nom de la table
- `data` (object, requis) : Données à insérer (clé => valeur)
- `database` (string, optionnel) : Nom de la base de données

**📤 Retour :**
```json
{
    "query": "INSERT INTO users (name, email, age) VALUES (:name, :email, :age)",
    "table": "users",
    "database": "app_db",
    "insert_id": 123,
    "affected_rows": 1,
    "execution_time_ms": 5.2,
    "inserted_data": {
        "name": "John Doe",
        "email": "john@example.com",
        "age": 35
    }
}
```

**🔒 Permissions :**
- Nécessite `ALLOW_INSERT_OPERATION=true`
- Validation des données d'entrée
- Utilise des requêtes préparées

**💡 Exemples d'usage avec Claude :**
```
Ajoute un nouvel utilisateur : John Doe, john@example.com, 35 ans
Insère une nouvelle commande pour l'utilisateur ID 5
```

---

### `mysql_update`

Met à jour des enregistrements existants avec des conditions obligatoires.

**📥 Paramètres :**
- `table` (string, requis) : Nom de la table
- `data` (object, requis) : Données à mettre à jour
- `conditions` (object, requis) : Conditions WHERE
- `database` (string, optionnel) : Nom de la base de données

**📤 Retour :**
```json
{
    "query": "UPDATE users SET age = :set_age WHERE id = :where_id",
    "table": "users",
    "database": "app_db",
    "affected_rows": 1,
    "execution_time_ms": 3.1,
    "updated_data": {"age": 36},
    "conditions": {"id": 123}
}
```

**🔒 Sécurité :**
- Nécessite `ALLOW_UPDATE_OPERATION=true`
- Conditions WHERE obligatoires (pas d'UPDATE sans WHERE)
- Requêtes préparées automatiques

**💡 Exemples d'usage avec Claude :**
```
Met à jour l'âge de l'utilisateur ID 123 à 36 ans
Change le statut de la commande 456 à "completed"
```

---

### `mysql_delete`

Supprime des enregistrements avec des conditions obligatoires et une limite optionnelle.

**📥 Paramètres :**
- `table` (string, requis) : Nom de la table
- `conditions` (object, requis) : Conditions WHERE
- `database` (string, optionnel) : Nom de la base de données
- `limit` (integer, optionnel) : Limite de suppressions (1-1000)

**📤 Retour :**
```json
{
    "query": "DELETE FROM orders WHERE status = :status LIMIT 5",
    "table": "orders",
    "database": "app_db",
    "affected_rows": 3,
    "execution_time_ms": 8.7,
    "conditions": {"status": "cancelled"},
    "limit": 5
}
```

**🔒 Sécurité :**
- Nécessite `ALLOW_DELETE_OPERATION=true`
- Conditions WHERE obligatoires
- Limite optionnelle pour éviter les suppressions massives

**💡 Exemples d'usage avec Claude :**
```
Supprime toutes les commandes annulées (avec limite de sécurité)
Efface l'utilisateur avec l'ID 999
```

---

## 🔧 Outils Avancés

### `mysql_execute_query`

Exécute des requêtes SQL personnalisées avec détection automatique du type d'opération.

**📥 Paramètres :**
- `query` (string, requis) : Requête SQL à exécuter
- `params` (array, optionnel) : Paramètres pour requête préparée

**📤 Retour (SELECT/SHOW/DESCRIBE) :**
```json
{
    "query": "SHOW PROCESSLIST",
    "operation": "SHOW",
    "results": [...],
    "row_count": 5,
    "execution_time_ms": 12.3
}
```

**📤 Retour (INSERT/UPDATE/DELETE) :**
```json
{
    "query": "UPDATE users SET last_login = NOW() WHERE active = 1",
    "operation": "UPDATE",
    "affected_rows": 42,
    "insert_id": null,
    "execution_time_ms": 25.1
}
```

**🔒 Sécurité :**
- Détection automatique de l'opération
- Validation selon les permissions configurées
- Protection contre les requêtes dangereuses

**💡 Exemples d'usage avec Claude :**
```
Exécute: SHOW PROCESSLIST
Lance cette requête complexe: [requête avec plusieurs jointures]
Optimise cette table: OPTIMIZE TABLE users
```

**🚨 Requêtes supportées :**
- **SELECT, SHOW, DESCRIBE, EXPLAIN** : Toujours autorisées
- **INSERT** : Si `ALLOW_INSERT_OPERATION=true`
- **UPDATE** : Si `ALLOW_UPDATE_OPERATION=true`
- **DELETE** : Si `ALLOW_DELETE_OPERATION=true`
- **Autres** : Validation selon configuration

---

## 🔄 Gestion des Erreurs

### Types d'Erreurs Communes

#### **ConnectionException**
```json
{
    "error": "Impossible de se connecter à MySQL: SQLSTATE[HY000] [2002] Connection refused"
}
```

#### **SecurityException**
```json
{
    "error": "Opération DELETE non autorisée par la configuration"
}
```

#### **QueryException**
```json
{
    "error": "Erreur lors de l'exécution: Table 'app.nonexistent' doesn't exist"
}
```

### Codes de Statut

- **200** : Succès
- **400** : Erreur de paramètres
- **401** : Permission refusée
- **500** : Erreur serveur/base de données

---

## 📊 Monitoring et Performance

### Métriques Automatiques

Chaque outil retourne :
- **execution_time_ms** : Temps d'exécution en millisecondes
- **row_count/affected_rows** : Nombre de lignes impactées
- **query** : Requête exécutée (pour audit)

### Limites par Défaut

| Paramètre | Valeur | Configuration |
|-----------|---------|---------------|
| **Résultats SELECT** | 1000 | `MAX_RESULTS` |
| **Timeout requête** | 30s | `QUERY_TIMEOUT` |
| **Suppressions DELETE** | 1000 | Limite intégrée |
| **Pool connexions** | 5 | `CONNECTION_POOL_SIZE` |

---

## 🎯 Bonnes Pratiques

### Performance
- Utilisez toujours des `LIMIT` sur les gros datasets
- Préférez les requêtes préparées avec `params`
- Créez des index sur les colonnes fréquemment filtrées

### Sécurité
- Activez uniquement les permissions nécessaires
- Utilisez des requêtes préparées pour éviter l'injection SQL
- Limitez `MAX_RESULTS` en production

### Debugging
- Activez `LOG_LEVEL=DEBUG` pour voir toutes les requêtes
- Utilisez `mysql_server_status` pour monitorer la santé
- Consultez `execution_time_ms` pour identifier les requêtes lentes

---

**Prêt à explorer ? Consultez les [Exemples d'Usage](./examples.md) pour voir ces outils en action !** 🚀