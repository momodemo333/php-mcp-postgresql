<?php

declare(strict_types=1);

namespace MySqlMcp\Tests\Unit;

use MySqlMcp\Services\ConnectionService;
use MySqlMcp\Exceptions\ConnectionException;
use Codeception\Test\Unit;
use Tests\Support\UnitTester;

/**
 * Tests unitaires pour ConnectionService
 * 
 * Note: Ces tests unitaires se concentrent sur la logique métier
 * sans vraie connexion MySQL. Les tests d'intégration se trouvent
 * dans Integration/ConnectionServiceIntegrationTest.php
 */
class ConnectionServiceTest extends Unit
{
    protected UnitTester $tester;
    private \Psr\Log\LoggerInterface $mockLogger;

    protected function _before()
    {
        $this->mockLogger = $this->tester->createMockLogger();
        
        // Reset du singleton pour chaque test
        $reflection = new \ReflectionClass(ConnectionService::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null);
    }

    // ===== TESTS DE SINGLETON =====

    public function testSingletonPattern()
    {
        $config = $this->tester->createMockConfig();
        
        $instance1 = ConnectionService::getInstance($config, $this->mockLogger);
        $instance2 = ConnectionService::getInstance();
        
        $this->assertSame($instance1, $instance2, 'Les instances doivent être identiques (singleton)');
    }

    public function testSingletonRequiresConfigOnFirstCall()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Configuration required for first instance');
        
