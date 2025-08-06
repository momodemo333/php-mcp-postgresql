<?php

declare(strict_types=1);

namespace MySqlMcp\Tests\Unit;

use MySqlMcp\Services\SecurityService;
use MySqlMcp\Exceptions\SecurityException;
use Codeception\Test\Unit;
use Tests\Support\UnitTester;

/**
 * Tests unitaires pour SecurityService
 */
class SecurityServiceTest extends Unit
{
    protected UnitTester $tester;
    private SecurityService $securityService;
    private \Psr\Log\LoggerInterface $mockLogger;

    protected function _before()
    {
        $this->mockLogger = $this->tester->createMockLogger();
    }

    // ===== TESTS DE CONFIGURATION =====

    public function testDefaultConfiguration()
    {
        $config = $this->tester->createMockConfig();
        $service = new SecurityService($config, $this->mockLogger);
        
        // Par défaut, les opérations DDL doivent être bloquées
        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('Mot-clé non autorisé détecté: ALTER');
        
        $service->validateQuery('ALTER TABLE test ADD COLUMN name VARCHAR(100)', 'ALTER');
    }

    public function testDDLOperationsAllowed()
    {
        $config = $this->tester->createMockConfig([
            'ALLOW_DDL_OPERATIONS' => true
        ]);
        $service = new SecurityService($config, $this->mockLogger);
        
        // Ne doit pas lever d'exception
        $service->validateQuery('ALTER TABLE test ADD COLUMN name VARCHAR(100)', 'ALTER');
        $service->validateQuery('CREATE TABLE new_table (id INT)', 'CREATE');
        $service->validateQuery('DROP TABLE old_table', 'DROP');
        
        $this->assertTrue(true); // Si on arrive ici, pas d'exception
    }

    public function testSuperAdminMode()
    {
        $config = $this->tester->createMockConfig([
            'ALLOW_ALL_OPERATIONS' => true
        ]);
        $service = new SecurityService($config, $this->mockLogger);
        
        // Même les mots-clés dangereux doivent être autorisés
        $service->validateQuery('GRANT SELECT ON test TO user', 'GRANT');
        $service->validateQuery('ALTER TABLE test ADD COLUMN name VARCHAR(100)', 'ALTER');
        
        $this->assertTrue(true);
    }

    // ===== TESTS DES MOTS-CLÉS DDL =====

    public function testDDLKeywordsBlocked()
    {
        $config = $this->tester->createMockConfig(['ALLOW_DDL_OPERATIONS' => false]);
        $service = new SecurityService($config, $this->mockLogger);
        
        $ddlQueries = [
            'CREATE TABLE test (id INT)',
            'ALTER TABLE test ADD COLUMN name VARCHAR(100)',
            'DROP TABLE test'
        ];
        
        foreach ($ddlQueries as $query) {
            $this->expectException(SecurityException::class);
            $service->validateQuery($query);
            break; // Premier test seulement car on ne peut avoir qu'une exception
        }
    }

    public function testDDLKeywordsAllowed()
    {
        $config = $this->tester->createMockConfig(['ALLOW_DDL_OPERATIONS' => true]);
        $service = new SecurityService($config, $this->mockLogger);
        
        $ddlQueries = [
            'CREATE TABLE test (id INT)',
            'ALTER TABLE test ADD COLUMN name VARCHAR(100)',
            'DROP TABLE test'
        ];
        
        foreach ($ddlQueries as $query) {
            $service->validateQuery($query);
        }
        
        $this->assertTrue(true);
    }

    // ===== TESTS DES MOTS-CLÉS DANGEREUX =====

    public function testDangerousKeywordsBlocked()
    {
        $config = $this->tester->createMockConfig([
            'ALLOW_DDL_OPERATIONS' => true, // DDL autorisé mais pas les opérations dangereuses
            'BLOCK_DANGEROUS_KEYWORDS' => true
        ]);
        $service = new SecurityService($config, $this->mockLogger);
        
        $dangerousQueries = [
            'GRANT SELECT ON test TO user',
            'LOAD_FILE("/etc/passwd")',
            'SELECT * INTO OUTFILE "/tmp/file"',
            'SYSTEM("ls")'
        ];
        
        foreach ($dangerousQueries as $query) {
            try {
                $service->validateQuery($query);
                $this->fail("La requête dangereuse aurait dû être bloquée: {$query}");
            } catch (SecurityException $e) {
                $this->assertStringContainsStringString('Mot-clé non autorisé détecté', $e->getMessage());
            }
        }
    }

    // ===== TESTS DES PERMISSIONS D'OPÉRATION =====

