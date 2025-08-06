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
 * Tests d'intégration pour QueryTools avec vraie base MySQL
 */
class QueryToolsIntegrationTest extends Unit
{
    protected IntegrationTester $tester;
    private QueryTools $queryTools;
    private ConnectionService $connectionService;
    private SecurityService $securityService;

    protected function _before()
    {
        // Configuration des variables d'environnement pour les tests
        $this->tester->setTestEnvironment([
            'ALLOW_INSERT_OPERATION' => 'true',
            'ALLOW_UPDATE_OPERATION' => 'true',
            'ALLOW_DELETE_OPERATION' => 'true',
            'ALLOW_TRUNCATE_OPERATION' => 'false',
            'ALLOW_DDL_OPERATIONS' => 'false',
            'ALLOW_ALL_OPERATIONS' => 'false',
        ]);
        
        $config = $this->tester->createTestConfig();
        
        // Reset du singleton ConnectionService
        $reflection = new \ReflectionClass(ConnectionService::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null);
        
        // Initialisation des services
        $this->connectionService = ConnectionService::getInstance($config);
        $this->securityService = new SecurityService($config);
        
        // Création du logger mock
        $logger = $this->tester->createMockLogger();
        
        $this->queryTools = new QueryTools(
            $this->connectionService,
            $this->securityService,
            $logger
        );
    }

    protected function _after()
    {
        // Nettoyage des données de test
        $this->cleanupTestData();
        $this->connectionService->closeAll();
        $this->tester->cleanEnvironment();
    }

    private function cleanupTestData(): void
    {
        try {
            $pdo = $this->connectionService->getConnection();
            $pdo->exec("DELETE FROM users WHERE email LIKE '%@integration-test.com'");
            $pdo->exec("DELETE FROM posts WHERE title LIKE 'Integration Test%'");
            $this->connectionService->releaseConnection($pdo);
        } catch (\Exception $e) {
            // Ignore cleanup errors
        }
    }

    // ===== TESTS DE REQUÊTES SELECT =====

    public function testExecuteSelectQuery()
    {
        $result = $this->queryTools->executeQuery('SELECT * FROM users LIMIT 3');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('row_count', $result);
        $this->assertArrayHasKey('execution_time_ms', $result);
        
        $this->assertIsArray($result['data']);
        $this->assertLessThanOrEqual(3, count($result['data']));
        $this->assertLessThanOrEqual(3, $result['row_count']);
    }

    public function testExecuteSelectWithParameters()
    {
        $result = $this->queryTools->executeQuery(
            'SELECT * FROM users WHERE id = ? AND name LIKE ?',
            [1, '%John%']
        );
        
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        
        if ($result['row_count'] > 0) {
            $this->assertEquals(1, $result['data'][0]['id']);
            $this->assertStringContainsString('John', $result['data'][0]['name']);
        }
    }

    public function testExecuteComplexQuery()
    {
        // Test avec JOIN
        $query = 'SELECT u.name, COUNT(p.id) as post_count 
                  FROM users u 
                  LEFT JOIN posts p ON u.id = p.user_id 
                  GROUP BY u.id, u.name 
                  ORDER BY post_count DESC';
        
        $result = $this->queryTools->executeQuery($query);
        
        $this->assertTrue($result['success']);
        $this->assertIsArray($result['data']);
        
        if (!empty($result['data'])) {
            foreach ($result['data'] as $row) {
                $this->assertArrayHasKey('name', $row);
                $this->assertArrayHasKey('post_count', $row);
                $this->assertIsString($row['name']);
                $this->assertIsNumeric($row['post_count']);
            }
        }
    }

    // ===== TESTS DE REQUÊTES INSERT =====

    public function testExecuteInsertQuery()
    {
        $query = "INSERT INTO users (name, email) VALUES (?, ?)";
        $params = ['Integration Test User', 'integration@integration-test.com'];
        
        $result = $this->queryTools->executeQuery($query, $params);
        
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['affected_rows']);
        $this->assertArrayHasKey('insert_id', $result);
        $this->assertGreaterThan(0, $result['insert_id']);
        
        // Vérification que l'insertion a bien eu lieu
        $selectResult = $this->queryTools->executeQuery(
            "SELECT * FROM users WHERE email = ?",
            ['integration@integration-test.com']
        );
        
