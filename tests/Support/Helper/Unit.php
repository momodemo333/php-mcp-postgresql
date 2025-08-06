<?php

declare(strict_types=1);

namespace MySqlMcp\Tests\Support\Helper;

use Codeception\Module;

/**
 * Helper pour les tests unitaires
 */
class Unit extends Module
{
    /**
     * Crée un mock de configuration pour les tests
     */
    public function createMockConfig(array $config = []): array
    {
        $defaultConfig = [
            'MYSQL_HOST' => 'localhost',
            'MYSQL_PORT' => 3306,
            'MYSQL_USER' => 'root',
            'MYSQL_PASS' => 'password',
            'MYSQL_DB' => 'testdb',
            'ALLOW_INSERT_OPERATION' => false,
            'ALLOW_UPDATE_OPERATION' => false,
            'ALLOW_DELETE_OPERATION' => false,
            'ALLOW_TRUNCATE_OPERATION' => false,
            'ALLOW_DDL_OPERATIONS' => false,
            'ALLOW_ALL_OPERATIONS' => false,
            'MAX_RESULTS' => 1000,
            'QUERY_TIMEOUT' => 30,
            'BLOCK_DANGEROUS_KEYWORDS' => true,
            'ALLOWED_SCHEMAS' => '',
            'LOG_LEVEL' => 'ERROR',
        ];

        return array_merge($defaultConfig, $config);
    }

    /**
     * Génère une requête SQL de test
     */
    public function generateTestQuery(string $type = 'SELECT', array $params = []): string
    {
        switch (strtoupper($type)) {
            case 'SELECT':
                return $params['query'] ?? 'SELECT * FROM users LIMIT 10';
            case 'INSERT':
                return $params['query'] ?? "INSERT INTO users (name, email) VALUES ('Test User', 'test@example.com')";
            case 'UPDATE':
                return $params['query'] ?? "UPDATE users SET name = 'Updated User' WHERE id = 1";
            case 'DELETE':
                return $params['query'] ?? "DELETE FROM users WHERE id = 1";
            case 'CREATE':
                return $params['query'] ?? "CREATE TABLE test (id INT PRIMARY KEY)";
            case 'ALTER':
                return $params['query'] ?? "ALTER TABLE test ADD COLUMN name VARCHAR(100)";
            case 'DROP':
                return $params['query'] ?? "DROP TABLE test";
            case 'TRUNCATE':
                return $params['query'] ?? "TRUNCATE TABLE test";
            default:
                throw new \InvalidArgumentException("Type de requête non supporté: {$type}");
        }
    }

    /**
     * Créé un logger mock pour les tests
     */
    public function createMockLogger(): \Psr\Log\LoggerInterface
    {
        return new class implements \Psr\Log\LoggerInterface {
            private array $logs = [];

            public function emergency($message, array $context = []): void { $this->logs[] = ['emergency', $message, $context]; }
            public function alert($message, array $context = []): void { $this->logs[] = ['alert', $message, $context]; }
            public function critical($message, array $context = []): void { $this->logs[] = ['critical', $message, $context]; }
            public function error($message, array $context = []): void { $this->logs[] = ['error', $message, $context]; }
            public function warning($message, array $context = []): void { $this->logs[] = ['warning', $message, $context]; }
            public function notice($message, array $context = []): void { $this->logs[] = ['notice', $message, $context]; }
            public function info($message, array $context = []): void { $this->logs[] = ['info', $message, $context]; }
            public function debug($message, array $context = []): void { $this->logs[] = ['debug', $message, $context]; }
            public function log($level, $message, array $context = []): void { $this->logs[] = [$level, $message, $context]; }

            public function getLogs(): array { return $this->logs; }
            public function clearLogs(): void { $this->logs = []; }
        };
    }
}