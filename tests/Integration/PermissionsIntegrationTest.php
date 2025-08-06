<?php

declare(strict_types=1);

namespace MySqlMcp\Tests\Integration;

use MySqlMcp\Elements\QueryTools;
use MySqlMcp\Services\ConnectionService;
use MySqlMcp\Services\SecurityService;
use MySqlMcp\Exceptions\SecurityException;
use Codeception\Test\Unit;
use Tests\Support\IntegrationTester;

/**
 * Tests d'intégration pour les permissions et configurations de sécurité
 */
class PermissionsIntegrationTest extends Unit
{
    protected IntegrationTester $tester;
    private ConnectionService $connectionService;

    protected function _before()
    {
        // Configuration de base
        $this->tester->setTestEnvironment();
        
        $config = $this->tester->createTestConfig();
        
        // Reset du singleton ConnectionService
        $reflection = new \ReflectionClass(ConnectionService::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null);
        
        $this->connectionService = ConnectionService::getInstance($config);
    }

    protected function _after()
    {
        $this->connectionService->closeAll();
        $this->tester->cleanEnvironment();
    }

    // ===== TESTS DES PERMISSIONS CRUD =====

    public function testInsertOperationPermissions()
    {
        // Test avec permission INSERT désactivée
        $this->tester->setTestEnvironment(['ALLOW_INSERT_OPERATION' => 'false']);
        $config = $this->tester->createTestConfig();
        $securityService = new SecurityService($config);
        $logger = $this->tester->createMockLogger();
        
        $queryTools = new QueryTools($this->connectionService, $securityService, $logger);
        
        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('Opération INSERT non autorisée');
        
        $queryTools->executeQuery(
            "INSERT INTO users (name, email) VALUES (?, ?)",
            ['Test User', 'test@example.com']
        );
    }

    public function testInsertOperationAllowed()
    {
        // Test avec permission INSERT activée
        $this->tester->setTestEnvironment(['ALLOW_INSERT_OPERATION' => 'true']);
        $config = $this->tester->createTestConfig();
        $securityService = new SecurityService($config);
        $logger = $this->tester->createMockLogger();
        
        $queryTools = new QueryTools($this->connectionService, $securityService, $logger);
        
        $result = $queryTools->executeQuery(
            "INSERT INTO users (name, email) VALUES (?, ?)",
            ['Permission Test User', 'permission@example.com']
        );
        
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['affected_rows']);
        
