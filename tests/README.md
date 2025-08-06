# Tests PHP MCP MySQL

Suite de tests complète pour le serveur MCP MySQL PHP avec Codeception et Docker.

## 🚀 Démarrage rapide

```bash
# Installation et premier test
make install-dev
make test

# Tests spécifiques
make test-unit          # Tests unitaires seulement (rapide)
make test-integration   # Tests d'intégration avec MySQL Docker
make test-coverage      # Tests avec rapport de couverture
```

## 📁 Structure

```
tests/
├── Unit/               # Tests unitaires (pas de DB)
├── Integration/        # Tests d'intégration (avec MySQL Docker)
├── fixtures/           # Données de test SQL
├── scripts/            # Scripts d'automatisation
└── Support/            # Helpers Codeception
```

## 🧪 Types de tests

### Tests unitaires (`Unit/`)
- **SecurityServiceTest** : Validation des permissions et sécurité
- **ConnectionServiceTest** : Pool de connexions (logique seulement)

### Tests d'intégration (`Integration/`)
- **ConnectionServiceIntegrationTest** : Connexions réelles MySQL
- **DatabaseToolsIntegrationTest** : Outils de gestion des bases/tables
- **QueryToolsIntegrationTest** : Exécution de requêtes CRUD
- **PermissionsIntegrationTest** : Tests complets des permissions

## 🐳 Environnement Docker

**Container automatique** : `php-mcp-mysql-test`  
**Port** : `33306` (évite les conflits)  
**Données** : Chargées depuis `fixtures/test-data.sql`

```bash
# Contrôle manuel
make docker-up      # Démarrer MySQL
make docker-down    # Arrêter MySQL  
make docker-shell   # Console MySQL
make clean          # Nettoyage complet
```

## 🔧 Configuration

### Variables d'environnement

```bash
# Connexion MySQL
MYSQL_HOST=127.0.0.1
MYSQL_PORT=33306
MYSQL_USER=testuser
MYSQL_PASS=testpass
MYSQL_DB=testdb

# Permissions (configurables par test)
ALLOW_INSERT_OPERATION=true
ALLOW_UPDATE_OPERATION=true
ALLOW_DELETE_OPERATION=true
ALLOW_DDL_OPERATIONS=false
ALLOW_ALL_OPERATIONS=false
```

### Helpers de test

```php
// Tests unitaires
$config = $this->tester->createMockConfig(['ALLOW_DDL_OPERATIONS' => true]);
$logger = $this->tester->createMockLogger();

// Tests d'intégration
$this->tester->setTestEnvironment(['ALLOW_ALL_OPERATIONS' => 'true']);
$config = $this->tester->createTestConfig();
```

## 📊 Rapports

```bash
# Génération rapports
make test-coverage

# Localisation
- coverage/index.html           # Rapport HTML
- tests/_output/coverage.xml    # XML pour CI
- tests/reports/                # Synthèses
```

## 🎯 Couverture

- **SecurityService** : ~95% (permissions, validation, sécurité)
- **ConnectionService** : ~85% (pool, configuration, erreurs)
- **DatabaseTools** : ~90% (listage, description, metadata)
- **QueryTools** : ~88% (CRUD, transactions, performance)

## 🐛 Dépannage

### Erreurs communes

```bash
# MySQL ne démarre pas
make clean && make test

# Port occupé
# → Modifier port dans docker-compose.test.yml

# Permission denied
chmod +x tests/scripts/*.sh

# Classes Codeception
make build
```

### Debug

```bash
# Informations système
make debug

# Tests verbeux
./tests/scripts/docker-test-complete.sh -v

# Test spécifique
vendor/bin/codecept run integration QueryToolsIntegrationTest:testExecuteSelectQuery --debug
```

## 📚 Documentation complète

Voir [docs/TESTING.md](../docs/TESTING.md) pour la documentation détaillée incluant :
- Architecture des tests
- Configuration avancée
- CI/CD GitHub Actions  
- Bonnes pratiques
- Stratégies de test

## 🤝 Contribution

1. **Nouveaux tests** : Suivre les patterns existants
2. **Nommage** : `testMethodName_Scenario_ExpectedResult`
3. **Isolation** : Chaque test doit être indépendant  
4. **Nettoyage** : Utiliser `_before()` et `_after()`
5. **Documentation** : Commenter les tests complexes

---

**Quick commands**:
- `make test` - Tous les tests  
- `make test-unit` - Rapide (pas de Docker)
- `make test-coverage` - Avec rapports
- `make clean` - Nettoyage Docker