        $this->assertTrue($selectResult['success']);
        $this->assertEquals(1, $selectResult['row_count']);
        $this->assertEquals('Integration Test User', $selectResult['data'][0]['name']);
    }

    // ===== TESTS DE REQUÊTES UPDATE =====

    public function testExecuteUpdateQuery()
    {
        // D'abord insérer un utilisateur de test
        $this->queryTools->executeQuery(
            "INSERT INTO users (name, email) VALUES (?, ?)",
            ['Update Test User', 'update@integration-test.com']
        );
        
        // Puis le mettre à jour
        $result = $this->queryTools->executeQuery(
            "UPDATE users SET name = ? WHERE email = ?",
            ['Updated Test User', 'update@integration-test.com']
        );
        
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['affected_rows']);
        
        // Vérification de la mise à jour
        $selectResult = $this->queryTools->executeQuery(
            "SELECT name FROM users WHERE email = ?",
            ['update@integration-test.com']
        );
        
        $this->assertEquals('Updated Test User', $selectResult['data'][0]['name']);
    }

    // ===== TESTS DE REQUÊTES DELETE =====

    public function testExecuteDeleteQuery()
    {
        // Insérer un utilisateur à supprimer
        $this->queryTools->executeQuery(
            "INSERT INTO users (name, email) VALUES (?, ?)",
            ['Delete Test User', 'delete@integration-test.com']
        );
        
        // Supprimer l'utilisateur
        $result = $this->queryTools->executeQuery(
            "DELETE FROM users WHERE email = ?",
            ['delete@integration-test.com']
        );
        
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['affected_rows']);
        
        // Vérifier que la suppression a eu lieu
        $selectResult = $this->queryTools->executeQuery(
            "SELECT * FROM users WHERE email = ?",
            ['delete@integration-test.com']
        );
        
        $this->assertEquals(0, $selectResult['row_count']);
    }

    // ===== TESTS DE PERMISSIONS ET SÉCURITÉ =====

    public function testTruncateQueryBlocked()
    {
        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('TRUNCATE');
        
        $this->queryTools->executeQuery("TRUNCATE TABLE test_ddl");
    }

    public function testDDLQueryBlocked()
    {
        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('ALTER');
        
        $this->queryTools->executeQuery("ALTER TABLE test_ddl ADD COLUMN new_col VARCHAR(50)");
    }

    public function testDDLQueryWithPermissions()
    {
        // Reconfiguration avec permissions DDL
        $this->tester->cleanEnvironment();
        $this->tester->setTestEnvironment([
            'ALLOW_DDL_OPERATIONS' => 'true'
        ]);
        
        $config = $this->tester->createTestConfig();
        $securityService = new SecurityService($config);
        $logger = $this->tester->createMockLogger();
        
        $queryTools = new QueryTools(
            $this->connectionService,
            $securityService,
            $logger
        );
        
        // Maintenant l'ALTER devrait fonctionner
        $result = $queryTools->executeQuery("ALTER TABLE test_ddl ADD COLUMN integration_test_col VARCHAR(50)");
        
        $this->assertTrue($result['success']);
        
        // Nettoyage
        $queryTools->executeQuery("ALTER TABLE test_ddl DROP COLUMN integration_test_col");
    }

    public function testSqlInjectionBlocked()
    {
        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('injection SQL');
        
        $this->queryTools->executeQuery("SELECT * FROM users WHERE id = 1 OR 1=1");
    }

    public function testDangerousKeywordBlocked()
    {
        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('LOAD_FILE');
        
        $this->queryTools->executeQuery("SELECT LOAD_FILE('/etc/passwd')");
    }

    // ===== TESTS DE LIMITATIONS =====

    public function testResultLimit()
    {
        // Insérer plusieurs enregistrements pour tester la limite
        for ($i = 0; $i < 5; $i++) {
            $this->queryTools->executeQuery(
                "INSERT INTO users (name, email) VALUES (?, ?)",
                ["Limit Test User {$i}", "limit{$i}@integration-test.com"]
            );
        }
        
        // Requête qui devrait retourner tous les enregistrements
        $result = $this->queryTools->executeQuery(
            "SELECT * FROM users WHERE email LIKE '%@integration-test.com'"
        );
        
        $this->assertTrue($result['success']);
        
        // La limite MAX_RESULTS (1000 par défaut) ne devrait pas être atteinte
        $this->assertLessThanOrEqual(1000, $result['row_count']);
    }

    // ===== TESTS DE TRANSACTIONS =====

    public function testTransactionWithCommit()
    {
        $pdo = $this->connectionService->getConnection();
        
        try {
            $pdo->beginTransaction();
            
            $result1 = $this->queryTools->executeQuery(
                "INSERT INTO users (name, email) VALUES (?, ?)",
                ['Transaction User 1', 'trans1@integration-test.com']
            );
            
            $result2 = $this->queryTools->executeQuery(
                "INSERT INTO users (name, email) VALUES (?, ?)",
                ['Transaction User 2', 'trans2@integration-test.com']
            );
            
            $this->assertTrue($result1['success']);
            $this->assertTrue($result2['success']);
            
            $pdo->commit();
            
            // Vérifier que les données sont persistées
            $checkResult = $this->queryTools->executeQuery(
                "SELECT COUNT(*) as count FROM users WHERE email LIKE 'trans%@integration-test.com'"
            );
            
            $this->assertEquals(2, $checkResult['data'][0]['count']);
            
        } finally {
            $this->connectionService->releaseConnection($pdo);
        }
    }

    // ===== TESTS DE PERFORMANCE =====

    public function testQueryPerformance()
    {
        $startTime = microtime(true);
        
        $result = $this->queryTools->executeQuery('SELECT * FROM users LIMIT 10');
        
        $totalTime = microtime(true) - $startTime;
        
        $this->assertTrue($result['success']);
        $this->assertLessThan(1.0, $totalTime, 'La requête simple ne devrait pas prendre plus de 1 seconde');
        $this->assertArrayHasKey('execution_time_ms', $result);
        $this->assertIsFloat($result['execution_time_ms']);
        $this->assertGreaterThan(0, $result['execution_time_ms']);
    }

    // ===== TESTS D'ERREURS =====

    public function testInvalidSyntaxQuery()
    {
        $result = $this->queryTools->executeQuery("INVALID SQL SYNTAX HERE");
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('syntax', strtolower($result['error']));
    }

    public function testQueryOnNonExistentTable()
    {
        $result = $this->queryTools->executeQuery("SELECT * FROM non_existent_table");
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    // ===== TESTS DE TYPES DE DONNÉES =====

    public function testDifferentDataTypes()
    {
        // Test avec différents types de données MySQL
        $result = $this->queryTools->executeQuery("
            SELECT 
                1 as integer_val,
                1.5 as decimal_val,
                'string' as string_val,
                NOW() as datetime_val,
                TRUE as boolean_val,
                NULL as null_val
        ");
        
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['row_count']);
        
        $row = $result['data'][0];
        $this->assertEquals(1, $row['integer_val']);
        $this->assertEquals('1.5', $row['decimal_val']); // MySQL retourne les décimaux comme strings
        $this->assertEquals('string', $row['string_val']);
        $this->assertNotNull($row['datetime_val']);
        $this->assertEquals(1, $row['boolean_val']); // MySQL retourne TRUE comme 1
        $this->assertNull($row['null_val']);
    }

    // ===== TESTS UTF-8 =====

    public function testUTF8DataHandling()
    {
        $specialText = "Texte avec émojis 🚀🎉 et caractères spéciaux: àèéôü ñ 中文";
        
        $result = $this->queryTools->executeQuery(
            "INSERT INTO users (name, email) VALUES (?, ?)",
            [$specialText, 'utf8@integration-test.com']
        );
        
        $this->assertTrue($result['success']);
        
        $selectResult = $this->queryTools->executeQuery(
            "SELECT name FROM users WHERE email = ?",
            ['utf8@integration-test.com']
        );
        
        $this->assertTrue($selectResult['success']);
        $this->assertEquals($specialText, $selectResult['data'][0]['name']);
    }

    // ===== TESTS DE REQUÊTES PERSONNALISÉES =====

    public function testExecuteCustomQuery()
    {
        // Test de executeCustomQuery si elle existe
        if (method_exists($this->queryTools, 'executeCustomQuery')) {
            $result = $this->queryTools->executeCustomQuery('SELECT VERSION() as mysql_version');
            
            $this->assertIsArray($result);
            $this->assertTrue($result['success']);
            $this->assertArrayHasKey('data', $result);
            $this->assertNotEmpty($result['data'][0]['mysql_version']);
        } else {
            $this->markTestSkipped('La méthode executeCustomQuery n\'existe pas');
        }
    }
}