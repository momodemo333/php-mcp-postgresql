<?php

declare(strict_types=1);

namespace MySqlMcp\Tests\Integration;

use MySqlMcp\Services\ConnectionService;
use MySqlMcp\Exceptions\ConnectionException;
use Codeception\Test\Unit;
use Tests\Support\IntegrationTester;

/**
 * Tests d'intégration pour ConnectionService avec vraie base MySQL
 */
class ConnectionServiceIntegrationTest extends Unit
{
    protected IntegrationTester $tester;
    private ConnectionService $service;

    protected function _before()
    {
        // Configuration des variables d'environnement pour les tests
        $this->tester->setTestEnvironment();
        
        $config = $this->tester->createTestConfig();
        
        // Reset du singleton
        $reflection = new \ReflectionClass(ConnectionService::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null);
        
        $this->service = ConnectionService::getInstance($config);
    }

    protected function _after()
    {
        $this->service->closeAll();
        $this->tester->cleanEnvironment();
    }

    // ===== TESTS DE CONNEXION RÉELLE =====

    public function testRealConnection()
    {
        $pdo = $this->service->getConnection();
        
        $this->assertInstanceOf(\PDO::class, $pdo);
        
        // Test d'une requête simple
        $stmt = $pdo->query('SELECT 1 as test');
        $result = $stmt->fetch();
        
        $this->assertEquals(1, $result['test']);
        
        $this->service->releaseConnection($pdo);
    }

    public function testConnectionTest()
    {
        $result = $this->service->testConnection();
        
        $this->assertTrue($result, 'Le test de connexion devrait réussir');
    }

    public function testServerInfo()
    {
        $info = $this->service->getServerInfo();
        
        $this->assertIsArray($info);
        $this->assertArrayHasKey('mysql_version', $info);
        $this->assertArrayHasKey('uptime_seconds', $info);
        $this->assertArrayHasKey('connection_pool_size', $info);
        $this->assertArrayHasKey('active_connections', $info);
        $this->assertArrayHasKey('total_connections', $info);
        
        $this->assertIsString($info['mysql_version']);
        $this->assertIsInt($info['uptime_seconds']);
        $this->assertGreaterThan(0, $info['uptime_seconds']);
    }

    // ===== TESTS DU POOL DE CONNEXIONS =====

    public function testConnectionPool()
    {
        $connections = [];
        
        // Récupération de 3 connexions
        for ($i = 0; $i < 3; $i++) {
            $connections[] = $this->service->getConnection();
        }
        
        $this->assertCount(3, $connections);
        
        // Vérification que ce sont bien des instances PDO différentes
        $this->assertNotSame($connections[0], $connections[1]);
        $this->assertNotSame($connections[1], $connections[2]);
        
        // Libération des connexions
        foreach ($connections as $conn) {
            $this->service->releaseConnection($conn);
        }
        
        // Récupération d'une nouvelle connexion (devrait réutiliser une existante)
        $newConnection = $this->service->getConnection();
        $this->assertInstanceOf(\PDO::class, $newConnection);
        
        $this->service->releaseConnection($newConnection);
    }

    public function testConnectionPoolLimit()
    {
        // Utilise une configuration avec pool limité
        $this->tester->cleanEnvironment();
        $config = $this->tester->createTestConfig(['CONNECTION_POOL_SIZE' => 2]);
        
        // Reset du singleton
        $reflection = new \ReflectionClass(ConnectionService::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null);
        
        $service = ConnectionService::getInstance($config);
        
        $connections = [];
        
        // Récupération des connexions jusqu'à la limite
        $connections[] = $service->getConnection();
        $connections[] = $service->getConnection();
        
        // La troisième devrait échouer
        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Pool de connexions saturé. Maximum: 2');
        
        $service->getConnection();
    }

    // ===== TESTS DE REQUÊTES RÉELLES =====

    public function testBasicQuery()
    {
        $pdo = $this->service->getConnection();
        
        // Test avec la table users créée par le fixture
        $stmt = $pdo->query('SELECT COUNT(*) as count FROM users');
        $result = $stmt->fetch();
        
        $this->assertGreaterThan(0, $result['count'], 'La table users devrait contenir des données');
        
        $this->service->releaseConnection($pdo);
    }

    public function testPreparedStatement()
    {
        $pdo = $this->service->getConnection();
        
        // Test d'une requête préparée
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([1]);
        $result = $stmt->fetch();
        
        $this->assertNotEmpty($result);
        $this->assertEquals(1, $result['id']);
        
        $this->service->releaseConnection($pdo);
    }

