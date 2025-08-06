<?php

declare(strict_types=1);

namespace MySqlMcp\Tests\PHPUnit\Integration;

use PHPUnit\Framework\TestCase;

class DatabaseConnectionTest extends TestCase
{
    private ?\PDO $connection = null;

    protected function setUp(): void
    {
        // Only run integration tests if MySQL environment is available
        if (!$this->isMysqlAvailable()) {
            $this->markTestSkipped('MySQL environment not available for integration tests');
        }

        // Create direct PDO connection for testing
        $this->connection = $this->createTestConnection();
    }

    public function testCanConnectToDatabase(): void
    {
        $this->assertNotNull($this->connection);
        
        // Test basic query
        $stmt = $this->connection->prepare('SELECT 1 as test');
        $stmt->execute();
        $result = $stmt->fetch();
        
        $this->assertEquals(1, $result['test']);
    }

    public function testCanExecuteSimpleQuery(): void
    {
        // Create test table
        $this->connection->exec('CREATE TEMPORARY TABLE test_phpunit (id INT PRIMARY KEY, name VARCHAR(50))');
        
        // Insert test data
        $stmt = $this->connection->prepare('INSERT INTO test_phpunit (id, name) VALUES (?, ?)');
        $stmt->execute([1, 'Test User']);
        
        // Read test data
        $stmt = $this->connection->prepare('SELECT * FROM test_phpunit WHERE id = ?');
        $stmt->execute([1]);
        $result = $stmt->fetch();
        
        $this->assertEquals(1, $result['id']);
        $this->assertEquals('Test User', $result['name']);
    }

    private function createTestConnection(): ?\PDO
    {
        $host = getenv('MYSQL_HOST') ?: '127.0.0.1';
        $port = getenv('MYSQL_PORT') ?: '3306';
        $user = getenv('MYSQL_USER') ?: 'root';
        $pass = getenv('MYSQL_PASS') ?: '';
        $db = getenv('MYSQL_DB') ?: 'mysql';
        
        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            return new \PDO($dsn, $user, $pass, $options);
        } catch (\PDOException $e) {
            return null;
        }
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