<?php

declare(strict_types=1);

namespace MySqlMcp\Tests\Integration;

use MySqlMcp\Elements\DatabaseTools;
use MySqlMcp\Services\ConnectionService;
use MySqlMcp\Services\SecurityService;
use MySqlMcp\Exceptions\SecurityException;
use Codeception\Test\Unit;
use Tests\Support\IntegrationTester;

/**
 * Tests d'intégration pour DatabaseTools avec vraie base MySQL
 */
class DatabaseToolsIntegrationTest extends Unit
{
    protected IntegrationTester $tester;
    private DatabaseTools $databaseTools;
    private ConnectionService $connectionService;
    private SecurityService $securityService;

    protected function _before()
    {
        // Configuration des variables d'environnement pour les tests
        $this->tester->setTestEnvironment();
        
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
        
        $this->databaseTools = new DatabaseTools(
            $this->connectionService,
            $this->securityService,
            $logger
        );
    }

    protected function _after()
    {
        $this->connectionService->closeAll();
        $this->tester->cleanEnvironment();
    }

    // ===== TESTS DE LISTAGE DES BASES =====

    public function testListDatabases()
    {
        $databases = $this->databaseTools->listDatabases();
        
        $this->assertIsArray($databases);
        $this->assertContains('testdb', $databases, 'La base testdb devrait être listée');
        
        // Les bases système ne devraient pas être incluses par défaut
        $this->assertNotContains('information_schema', $databases);
        $this->assertNotContains('mysql', $databases);
        $this->assertNotContains('performance_schema', $databases);
        $this->assertNotContains('sys', $databases);
    }

    // ===== TESTS DE LISTAGE DES TABLES =====

    public function testListTables()
    {
        $tables = $this->databaseTools->listTables('testdb');
        
        $this->assertIsArray($tables);
        $this->assertNotEmpty($tables);
        
        // Tables créées par le fixture
        $expectedTables = ['users', 'posts', 'sensitive_data', 'test_ddl'];
        
        foreach ($expectedTables as $expectedTable) {
            $this->assertContains($expectedTable, array_column($tables, 'table_name'),
                "La table {$expectedTable} devrait être listée");
        }
        
        // Vérification de la structure des données retournées
        foreach ($tables as $table) {
            $this->assertArrayHasKey('table_name', $table);
            $this->assertArrayHasKey('table_type', $table);
        }
    }

    public function testListTablesNonExistentDatabase()
    {
        $this->expectException(\Exception::class);
        
        $this->databaseTools->listTables('non_existent_database');
    }

    // ===== TESTS DE DESCRIPTION DES TABLES =====

    public function testDescribeTable()
    {
        $description = $this->databaseTools->describeTable('testdb', 'users');
        
        $this->assertIsArray($description);
        $this->assertNotEmpty($description);
        
        // Vérification des colonnes attendues
        $columnNames = array_column($description, 'Field');
        $this->assertContains('id', $columnNames);
        $this->assertContains('name', $columnNames);
        $this->assertContains('email', $columnNames);
        $this->assertContains('created_at', $columnNames);
        $this->assertContains('updated_at', $columnNames);
        
        // Vérification de la structure des données
        foreach ($description as $column) {
            $this->assertArrayHasKey('Field', $column);
            $this->assertArrayHasKey('Type', $column);
            $this->assertArrayHasKey('Null', $column);
            $this->assertArrayHasKey('Key', $column);
        }
    }

    public function testDescribeTableNonExistent()
    {
        $this->expectException(\Exception::class);
        
        $this->databaseTools->describeTable('testdb', 'non_existent_table');
    }

    // ===== TESTS D'INDEX =====

    public function testListIndexes()
    {
        $indexes = $this->databaseTools->listIndexes('testdb', 'users');
        
        $this->assertIsArray($indexes);
        $this->assertNotEmpty($indexes);
        
        // Devrait contenir au moins la clé primaire
        $indexNames = array_column($indexes, 'Key_name');
        $this->assertContains('PRIMARY', $indexNames);
        
        // Vérification de la structure
        foreach ($indexes as $index) {
            $this->assertArrayHasKey('Table', $index);
            $this->assertArrayHasKey('Key_name', $index);
            $this->assertArrayHasKey('Column_name', $index);
        }
    }

    // ===== TESTS DE CONTRAINTES =====

    public function testListForeignKeys()
    {
        $foreignKeys = $this->databaseTools->listForeignKeys('testdb', 'posts');
        
        $this->assertIsArray($foreignKeys);
        
        if (!empty($foreignKeys)) {
            // Vérification de la contrainte posts -> users
            $userConstraint = array_filter($foreignKeys, 
                fn($fk) => $fk['COLUMN_NAME'] === 'user_id'
            );
            
            $this->assertNotEmpty($userConstraint, 'La contrainte user_id devrait exister');
        }
    }

    // ===== TESTS AVEC PERMISSIONS RESTREINTES =====