    public function testTransaction()
    {
        $pdo = $this->service->getConnection();
        
        try {
            $pdo->beginTransaction();
            
            // Insert de test
            $stmt = $pdo->prepare('INSERT INTO users (name, email) VALUES (?, ?)');
            $stmt->execute(['Test Transaction', 'transaction@test.com']);
            
            // Vérification que l'insert existe dans la transaction
            $stmt = $pdo->query('SELECT COUNT(*) as count FROM users WHERE email = "transaction@test.com"');
            $result = $stmt->fetch();
            $this->assertEquals(1, $result['count']);
            
            $pdo->rollback();
            
            // Vérification que l'insert a été annulé
            $stmt = $pdo->query('SELECT COUNT(*) as count FROM users WHERE email = "transaction@test.com"');
            $result = $stmt->fetch();
            $this->assertEquals(0, $result['count']);
            
        } finally {
            $this->service->releaseConnection($pdo);
        }
    }

    // ===== TESTS D'ERREURS DE CONNEXION =====

    public function testInvalidCredentials()
    {
        $this->tester->cleanEnvironment();
        $config = $this->tester->createTestConfig([
            'MYSQL_USER' => 'invalid_user',
            'MYSQL_PASS' => 'invalid_password'
        ]);
        
        // Reset du singleton
        $reflection = new \ReflectionClass(ConnectionService::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null);
        
        $service = ConnectionService::getInstance($config);
        
        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Impossible de se connecter à MySQL');
        
        $service->getConnection();
    }

    public function testInvalidHost()
    {
        $this->tester->cleanEnvironment();
        $config = $this->tester->createTestConfig([
            'MYSQL_HOST' => 'invalid-host-that-does-not-exist.local'
        ]);
        
        // Reset du singleton
        $reflection = new \ReflectionClass(ConnectionService::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null);
        
        $service = ConnectionService::getInstance($config);
        
        $this->expectException(ConnectionException::class);
        
        $service->getConnection();
    }

    // ===== TESTS DE CARACTÈRES SPÉCIAUX =====

    public function testUTF8Support()
    {
        $pdo = $this->service->getConnection();
        
        // Test d'insertion avec caractères UTF-8
        $stmt = $pdo->prepare('INSERT INTO users (name, email) VALUES (?, ?)');
        $stmt->execute(['Utilisateur ñoël 中文', 'utf8@test.com']);
        
        // Récupération et vérification
        $stmt = $pdo->prepare('SELECT name FROM users WHERE email = ?');
        $stmt->execute(['utf8@test.com']);
        $result = $stmt->fetch();
        
        $this->assertEquals('Utilisateur ñoël 中文', $result['name']);
        
        // Nettoyage
        $stmt = $pdo->prepare('DELETE FROM users WHERE email = ?');
        $stmt->execute(['utf8@test.com']);
        
        $this->service->releaseConnection($pdo);
    }

    // ===== TESTS DE PERFORMANCE =====

    public function testConnectionReuse()
    {
        $startTime = microtime(true);
        
        // Première connexion (création)
        $conn1 = $this->service->getConnection();
        $firstConnectionTime = microtime(true) - $startTime;
        $this->service->releaseConnection($conn1);
        
        $startTime = microtime(true);
        
        // Deuxième connexion (réutilisation)
        $conn2 = $this->service->getConnection();
        $secondConnectionTime = microtime(true) - $startTime;
        $this->service->releaseConnection($conn2);
        
        // La réutilisation devrait être plus rapide
        $this->assertLessThan($firstConnectionTime, $secondConnectionTime * 2, 
            'La réutilisation de connexion devrait être plus rapide que la création');
    }

    // ===== TESTS DE NETTOYAGE =====

    public function testCleanupWithActiveConnections()
    {
        $conn1 = $this->service->getConnection();
        $conn2 = $this->service->getConnection();
        
        // Une connexion active, une libérée
        $this->service->releaseConnection($conn2);
        
        // Le nettoyage ne devrait pas affecter les connexions récentes
        $this->service->cleanup();
        
        // La connexion active devrait toujours fonctionner
        $stmt = $conn1->query('SELECT 1');
        $result = $stmt->fetch();
        $this->assertEquals(1, $result[1]);
        
        $this->service->releaseConnection($conn1);
    }
}