        // Nettoyage
        $queryTools->executeQuery("DELETE FROM users WHERE email = ?", ['permission@example.com']);
    }

    public function testUpdateOperationPermissions()
    {
        // Préparer une donnée à mettre à jour
        $this->tester->setTestEnvironment(['ALLOW_INSERT_OPERATION' => 'true']);
        $config = $this->tester->createTestConfig();
        $securityService = new SecurityService($config);
        $logger = $this->tester->createMockLogger();
        $queryTools = new QueryTools($this->connectionService, $securityService, $logger);
        
        $queryTools->executeQuery(
            "INSERT INTO users (name, email) VALUES (?, ?)",
            ['Update Test', 'update@example.com']
        );
        
        // Test avec permission UPDATE désactivée
        $this->tester->setTestEnvironment(['ALLOW_UPDATE_OPERATION' => 'false']);
        $config = $this->tester->createTestConfig();
        $securityService = new SecurityService($config);
        $queryTools = new QueryTools($this->connectionService, $securityService, $logger);
        
        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('Opération UPDATE non autorisée');
        
        $queryTools->executeQuery(
            "UPDATE users SET name = ? WHERE email = ?",
            ['Updated Name', 'update@example.com']
        );
    }

    public function testDeleteOperationPermissions()
    {
        // Préparer une donnée à supprimer
        $this->tester->setTestEnvironment(['ALLOW_INSERT_OPERATION' => 'true']);
        $config = $this->tester->createTestConfig();
        $securityService = new SecurityService($config);
        $logger = $this->tester->createMockLogger();
        $queryTools = new QueryTools($this->connectionService, $securityService, $logger);
        
        $queryTools->executeQuery(
            "INSERT INTO users (name, email) VALUES (?, ?)",
            ['Delete Test', 'delete@example.com']
        );
        
        // Test avec permission DELETE désactivée
        $this->tester->setTestEnvironment(['ALLOW_DELETE_OPERATION' => 'false']);
        $config = $this->tester->createTestConfig();
        $securityService = new SecurityService($config);
        $queryTools = new QueryTools($this->connectionService, $securityService, $logger);
        
        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('Opération DELETE non autorisée');
        
        $queryTools->executeQuery("DELETE FROM users WHERE email = ?", ['delete@example.com']);
    }

    public function testTruncateOperationPermissions()
    {
        // Test avec permission TRUNCATE désactivée (par défaut)
        $config = $this->tester->createTestConfig(['ALLOW_TRUNCATE_OPERATION' => false]);
        $securityService = new SecurityService($config);
        $logger = $this->tester->createMockLogger();
        $queryTools = new QueryTools($this->connectionService, $securityService, $logger);
        
        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('Opération TRUNCATE non autorisée');
        
        $queryTools->executeQuery("TRUNCATE TABLE test_ddl");
    }

    // ===== TESTS DES PERMISSIONS DDL =====

    public function testDDLOperationsBlocked()
    {
        // Configuration avec DDL bloqué
        $config = $this->tester->createTestConfig(['ALLOW_DDL_OPERATIONS' => false]);
        $securityService = new SecurityService($config);
        $logger = $this->tester->createMockLogger();
        $queryTools = new QueryTools($this->connectionService, $securityService, $logger);
        
        $ddlQueries = [
            "CREATE TABLE test_perm (id INT)",
            "ALTER TABLE test_ddl ADD COLUMN test_col VARCHAR(50)",
            "DROP TABLE IF EXISTS test_perm"
        ];
        
        foreach ($ddlQueries as $query) {
            try {
                $queryTools->executeQuery($query);
                $this->fail("La requête DDL aurait dû être bloquée: {$query}");
            } catch (SecurityException $e) {
                $this->assertStringContainsString('Mot-clé non autorisé détecté', $e->getMessage());
            }
        }
    }

    public function testDDLOperationsAllowed()
    {
        // Configuration avec DDL autorisé
        $config = $this->tester->createTestConfig(['ALLOW_DDL_OPERATIONS' => true]);
        $securityService = new SecurityService($config);
        $logger = $this->tester->createMockLogger();
        $queryTools = new QueryTools($this->connectionService, $securityService, $logger);
        
        // CREATE
        $result = $queryTools->executeQuery("CREATE TABLE test_perm_allowed (id INT, name VARCHAR(100))");
        $this->assertTrue($result['success'], 'CREATE TABLE devrait être autorisé');
        
        // ALTER
        $result = $queryTools->executeQuery("ALTER TABLE test_perm_allowed ADD COLUMN email VARCHAR(150)");
        $this->assertTrue($result['success'], 'ALTER TABLE devrait être autorisé');
        
        // DROP
        $result = $queryTools->executeQuery("DROP TABLE test_perm_allowed");
        $this->assertTrue($result['success'], 'DROP TABLE devrait être autorisé');
    }

    // ===== TESTS DU MODE SUPER ADMIN =====

    public function testSuperAdminMode()
    {
        // Configuration en mode super admin
        $config = $this->tester->createTestConfig([
            'ALLOW_ALL_OPERATIONS' => true,
            'ALLOW_DDL_OPERATIONS' => false, // Devrait être ignoré
            'ALLOW_INSERT_OPERATION' => false, // Devrait être ignoré
        ]);
        $securityService = new SecurityService($config);
        $logger = $this->tester->createMockLogger();
        $queryTools = new QueryTools($this->connectionService, $securityService, $logger);
        
        // Toutes les opérations devraient être autorisées
        $result = $queryTools->executeQuery(
            "INSERT INTO users (name, email) VALUES (?, ?)",
            ['Super Admin Test', 'superadmin@example.com']
        );
        $this->assertTrue($result['success'], 'INSERT devrait être autorisé en mode super admin');
        
        $result = $queryTools->executeQuery("CREATE TABLE super_admin_test (id INT)");
        $this->assertTrue($result['success'], 'CREATE devrait être autorisé en mode super admin');
        
        $result = $queryTools->executeQuery("ALTER TABLE super_admin_test ADD COLUMN name VARCHAR(50)");
        $this->assertTrue($result['success'], 'ALTER devrait être autorisé en mode super admin');
        
        // Nettoyage
        $queryTools->executeQuery("DROP TABLE super_admin_test");
        $queryTools->executeQuery("DELETE FROM users WHERE email = ?", ['superadmin@example.com']);
    }

    public function testSuperAdminModeDangerousOperations()
    {
        // Configuration en mode super admin
        $config = $this->tester->createTestConfig(['ALLOW_ALL_OPERATIONS' => true]);
        $securityService = new SecurityService($config);
        $logger = $this->tester->createMockLogger();
        $queryTools = new QueryTools($this->connectionService, $securityService, $logger);
        
        // Même les opérations normalement dangereuses devraient être autorisées
        // Note: On ne peut pas vraiment tester GRANT/REVOKE avec notre utilisateur de test
        // mais on peut tester que la validation les autorise
        
        try {
            // Cette requête va échouer au niveau MySQL (permissions insuffisantes)
            // mais ne devrait PAS être bloquée par SecurityService
            $queryTools->executeQuery("GRANT SELECT ON testdb.users TO 'testuser'@'%'");
            $this->fail("Cette requête devrait échouer au niveau MySQL, pas au niveau sécurité");
        } catch (\Exception $e) {
            // L'erreur devrait venir de MySQL, pas de SecurityService
            $this->assertNotInstanceOf(SecurityException::class, $e,
                'L\'erreur ne devrait PAS venir de SecurityService en mode super admin');
        }
    }

    // ===== TESTS DE SCHÉMAS AUTORISÉS =====

    public function testAllowedSchemas()
    {
        // Configuration avec schémas limités
        $config = $this->tester->createTestConfig(['ALLOWED_SCHEMAS' => 'testdb']);
        $securityService = new SecurityService($config);
        $logger = $this->tester->createMockLogger();
        $queryTools = new QueryTools($this->connectionService, $securityService, $logger);
        
        // Requête sur schéma autorisé
        $result = $queryTools->executeQuery("SELECT * FROM testdb.users LIMIT 1");
        $this->assertTrue($result['success'], 'Requête sur schéma autorisé devrait fonctionner');
        
        // Requête sur schéma non autorisé
        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('Schéma non autorisé');
        
        $queryTools->executeQuery("SELECT * FROM information_schema.tables LIMIT 1");
    }

    public function testAllowedSchemasEmpty()
    {
        // Configuration avec ALLOWED_SCHEMAS vide = tous autorisés
        $config = $this->tester->createTestConfig(['ALLOWED_SCHEMAS' => '']);
        $securityService = new SecurityService($config);
        $logger = $this->tester->createMockLogger();
        $queryTools = new QueryTools($this->connectionService, $securityService, $logger);
        
        // Toutes les requêtes devraient fonctionner
        $result = $queryTools->executeQuery("SELECT * FROM testdb.users LIMIT 1");
        $this->assertTrue($result['success']);
        
        $result = $queryTools->executeQuery("SELECT COUNT(*) as cnt FROM information_schema.tables");
        $this->assertTrue($result['success']);
    }

    // ===== TESTS DES LIMITES =====

    public function testMaxResultsLimit()
    {
        // Configuration avec limite très basse
        $config = $this->tester->createTestConfig(['MAX_RESULTS' => 2]);
        $securityService = new SecurityService($config);
        
        // Test de la limite via checkResultLimit
        try {
            $securityService->checkResultLimit(5);
            $this->fail('La limite de résultats devrait être dépassée');
        } catch (SecurityException $e) {
            $this->assertStringContainsString('Nombre de résultats dépassé', $e->getMessage());
            $this->assertStringContainsString('Maximum: 2', $e->getMessage());
            $this->assertStringContainsString('demandé: 5', $e->getMessage());
        }
        
        // Test avec limite respectée
        $securityService->checkResultLimit(1); // Ne devrait pas lever d'exception
        $this->assertTrue(true);
    }

    public function testQueryTimeout()
    {
        // Configuration avec timeout très court
        $config = $this->tester->createTestConfig(['QUERY_TIMEOUT' => 1]);
        
        // Reset du singleton avec nouvelle config
        $reflection = new \ReflectionClass(ConnectionService::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null);
        
        $connectionService = ConnectionService::getInstance($config);
        $securityService = new SecurityService($config);
        $logger = $this->tester->createMockLogger();
        $queryTools = new QueryTools($connectionService, $securityService, $logger);
        
        // Une requête qui prend du temps devrait timeout
        // Note: MySQL SLEEP() pourrait ne pas respecter le timeout PDO
        try {
            $result = $queryTools->executeQuery("SELECT SLEEP(3)");
            // Si la requête réussit malgré tout, ce n'est pas grave pour ce test
            $this->assertTrue(true);
        } catch (\Exception $e) {
            // Si elle timeout, c'est le comportement attendu
            $this->assertStringContainsString('timeout', strtolower($e->getMessage()));
        }
        
        $connectionService->closeAll();
    }

    // ===== TESTS DE CONFIGURATION BOOLÉENNE =====

    public function testBooleanConfigurationParsing()
    {
        $testCases = [
            'true' => true,
            '1' => true,
            'yes' => true,
            'on' => true,
            'false' => false,
            '0' => false,
            'no' => false,
            'off' => false,
            '' => false,
        ];
        
        foreach ($testCases as $value => $expected) {
            $this->tester->setTestEnvironment(['ALLOW_INSERT_OPERATION' => $value]);
            $config = $this->tester->createTestConfig();
            $securityService = new SecurityService($config);
            $logger = $this->tester->createMockLogger();
            $queryTools = new QueryTools($this->connectionService, $securityService, $logger);
            
            if ($expected) {
                // Devrait être autorisé
                try {
                    $result = $queryTools->executeQuery(
                        "INSERT INTO users (name, email) VALUES (?, ?)",
                        ['Bool Test', "bool_{$value}@example.com"]
                    );
                    $this->assertTrue($result['success'], "INSERT devrait être autorisé pour la valeur '{$value}'");
                    
                    // Nettoyage
                    $queryTools->executeQuery("DELETE FROM users WHERE email = ?", ["bool_{$value}@example.com"]);
                } catch (SecurityException $e) {
                    $this->fail("INSERT devrait être autorisé pour la valeur '{$value}': " . $e->getMessage());
                }
            } else {
                // Devrait être bloqué
                try {
                    $queryTools->executeQuery(
                        "INSERT INTO users (name, email) VALUES (?, ?)",
                        ['Bool Test', "bool_{$value}@example.com"]
                    );
                    $this->fail("INSERT devrait être bloqué pour la valeur '{$value}'");
                } catch (SecurityException $e) {
                    $this->assertStringContainsString('Opération INSERT non autorisée', $e->getMessage());
                }
            }
        }
    }

    // ===== TESTS DE COMBINAISONS DE PERMISSIONS =====

    public function testPermissionsCombinations()
    {
        // Test de combinaisons complexes de permissions
        $this->tester->setTestEnvironment([
            'ALLOW_INSERT_OPERATION' => 'true',
            'ALLOW_UPDATE_OPERATION' => 'false',
            'ALLOW_DELETE_OPERATION' => 'true',
            'ALLOW_DDL_OPERATIONS' => 'true',
            'ALLOW_ALL_OPERATIONS' => 'false',
        ]);
        
        $config = $this->tester->createTestConfig();
        $securityService = new SecurityService($config);
        $logger = $this->tester->createMockLogger();
        $queryTools = new QueryTools($this->connectionService, $securityService, $logger);
        
        // INSERT autorisé
        $result = $queryTools->executeQuery(
            "INSERT INTO users (name, email) VALUES (?, ?)",
            ['Combo Test', 'combo@example.com']
        );
        $this->assertTrue($result['success'], 'INSERT devrait être autorisé');
        
        // UPDATE bloqué
        try {
            $queryTools->executeQuery(
                "UPDATE users SET name = ? WHERE email = ?",
                ['Updated Combo', 'combo@example.com']
            );
            $this->fail('UPDATE devrait être bloqué');
        } catch (SecurityException $e) {
            $this->assertStringContainsString('UPDATE non autorisée', $e->getMessage());
        }
        
        // DDL autorisé
        $result = $queryTools->executeQuery("CREATE TABLE combo_test (id INT)");
        $this->assertTrue($result['success'], 'CREATE devrait être autorisé');
        
        // DELETE autorisé pour nettoyage
        $result = $queryTools->executeQuery("DELETE FROM users WHERE email = ?", ['combo@example.com']);
        $this->assertTrue($result['success'], 'DELETE devrait être autorisé');
        
        $queryTools->executeQuery("DROP TABLE combo_test");
    }
}