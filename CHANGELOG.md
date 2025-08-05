# Changelog

Toutes les modifications notables de ce projet seront documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Rien pour le moment

## [1.0.1] - 2025-08-05

### Fixed
- 🧹 **Nettoyage des variables de configuration** : Suppression de 7 variables déclarées mais non implémentées
- 📚 **Documentation cohérente** : Mise à jour de tous les exemples et guides pour correspondre aux fonctionnalités réelles
- 🎯 **Expérience utilisateur améliorée** : Suppression des options confuses qui ne fonctionnaient pas

### Removed
- `ENABLE_QUERY_LOGGING` - Pas de système de logging des requêtes implémenté
- `CACHE_TTL` - Pas de système de cache implémenté
- `ENABLE_QUERY_CACHE` - Pas de fonctionnalité de cache de requêtes
- `ENABLE_TRANSACTIONS` - Pas de gestion des transactions implémentée
- `ENABLE_SCHEMA_INTROSPECTION` - Fonctionnalité non utilisée dans l'implémentation
- `ENABLE_EXPORT_TOOLS` - Pas d'outils d'export implémentés
- `LOG_FILE` - Le logging se fait uniquement vers stdout/stderr

### Documentation
- Mise à jour du template `.env.example`
- Nettoyage du guide de configuration MCP
- Correction de tous les exemples dans la documentation
- Suppression des références aux fonctionnalités non implémentées

**Note** : Aucun changement breaking, toutes les fonctionnalités existantes sont préservées.

## [1.0.0] - 2025-08-05

### Added
- 🎉 **Release initiale stable** du serveur MySQL MCP
- 🔧 **Serveur MCP complet** avec support de toutes les opérations MySQL (SELECT, INSERT, UPDATE, DELETE)
- 🛡️ **Système de sécurité avancé** avec contrôles de permissions configurables
- ⚙️ **Configuration par variables d'environnement** pour tous les paramètres
- 📡 **Support multi-transport** MCP (stdio, http, websocket)
- 🔄 **Connection pooling et cache** pour optimiser les performances
- 📝 **Gestion d'erreurs et logging** complets et structurés
- 📚 **Documentation exhaustive** avec guides d'installation et démarrage rapide
- 🔧 **Guide de configuration MCP** pour tous les clients (Claude Code, Cursor, Zed)
- 📖 **Exemples d'usage** et configuration multi-projets
- 🚨 **Guide de dépannage** et bonnes pratiques
- 🧪 **Utilitaires de test** pour connexion et validation de configuration
- 📦 **Package Composer** prêt pour publication sur Packagist
- ⚖️ **Licence MIT** pour usage commercial
- 🛡️ **Sécurité renforcée** : pas de credentials dans le repository
- 🎯 **Tests complets** : connexion, configuration MCP, serveur

### Technical
- Support PHP 8.1+
- Intégration avec php-mcp/server v3.3
- Architecture modulaire avec services et outils MCP
- Validation des requêtes et sanitisation
- Gestion des timeouts et limites de résultats
- Dependency injection container
- PSR-12 coding standards
- Package Composer pour installation via `composer require morgan/mysql-mcp`
- Scripts d'installation automatiques
- Badges de documentation dans README

### Changed
- Restructuration en dossiers : bin/, tests/, scripts/, examples/
- Amélioration du README avec installation Composer
- Noms de package standardisés

### Removed
- CONFIGURATION_GUIDE.md redondant (fusionné dans docs/)

## [1.0.0] - 2025-08-05

### Added
- Serveur MCP MySQL complet avec 9 outils
- Configuration par variables d'environnement
- Support multi-projets
- Documentation complète dans docs/
- Tests automatisés
- Exemples de configuration
- Sécurité avec validation SQL
- Gestion de pool de connexions
- Support pour MySQL 5.7+ et 8.0+

### Features
- **Base de données** : list_databases, list_tables, describe_table, server_status
- **Requêtes** : mysql_select avec sécurité anti-injection
- **Écriture** : mysql_insert, mysql_update, mysql_delete avec permissions
- **Avancé** : mysql_execute_query pour requêtes personnalisées
- **Configuration** : .env, variables MCP, arguments CLI
- **Multi-projets** : wrapper pour configurations séparées