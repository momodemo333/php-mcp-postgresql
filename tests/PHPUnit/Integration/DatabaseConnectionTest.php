<?php

declare(strict_types=1);

namespace MySqlMcp\Tests\PHPUnit\Integration;

use PHPUnit\Framework\TestCase;
use MySqlMcp\Services\ConnectionService;

class DatabaseConnectionTest extends TestCase
{
    private ?ConnectionService $connectionService = null;

    protected function setUp(): void
    {
        // Only run integration tests if MySQL environment is available
        if (!$this->isMysqlAvailable()) {
            $this->markTestSkipped('MySQL environment not available for integration tests');
        }

        $this->connectionService = new ConnectionService();
    }

    public function testCanConnectToDatabase(): void
    {
        $connection = $this->connectionService->getConnection();
        $this->assertNotNull($connection);
        
        // Test basic query
        $stmt = $connection->prepare('SELECT 1 as test');
        $stmt->execute();
        $result = $stmt->fetch();
        
        $this->assertEquals(1, $result['test']);
    }

    public function testCanExecuteSimpleQuery(): void
    {
        $connection = $this->connectionService->getConnection();
        
        // Create test table
        $connection->exec('CREATE TEMPORARY TABLE test_phpunit (id INT PRIMARY KEY, name VARCHAR(50))');
        
        // Insert test data
        $stmt = $connection->prepare('INSERT INTO test_phpunit (id, name) VALUES (?, ?)');
        $stmt->execute([1, 'Test User']);
        
        // Read test data
        $stmt = $connection->prepare('SELECT * FROM test_phpunit WHERE id = ?');
        $stmt->execute([1]);
        $result = $stmt->fetch();
        
        $this->assertEquals(1, $result['id']);
        $this->assertEquals('Test User', $result['name']);
    }

    private function isMysqlAvailable(): bool
    {
        $host = getenv('MYSQL_HOST') ?: $_ENV['MYSQL_HOST'] ?? 'localhost';
        $port = getenv('MYSQL_PORT') ?: $_ENV['MYSQL_PORT'] ?? '3306';
        $user = getenv('MYSQL_USER') ?: $_ENV['MYSQL_USER'] ?? 'root';
        $pass = getenv('MYSQL_PASS') ?: $_ENV['MYSQL_PASS'] ?? '';
        
        try {
            $dsn = "mysql:host={$host};port={$port}";
            $pdo = new \PDO($dsn, $user, $pass);
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }
}