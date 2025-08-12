# 📋 Plan de Migration MySQL → PostgreSQL MCP Server

## 🎯 Objectif
Créer un serveur MCP PostgreSQL indépendant basé sur le code existant MySQL MCP.

## 📊 État d'avancement global: 10%

## ✅ Phase 1: Initialisation (COMPLÉTÉ ✓)
- [x] Duplication du projet source
- [x] Création du plan de migration
- [ ] Configuration Git et GitHub

## 🔄 Phase 2: Refactoring de base (EN COURS - 25%)
- [ ] **composer.json** - Métadonnées et dépendances
  - [ ] Renommer le package
  - [ ] Mettre à jour les dépendances (ext-pdo_pgsql)
  - [ ] Ajuster les scripts
- [ ] **Namespaces PHP**
  - [ ] MySqlMcp\ → PostgreSqlMcp\
  - [ ] Mise à jour autoloading
- [ ] **Variables d'environnement**
  - [ ] MYSQL_* → PGSQL_*
  - [ ] .env.example
  - [ ] Documentation config

## 🗄️ Phase 3: Adaptation ConnectionService (0%)
- [ ] **DSN PostgreSQL**
  ```php
  // Avant: mysql:host=$host;port=$port
  // Après: pgsql:host=$host;port=$port
  ```
- [ ] **Options PDO spécifiques**
  - [ ] Retirer MYSQL_ATTR_*
  - [ ] Ajouter options PostgreSQL
- [ ] **Gestion erreurs**
  - [ ] Codes erreur PostgreSQL
  - [ ] Messages adaptés
- [ ] **Tests connexion**
  - [ ] SELECT 1 → SELECT 1

## 🛠️ Phase 4: DatabaseTools PostgreSQL (0%)

### Mappings requis:
| MySQL | PostgreSQL | Statut |
|-------|------------|--------|
| SHOW DATABASES | SELECT datname FROM pg_database WHERE datistemplate = false | ⏳ |
| SHOW TABLES | SELECT tablename FROM pg_tables WHERE schemaname = 'public' | ⏳ |
| DESCRIBE table | Information_schema.columns query | ⏳ |
| SHOW INDEX | pg_indexes system view | ⏳ |
| SHOW STATUS | pg_stat_activity + pg_stat_database | ⏳ |

### Tâches:
- [ ] listDatabases() - Requête pg_database
- [ ] listTables() - Requête pg_tables
- [ ] describeTable() - Information_schema
- [ ] getServerStatus() - pg_stat views

## 📝 Phase 5: QueryTools Adaptation (0%)
- [ ] **Syntaxe des identifiants**
  - [ ] Backticks `` → Double quotes ""
  - [ ] Échappement adapté
- [ ] **Fonctionnalités PostgreSQL**
  - [ ] Support RETURNING clause
  - [ ] Arrays natifs
  - [ ] JSONB support
- [ ] **Validation sécurité**
  - [ ] Keywords PostgreSQL dangereux
  - [ ] Injection patterns

## 📚 Phase 6: Documentation (0%)
- [ ] **README.md** principal
- [ ] **Installation** PostgreSQL
- [ ] **Configuration** spécifique
- [ ] **Exemples** d'utilisation
- [ ] **Troubleshooting** PostgreSQL
- [ ] **CHANGELOG.md** initial

## 🧪 Phase 7: Tests (0%)
- [ ] **Tests unitaires**
  - [ ] ConnectionService
  - [ ] SecurityService
- [ ] **Tests d'intégration**
  - [ ] DatabaseTools
  - [ ] QueryTools
- [ ] **Docker Compose** pour tests
  - [ ] Container PostgreSQL
  - [ ] Scripts d'initialisation

## 🚀 Phase 8: Publication (0%)
- [ ] **GitHub Repository**
  - [ ] Création repo
  - [ ] Push initial
  - [ ] Configuration Actions
- [ ] **Packagist**
  - [ ] Enregistrement package
  - [ ] Webhook auto-update
- [ ] **Version 1.0.0**
  - [ ] Tag release
  - [ ] Release notes

## 📈 Métriques de progression

| Composant | Fichiers | Modifiés | % |
|-----------|----------|----------|---|
| Core | 3 | 0 | 0% |
| Services | 2 | 0 | 0% |
| Elements | 2 | 0 | 0% |
| Config | 5 | 0 | 0% |
| Docs | 10 | 0 | 0% |
| Tests | 8 | 0 | 0% |
| **TOTAL** | **30** | **1** | **3%** |

## 🔄 Prochaines étapes immédiates

1. **Mettre à jour composer.json**
2. **Refactorer les namespaces PHP**
3. **Adapter ConnectionService**
4. **Créer test de connexion basique**

## ⏱️ Estimation temps restant

- Phase 2: 2h
- Phase 3: 3h
- Phase 4: 4h
- Phase 5: 3h
- Phase 6: 2h
- Phase 7: 3h
- Phase 8: 1h

**Total estimé: 18 heures**

## 📝 Notes techniques

### Différences critiques PostgreSQL:
- **Schémas**: PostgreSQL a des schémas (public par défaut)
- **Casse**: PostgreSQL force minuscules sauf avec quotes
- **Types**: SERIAL vs AUTO_INCREMENT, JSONB, Arrays
- **Fonctions**: Syntaxe différente pour certaines fonctions
- **Permissions**: Modèle GRANT/REVOKE plus granulaire

### Points d'attention:
- ⚠️ Les backticks MySQL deviennent des double quotes
- ⚠️ information_schema différent
- ⚠️ pg_catalog pour métadonnées système
- ⚠️ Gestion transactions différente

---

*Document créé le: 2025-01-12*
*Dernière mise à jour: 2025-01-12*