    public function testOperationPermissions()
    {
        $config = $this->tester->createMockConfig([
            'ALLOW_INSERT_OPERATION' => false,
            'ALLOW_UPDATE_OPERATION' => false,
            'ALLOW_DELETE_OPERATION' => false,
            'ALLOW_TRUNCATE_OPERATION' => false
        ]);
        $service = new SecurityService($config, $this->mockLogger);
        
        $operations = ['INSERT', 'UPDATE', 'DELETE', 'TRUNCATE'];
        
        foreach ($operations as $operation) {
            try {
                $service->validateQuery("SELECT 1", $operation);
                $this->fail("L'opération {$operation} aurait dû être bloquée");
            } catch (SecurityException $e) {
                $this->assertStringContainsString("Opération {$operation} non autorisée", $e->getMessage());
            }
        }
    }

    public function testOperationPermissionsWithSuperAdmin()
    {
        $config = $this->tester->createMockConfig([
            'ALLOW_ALL_OPERATIONS' => true,
            'ALLOW_INSERT_OPERATION' => false // Doit être ignoré en mode super admin
        ]);
        $service = new SecurityService($config, $this->mockLogger);
        
        // Toutes les opérations doivent être autorisées
        $service->validateQuery("SELECT 1", 'INSERT');
        $service->validateQuery("SELECT 1", 'UPDATE');
        $service->validateQuery("SELECT 1", 'DELETE');
        $service->validateQuery("SELECT 1", 'TRUNCATE');
        
        $this->assertTrue(true);
    }

    // ===== TESTS D'INJECTION SQL =====

    public function testSqlInjectionDetection()
    {
        $config = $this->tester->createMockConfig();
        $service = new SecurityService($config, $this->mockLogger);
        
        $injectionQueries = [
            "SELECT * FROM users WHERE id = 1 OR 1=1",
            "SELECT * FROM users WHERE name = 'admin' AND '1'='1'",
            "SELECT * FROM users UNION SELECT * FROM passwords",
            "SELECT * FROM users -- comment",
            "SELECT * FROM users; DROP TABLE users;"
        ];
        
        foreach ($injectionQueries as $query) {
            try {
                $service->validateQuery($query);
                $this->fail("L'injection SQL aurait dû être détectée: {$query}");
            } catch (SecurityException $e) {
                $this->assertStringContainsString('injection SQL', $e->getMessage());
            }
        }
    }

    // ===== TESTS DES SCHÉMAS AUTORISÉS =====

    public function testAllowedSchemas()
    {
        $config = $this->tester->createMockConfig([
            'ALLOWED_SCHEMAS' => 'allowed_db,another_db'
        ]);
        $service = new SecurityService($config, $this->mockLogger);
        
        // Schéma autorisé
        $service->validateQuery('SELECT * FROM allowed_db.users');
        
        // Schéma non autorisé
        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('Schéma non autorisé: forbidden_db');
        $service->validateQuery('SELECT * FROM forbidden_db.users');
    }

    // ===== TESTS DES LIMITES DE RÉSULTATS =====

    public function testResultLimit()
    {
        $config = $this->tester->createMockConfig(['MAX_RESULTS' => 100]);
        $service = new SecurityService($config, $this->mockLogger);
        
        // Limite respectée
        $service->checkResultLimit(50);
        
        // Limite dépassée
        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('Nombre de résultats dépassé. Maximum: 100, demandé: 150');
        $service->checkResultLimit(150);
    }

    // ===== TESTS DE SANITISATION =====

    public function testSanitizeForLog()
    {
        $config = $this->tester->createMockConfig();
        $service = new SecurityService($config, $this->mockLogger);
        
        $input = "password='secret123' AND pwd=\"another_secret\"";
        $sanitized = $service->sanitizeForLog($input);
        
        $this->assertStringNotContains('secret123', $sanitized);
        $this->assertStringNotContains('another_secret', $sanitized);
        $this->assertStringContainsString('password=***', $sanitized);
        $this->assertStringContainsString('pwd=***', $sanitized);
    }

    // ===== TESTS D'EDGE CASES =====

    public function testEmptyQuery()
    {
        $config = $this->tester->createMockConfig();
        $service = new SecurityService($config, $this->mockLogger);
        
        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('Requête vide non autorisée');
        $service->validateQuery('');
    }

    public function testWhitespaceOnlyQuery()
    {
        $config = $this->tester->createMockConfig();
        $service = new SecurityService($config, $this->mockLogger);
        
        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('Requête vide non autorisée');
        $service->validateQuery('   ');
    }

    public function testCaseInsensitiveKeywordDetection()
    {
        $config = $this->tester->createMockConfig(['ALLOW_DDL_OPERATIONS' => false]);
        $service = new SecurityService($config, $this->mockLogger);
        
        $queries = [
            'alter table test add column name varchar(100)',
            'ALTER TABLE test ADD COLUMN name VARCHAR(100)',
            'AlTeR tAbLe test ADD column name VARCHAR(100)'
        ];
        
        foreach ($queries as $query) {
            try {
                $service->validateQuery($query);
                $this->fail("Le mot-clé ALTER aurait dû être détecté (case insensitive): {$query}");
            } catch (SecurityException $e) {
                $this->assertStringContainsString('ALTER', $e->getMessage());
            }
        }
    }
}