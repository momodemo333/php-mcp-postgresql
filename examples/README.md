# 💡 Exemples de Configuration

Exemples de configurations MCP pour différents cas d'usage.

## 📁 Fichiers Disponibles

### `minimal-config.json`
Configuration basique pour un projet simple.
- Un seul serveur PostgreSQL
- Permissions INSERT/UPDATE activées
- Configuration via variables d'environnement

### `multi-project-config.json`
Configuration pour plusieurs environnements (dev/staging/prod).
- Trois serveurs avec permissions différentes
- Dev : Accès complet + logs debug
- Staging : Lecture/écriture limitée
- Prod : Lecture seule strict

## 🚀 Utilisation

### 1. Copiez le fichier exemple
```bash
cp examples/minimal-config.json .cursor/mcp.json
```

### 2. Adaptez les chemins
Remplacez `/home/morgan/project/customMcp/postgresql/bin/server.php` par votre chemin absolu :
```bash
realpath bin/server.php
```

### 3. Adaptez la configuration
Modifiez les variables d'environnement selon vos besoins :
- `PGSQL_HOST`, `PGSQL_PORT`, `PGSQL_USER`, `PGSQL_PASS`, `PGSQL_DB`
- **Permissions CRUD** : `ALLOW_INSERT_OPERATION`, `ALLOW_UPDATE_OPERATION`, `ALLOW_DELETE_OPERATION`
- **Permissions DDL** : `ALLOW_DDL_OPERATIONS` (CREATE, ALTER, DROP), `ALLOW_ALL_OPERATIONS` (mode super admin)
- Limites : `MAX_RESULTS`, `LOG_LEVEL`

### 4. Redémarrez Claude Code
Après modification de la configuration MCP, redémarrez Claude Code.

## 🔧 Configuration Alternative avec .env

Pour plus de sécurité, utilisez la méthode .env :

### 1. Créez le fichier .env
```bash
cp .env.example .env
# Éditez .env avec vos paramètres
```

### 2. Configuration MCP avec wrapper
```json
{
    "mcpServers": {
        "postgresql": {
            "command": "php",
            "args": [
                "/chemin/vers/postgresql/bin/server-wrapper.php",
                "/chemin/vers/votre/projet/.env"
            ]
        }
    }
}
```

## 📚 Plus d'Informations

- [Guide Multi-Projets](../docs/multi-project-setup.md)
- [Démarrage Rapide](../docs/quick-start.md)
- [Référence des Outils](../docs/mcp-tools.md)