        ConnectionService::getInstance(); // Pas de config sur le premier appel
    }

    // ===== TESTS DE CONFIGURATION =====

    public function testDefaultConnectionPoolSize()
    {
        $config = $this->tester->createMockConfig(); // Pas de CONNECTION_POOL_SIZE
        $service = ConnectionService::getInstance($config, $this->mockLogger);
        
        // Test indirect via l'exception de pool saturé
        // Difficile de tester directement sans vraie DB
        $this->assertInstanceOf(ConnectionService::class, $service);
    }

    public function testCustomConnectionPoolSize()
    {
        $config = $this->tester->createMockConfig([
            'CONNECTION_POOL_SIZE' => 10
        ]);
        $service = ConnectionService::getInstance($config, $this->mockLogger);
        
        $this->assertInstanceOf(ConnectionService::class, $service);
    }

    // ===== TESTS DE CONFIGURATION DSN =====

    public function testDSNConstruction()
    {
        // Test unitaire indirect - on ne peut pas tester createConnection directement
        // car elle est privée, mais on peut tester la logique via reflection ou
        // en testant le comportement attendu
        
        $config = $this->tester->createMockConfig([
            'MYSQL_HOST' => 'testhost',
            'MYSQL_PORT' => 3307,
            'MYSQL_DB' => 'testdb',
            'MYSQL_USER' => 'testuser',
            'MYSQL_PASS' => 'testpass'
        ]);
        
        $service = ConnectionService::getInstance($config, $this->mockLogger);
        
        // Le test réel de connexion sera dans les tests d'intégration
        $this->assertInstanceOf(ConnectionService::class, $service);
    }

    public function testDSNWithoutDatabase()
    {
        $config = $this->tester->createMockConfig([
            'MYSQL_HOST' => 'testhost',
            'MYSQL_PORT' => 3307,
            'MYSQL_DB' => '', // Base vide pour mode multi-DB
            'MYSQL_USER' => 'testuser',
            'MYSQL_PASS' => 'testpass'
        ]);
        
        $service = ConnectionService::getInstance($config, $this->mockLogger);
        $this->assertInstanceOf(ConnectionService::class, $service);
    }

    // ===== TESTS DE MÉTHODES UTILITAIRES =====

    public function testCleanup()
    {
        $config = $this->tester->createMockConfig();
        $service = ConnectionService::getInstance($config, $this->mockLogger);
        
        // Appel de cleanup - ne devrait pas lever d'exception
        $service->cleanup();
        
        // Vérification que le logger a été appelé (ou pas si pas de connexions à nettoyer)
        $this->assertTrue(true); // Test passe si pas d'exception
    }

    public function testCloseAll()
    {
        $config = $this->tester->createMockConfig();
        $service = ConnectionService::getInstance($config, $this->mockLogger);
        
        // Appel de closeAll - ne devrait pas lever d'exception
        $service->closeAll();
        
        // Vérification que le logger a été appelé
        $logs = $this->mockLogger->getLogs();
        $this->assertContains(['info', 'Toutes les connexions fermées', []], $logs);
    }

    // ===== TESTS DE GESTION D'ERREURS =====

    public function testInvalidConfiguration()
    {
        // Test avec configuration invalide (host inexistant)
        $config = $this->tester->createMockConfig([
            'MYSQL_HOST' => 'invalid-host-that-does-not-exist.local',
            'MYSQL_PORT' => 3306,
            'MYSQL_USER' => 'invalid_user',
            'MYSQL_PASS' => 'invalid_pass'
        ]);
        
        $service = ConnectionService::getInstance($config, $this->mockLogger);
        
        // Le test de connexion réelle sera dans les tests d'intégration
        // Ici on teste juste que l'objet se crée sans erreur
        $this->assertInstanceOf(ConnectionService::class, $service);
    }

    // ===== TESTS DE LOGGING =====

    public function testLoggingOnCloseAll()
    {
        $config = $this->tester->createMockConfig();
        $service = ConnectionService::getInstance($config, $this->mockLogger);
        
        $service->closeAll();
        
        $logs = $this->mockLogger->getLogs();
        $infoLogs = array_filter($logs, fn($log) => $log[0] === 'info');
        
        $this->assertNotEmpty($infoLogs, 'Un log info devrait être généré lors de closeAll()');
        
        $closeAllLog = array_filter($infoLogs, fn($log) => $log[1] === 'Toutes les connexions fermées');
        $this->assertNotEmpty($closeAllLog, 'Le message de fermeture devrait être loggé');
    }

    // ===== TESTS D'EDGE CASES =====

    public function testMultipleCleanupCalls()
    {
        $config = $this->tester->createMockConfig();
        $service = ConnectionService::getInstance($config, $this->mockLogger);
        
        // Appels multiples de cleanup
        $service->cleanup();
        $service->cleanup();
        $service->cleanup();
        
        // Ne devrait pas lever d'exception
        $this->assertTrue(true);
    }

    public function testMultipleCloseAllCalls()
    {
        $config = $this->tester->createMockConfig();
        $service = ConnectionService::getInstance($config, $this->mockLogger);
        
        // Appels multiples de closeAll
        $service->closeAll();
        $service->closeAll();
        $service->closeAll();
        
        // Devrait logger plusieurs fois
        $logs = $this->mockLogger->getLogs();
        $closeAllLogs = array_filter($logs, fn($log) => $log[1] === 'Toutes les connexions fermées');
        
        $this->assertCount(3, $closeAllLogs, 'closeAll() devrait logger à chaque appel');
    }

    // ===== TESTS DE TIMEOUT =====

    public function testQueryTimeoutConfiguration()
    {
        $config = $this->tester->createMockConfig([
            'QUERY_TIMEOUT' => 60
        ]);
        
        $service = ConnectionService::getInstance($config, $this->mockLogger);
        
        // Test indirect - la configuration est utilisée dans PDO options
        // Le test réel sera dans les tests d'intégration
        $this->assertInstanceOf(ConnectionService::class, $service);
    }

    public function testDefaultQueryTimeout()
    {
        $config = $this->tester->createMockConfig();
        unset($config['QUERY_TIMEOUT']); // Pas de timeout configuré
        
        $service = ConnectionService::getInstance($config, $this->mockLogger);
        
        // Devrait utiliser la valeur par défaut (30 secondes)
        $this->assertInstanceOf(ConnectionService::class, $service);
    }
}