    public function testListDatabasesWithRestrictedPermissions()
    {
        // Test avec permissions limitées
        $this->tester->cleanEnvironment();
        $config = $this->tester->createTestConfig([
            'ALLOWED_SCHEMAS' => 'testdb'
        ]);
        
        $securityService = new SecurityService($config);
        $logger = $this->tester->createMockLogger();
        
        $databaseTools = new DatabaseTools(
            $this->connectionService,
            $securityService,
            $logger
        );
        
        // Ne devrait pas lever d'exception
        $databases = $databaseTools->listDatabases();
        $this->assertIsArray($databases);
    }

    // ===== TESTS D'ERREURS ET EDGE CASES =====

    public function testListTablesEmptyDatabase()
    {
        // Créer une base vide pour le test
        $pdo = $this->connectionService->getConnection();
        $pdo->exec('CREATE DATABASE IF NOT EXISTS empty_test_db');
        
        try {
            $tables = $this->databaseTools->listTables('empty_test_db');
            $this->assertIsArray($tables);
            $this->assertEmpty($tables, 'Une base vide ne devrait pas contenir de tables');
        } finally {
            // Nettoyage
            $pdo->exec('DROP DATABASE IF EXISTS empty_test_db');
            $this->connectionService->releaseConnection($pdo);
        }
    }

    public function testDescribeTableWithSpecialCharacters()
    {
        // Test avec une table qui a des caractères spéciaux dans les noms de colonnes
        $pdo = $this->connectionService->getConnection();
        
        try {
            $pdo->exec('CREATE TABLE testdb.special_chars_table (
                `special-column` INT,
                `column with spaces` VARCHAR(100),
                `åçcént_çølümñ` TEXT
            )');
            
            $description = $this->databaseTools->describeTable('testdb', 'special_chars_table');
            
            $this->assertIsArray($description);
            $this->assertCount(3, $description);
            
            $columnNames = array_column($description, 'Field');
            $this->assertContains('special-column', $columnNames);
            $this->assertContains('column with spaces', $columnNames);
            $this->assertContains('åçcént_çølümñ', $columnNames);
            
        } finally {
            // Nettoyage
            $pdo->exec('DROP TABLE IF EXISTS testdb.special_chars_table');
            $this->connectionService->releaseConnection($pdo);
        }
    }

    // ===== TESTS DE PERFORMANCE =====

    public function testListTablesPerformance()
    {
        $startTime = microtime(true);
        
        $tables = $this->databaseTools->listTables('testdb');
        
        $executionTime = microtime(true) - $startTime;
        
        $this->assertLessThan(2.0, $executionTime, 
            'Le listage des tables ne devrait pas prendre plus de 2 secondes');
        $this->assertNotEmpty($tables);
    }

    public function testDescribeTablePerformance()
    {
        $startTime = microtime(true);
        
        $description = $this->databaseTools->describeTable('testdb', 'users');
        
        $executionTime = microtime(true) - $startTime;
        
        $this->assertLessThan(1.0, $executionTime,
            'La description d\'une table ne devrait pas prendre plus de 1 seconde');
        $this->assertNotEmpty($description);
    }

    // ===== TESTS DE LOGGING =====

    public function testLoggingOnListDatabases()
    {
        // Utilise un logger pour capturer les logs
        $logger = $this->tester->createMockLogger();
        
        $databaseTools = new DatabaseTools(
            $this->connectionService,
            $this->securityService,
            $logger
        );
        
        $databaseTools->listDatabases();
        
        $logs = $logger->getLogs();
        $infoLogs = array_filter($logs, fn($log) => $log[0] === 'info');
        
        $this->assertNotEmpty($infoLogs, 'Un log info devrait être généré');
        
        $dbListedLog = array_filter($infoLogs, 
            fn($log) => str_contains($log[1], 'Bases de données listées')
        );
        $this->assertNotEmpty($dbListedLog, 'Le log de listage devrait être présent');
    }

    // ===== TESTS DE CONCURRENT ACCESS =====

    public function testConcurrentDatabaseAccess()
    {
        // Simulation d'accès concurrent via multiple connexions
        $conn1 = $this->connectionService->getConnection();
        $conn2 = $this->connectionService->getConnection();
        
        try {
            // Requêtes simultanées sur différentes connexions
            $stmt1 = $conn1->query('SELECT COUNT(*) as count FROM users');
            $stmt2 = $conn2->query('SELECT COUNT(*) as count FROM posts');
            
            $result1 = $stmt1->fetch();
            $result2 = $stmt2->fetch();
            
            $this->assertIsArray($result1);
            $this->assertIsArray($result2);
            $this->assertArrayHasKey('count', $result1);
            $this->assertArrayHasKey('count', $result2);
            
        } finally {
            $this->connectionService->releaseConnection($conn1);
            $this->connectionService->releaseConnection($conn2);
        }
    }
}