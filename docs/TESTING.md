# Guide de test - PHP MCP MySQL

Documentation complète de la stratégie de test pour le serveur MCP MySQL PHP.

## 🎯 Vue d'ensemble

Ce projet utilise une approche de test multicouche avec Codeception et PHPUnit :

- **Tests unitaires** : Services isolés sans dépendances externes
- **Tests d'intégration** : Fonctionnalités complètes avec vraie base MySQL  
- **Tests de permissions** : Validation des configurations de sécurité
- **Tests Docker automatisés** : Environnement isolé et reproductible

## 📋 Table des matières

1. [Installation et configuration](#installation-et-configuration)
2. [Architecture des tests](#architecture-des-tests)
3. [Types de tests](#types-de-tests)
4. [Exécution des tests](#exécution-des-tests)
5. [Environnement Docker](#environnement-docker)
6. [Configuration des permissions](#configuration-des-permissions)
7. [Rapports et couverture](#rapports-et-couverture)
8. [CI/CD](#cicd)
9. [Dépannage](#dépannage)

## ⚙️ Installation et configuration

### Prérequis

```bash
# Dépendances système
- PHP 8.1+
- Composer
- Docker & docker-compose
- MySQL client (optionnel, pour debug)
```

### Installation des dépendances de test

```bash
# Installation complète avec dépendances de développement
make install-dev

# Ou manuellement
composer install --dev

# Construction des classes Codeception
make build
```

## 🏗️ Architecture des tests

### Structure des dossiers

```
tests/
├── Unit/                          # Tests unitaires isolés
│   ├── SecurityServiceTest.php    # Tests de validation/sécurité
│   └── ConnectionServiceTest.php  # Tests du pool de connexions
├── Integration/                   # Tests avec vraie base de données
│   ├── ConnectionServiceIntegrationTest.php
│   ├── DatabaseToolsIntegrationTest.php
│   ├── QueryToolsIntegrationTest.php
│   └── PermissionsIntegrationTest.php
├── fixtures/                     # Données de test
│   └── test-data.sql            # Structure et données MySQL
├── scripts/                     # Scripts d'automatisation
│   ├── run-docker-tests.php    # Runner PHP
│   └── docker-test-complete.sh # Runner Bash complet
├── Support/                     # Classes helper Codeception
│   └── Helper/
│       ├── Unit.php            # Helpers pour tests unitaires
│       └── Integration.php     # Helpers pour tests d'intégration
├── Unit.suite.yml              # Configuration tests unitaires
├── Integration.suite.yml       # Configuration tests d'intégration
└── Functional.suite.yml        # Configuration tests fonctionnels
```

### Configuration Codeception

**Tests unitaires** (`tests/Unit.suite.yml`) :
```yaml
actor: UnitTester
modules:
    enabled:
        - Asserts
step_decorators: ~
```

**Tests d'intégration** (`tests/Integration.suite.yml`) :
```yaml
actor: IntegrationTester
modules:
    enabled:
        - Asserts
        - Db:
            dsn: 'mysql:host=127.0.0.1;port=33306;dbname=testdb'
            user: 'testuser'
            password: 'testpass'
            dump: tests/fixtures/test-data.sql
            cleanup: false
            reconnect: true
        - \\MySqlMcp\\Tests\\Support\\Helper\\Integration
```

## 🧪 Types de tests

### 1. Tests unitaires

**Objectif** : Validation de la logique métier sans dépendances externes.

**Classes testées** :
- `SecurityService` : Validation des requêtes, permissions, mots-clés dangereux
- `ConnectionService` : Pool de connexions, configuration (tests logiques uniquement)

**Caractéristiques** :
- Exécution rapide (<5 secondes)
- Pas de vraie base de données
- Mocks et stubs pour les dépendances
- Validation des edge cases et configurations

**Exemple** :
```php
public function testDDLOperationsBlocked()
{
    $config = $this->tester->createMockConfig(['ALLOW_DDL_OPERATIONS' => false]);
    $service = new SecurityService($config, $this->mockLogger);
    
    $this->expectException(SecurityException::class);
    $this->expectExceptionMessage('ALTER');
    
    $service->validateQuery('ALTER TABLE test ADD COLUMN name VARCHAR(100)');
}
```

### 2. Tests d'intégration

**Objectif** : Validation des fonctionnalités complètes avec vraie base MySQL.

**Classes testées** :
- `ConnectionService` : Connexions réelles, transactions, pool
- `DatabaseTools` : Listage bases/tables, description, indexes
- `QueryTools` : Exécution CRUD, requêtes complexes
- Intégration complète MCP → Services → MySQL

**Caractéristiques** :
- Base MySQL Docker dédiée
- Données de test via fixtures
- Tests de performance
- Validation UTF-8, types de données
- Tests de concurrence

**Exemple** :
```php
public function testExecuteSelectQuery()
{
    $result = $this->queryTools->executeQuery('SELECT * FROM users LIMIT 3');
    
    $this->assertTrue($result['success']);
    $this->assertArrayHasKey('data', $result);
    $this->assertArrayHasKey('execution_time_ms', $result);
    $this->assertLessThanOrEqual(3, count($result['data']));
}
```

### 3. Tests de permissions

**Objectif** : Validation complète du système de permissions et sécurité.

**Scénarios testés** :
- Permissions CRUD (INSERT, UPDATE, DELETE, TRUNCATE)
- Permissions DDL (CREATE, ALTER, DROP)
- Mode super admin (ALLOW_ALL_OPERATIONS)
- Schémas autorisés (ALLOWED_SCHEMAS)
- Limites (MAX_RESULTS, QUERY_TIMEOUT)
- Combinaisons de permissions

**Exemple** :
```php
public function testSuperAdminMode()
{
    $config = $this->tester->createTestConfig(['ALLOW_ALL_OPERATIONS' => true]);
    $securityService = new SecurityService($config);
    
    // Toutes les opérations doivent être autorisées
    $result = $this->queryTools->executeQuery("CREATE TABLE super_admin_test (id INT)");
    $this->assertTrue($result['success']);
}
```

## 🚀 Exécution des tests

### Commandes principales

```bash
# Tous les tests avec Docker (recommandé)
make test

# Tests unitaires seulement (rapide)
make test-unit

# Tests d'intégration seulement
make test-integration

# Tests avec rapport de couverture
make test-coverage

# Tests rapides (unitaires sans Docker)
make test-quick
```

### Scripts avancés

```bash
# Script bash complet avec options
./tests/scripts/docker-test-complete.sh [OPTIONS]

Options:
  -v, --verbose     Mode verbeux
  -u, --unit-only   Tests unitaires seulement
  -i, --integration-only  Tests d'intégration seulement
  -c, --coverage    Générer rapport de couverture
  -h, --help        Aide

# Exemples
./tests/scripts/docker-test-complete.sh -v -c    # Tous + couverture + verbeux
./tests/scripts/docker-test-complete.sh -u       # Unitaires seulement
./tests/scripts/docker-test-complete.sh -i       # Intégration seulement
```

### Codeception direct

```bash
# Tests par suite
vendor/bin/codecept run unit
vendor/bin/codecept run integration
vendor/bin/codecept run functional

# Tests spécifiques
vendor/bin/codecept run unit SecurityServiceTest
vendor/bin/codecept run integration QueryToolsIntegrationTest:testExecuteSelectQuery

# Avec options
vendor/bin/codecept run --verbose --coverage --coverage-html
```

## 🐳 Environnement Docker

### Configuration automatique

Le système Docker démarre automatiquement une instance MySQL 8.0 dédiée aux tests :

**Container** : `php-mcp-mysql-test`
**Port** : `33306` (évite les conflits)
**Base** : `testdb`
**Utilisateur** : `testuser` / `testpass`

### Données de test

Le fichier `tests/fixtures/test-data.sql` est automatiquement chargé :

```sql
-- Tables créées
- users (id, name, email, created_at, updated_at)
- posts (id, user_id, title, content, status, created_at)  
- sensitive_data (id, secret_value, access_level)
- test_ddl (id, data) -- pour tests DDL

-- Données de test incluses
- 3 utilisateurs de test
- 4 posts liés aux utilisateurs
- Données sensibles avec différents niveaux d'accès
```

### Gestion Docker

```bash
# Contrôle manuel du container
make docker-up      # Démarrer MySQL
make docker-down    # Arrêter MySQL
make docker-logs    # Voir les logs
make docker-shell   # Shell MySQL interactif

# Nettoyage complet
make clean          # Supprime containers et volumes
```

## 🔒 Configuration des permissions

### Variables d'environnement de test

Les tests utilisent un système de configuration flexible :

```bash
# Permissions CRUD
ALLOW_INSERT_OPERATION=true
ALLOW_UPDATE_OPERATION=true  
ALLOW_DELETE_OPERATION=true
ALLOW_TRUNCATE_OPERATION=false

# Permissions DDL
ALLOW_DDL_OPERATIONS=false

# Mode super admin (autorise tout)
ALLOW_ALL_OPERATIONS=false

# Sécurité
MAX_RESULTS=1000
QUERY_TIMEOUT=30
ALLOWED_SCHEMAS=""              # Vide = tous autorisés
BLOCK_DANGEROUS_KEYWORDS=true

# Connexion
MYSQL_HOST=127.0.0.1
MYSQL_PORT=33306
MYSQL_USER=testuser
MYSQL_PASS=testpass
MYSQL_DB=testdb
```

### Helpers de configuration

```php
// Dans les tests d'intégration
$this->tester->setTestEnvironment([
    'ALLOW_DDL_OPERATIONS' => 'true',
    'ALLOW_ALL_OPERATIONS' => 'false'
]);

$config = $this->tester->createTestConfig();
```

## 📊 Rapports et couverture

### Génération des rapports

```bash
# Couverture de code
make test-coverage

# Rapports générés dans
- coverage/index.html           # Rapport HTML interactif
- tests/_output/coverage.xml    # Rapport XML (CI/CD)
- tests/reports/               # Rapports de synthèse
```

### Métriques ciblées

- **Couverture** : >90% pour les services critiques
- **Performance** : Requêtes <1s, tests complets <5min
- **Fiabilité** : 100% de succès sur environnement propre

### Types de rapports

1. **HTML interactif** : Navigation par fichier, lignes couvertes/non couvertes
2. **XML Clover** : Intégration CI/CD, outils d'analyse
3. **Synthèse textuelle** : Résumé rapide dans `tests/reports/`

## 🔄 CI/CD

### GitHub Actions

Configuration dans `.github/workflows/tests.yml` :

**Matrix PHP** : 8.1, 8.2, 8.3
**Services** : MySQL 8.0 automatique
**Étapes** :
1. Installation dépendances avec cache
2. Démarrage MySQL et import fixtures  
3. Tests unitaires + intégration
4. Rapport de couverture Codecov
5. Artefacts (rapports, logs)

**Jobs séparés** :
- `test` : Tests principaux multi-version PHP
- `lint` : Validation syntaxe et Composer
- `docker-test` : Tests avec script Docker complet

### Variables CI/CD

```yaml
env:
  MYSQL_HOST: 127.0.0.1
  MYSQL_PORT: 33306
  MYSQL_USER: testuser
  MYSQL_PASS: testpass
  MYSQL_DB: testdb
  # Permissions de test complètes
  ALLOW_INSERT_OPERATION: true
  ALLOW_UPDATE_OPERATION: true
  ALLOW_DELETE_OPERATION: true
  ALLOW_DDL_OPERATIONS: true
```

## 🐛 Dépannage

### Problèmes courants

#### MySQL ne démarre pas

```bash
# Vérifier Docker
docker ps -a
docker logs php-mcp-mysql-test

# Nettoyer complètement
make clean
make test
```

#### Port 33306 occupé

```bash
# Identifier le processus
sudo lsof -i :33306

# Changer le port dans docker-compose.test.yml
ports:
  - "33307:3306"  # Utiliser 33307 à la place
```

#### Tests d'intégration échouent

```bash
# Vérifier la connexion
make docker-shell
# Doit ouvrir MySQL

# Variables d'environnement
env | grep MYSQL_
env | grep ALLOW_

# Reconstruire les classes
make build
```

#### Permissions insuffisantes

```bash
# Scripts exécutables  
chmod +x tests/scripts/*.sh
chmod +x tests/scripts/*.php

# Docker sans sudo
sudo usermod -aG docker $USER
# Puis redémarrer la session
```

### Debug avancé

```bash
# Informations système
make debug

# Mode verbeux complet
./tests/scripts/docker-test-complete.sh -v

# Tests spécifiques avec debug
vendor/bin/codecept run integration --debug --verbose

# Logs MySQL en temps réel
make docker-logs
```

### Nettoyage en cas de problème

```bash
# Nettoyage leger
make clean

# Nettoyage complet (supprime vendor/)
make clean-all

# Réinstallation complète
make clean-all
make install-dev
make build
make test
```

## 📝 Bonnes pratiques

### Écriture de tests

1. **Isolation** : Chaque test doit être indépendant
2. **Nettoyage** : Utilisez `_before()` et `_after()` 
3. **Données de test** : Utilisez des emails uniques comme `test-{$id}@example.com`
4. **Assertions** : Testez les résultats ET les effets de bord
5. **Performance** : Limitez les tests longs aux cas critiques

### Organisation

1. **Un test par fonctionnalité** : Divisez les tests complexes
2. **Nommage clair** : `testMethodName_Scenario_ExpectedResult`  
3. **Documentation** : Commentez les tests complexes
4. **Groupement** : Utilisez les commentaires `// ===== SECTION =====`

### Maintenance

1. **Mise à jour régulière** : Synchronisez avec les changements du code
2. **Nettoyage** : Supprimez les tests obsolètes
3. **Optimisation** : Surveillez les temps d'exécution
4. **Documentation** : Tenez à jour ce guide

## 🎯 Couverture actuelle

### Services principaux

- **SecurityService** : ~95% (toutes les méthodes publiques + edge cases)
- **ConnectionService** : ~85% (logique + intégration réelle)
- **DatabaseTools** : ~90% (outils MCP complets)
- **QueryTools** : ~88% (CRUD + permissions + sécurité)

### Scénarios couverts

✅ **Permissions** : Toutes les combinaisons CRUD/DDL/SuperAdmin  
✅ **Sécurité** : Injection SQL, mots-clés dangereux, schémas  
✅ **Performance** : Pool de connexions, timeouts, limites  
✅ **Robustesse** : Erreurs MySQL, configurations invalides  
✅ **UTF-8** : Caractères spéciaux, emojis, encodage  

### À améliorer

🔲 **Tests fonctionnels** : API MCP complète end-to-end  
🔲 **Tests de charge** : Performance sous stress  
🔲 **Tests de régression** : Cas spécifiques découverts en production

---

**Dernière mise à jour** : $(date)  
**Version** : 1.0.2  
**Contributeurs** : Équipe de développement PHP MCP MySQL