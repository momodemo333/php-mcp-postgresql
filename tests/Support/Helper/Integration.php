<?php

declare(strict_types=1);

namespace MySqlMcp\Tests\Support\Helper;

use Codeception\Module;

/**
 * Helper pour les tests d'intégration
 */
class Integration extends Module
{
    /**
     * Configure les variables d'environnement pour les tests
     */
    public function setTestEnvironment(array $config = []): void
    {
        $defaultConfig = [
            'MYSQL_HOST' => '127.0.0.1',
            'MYSQL_PORT' => '33306',
            'MYSQL_USER' => 'testuser',
            'MYSQL_PASS' => 'testpass',
            'MYSQL_DB' => 'testdb',
            'ALLOW_INSERT_OPERATION' => 'true',
            'ALLOW_UPDATE_OPERATION' => 'true',
            'ALLOW_DELETE_OPERATION' => 'true',
            'ALLOW_TRUNCATE_OPERATION' => 'false',
            'ALLOW_DDL_OPERATIONS' => 'false',
            'ALLOW_ALL_OPERATIONS' => 'false',
            'MAX_RESULTS' => '1000',
            'QUERY_TIMEOUT' => '30',
            'LOG_LEVEL' => 'ERROR',
        ];

        $config = array_merge($defaultConfig, $config);

        foreach ($config as $key => $value) {
            putenv("{$key}={$value}");
        }
    }

    /**
     * Nettoie les variables d'environnement après les tests
     */
    public function cleanEnvironment(): void
    {
        $envVars = [
            'MYSQL_HOST', 'MYSQL_PORT', 'MYSQL_USER', 'MYSQL_PASS', 'MYSQL_DB',
            'ALLOW_INSERT_OPERATION', 'ALLOW_UPDATE_OPERATION', 'ALLOW_DELETE_OPERATION',
            'ALLOW_TRUNCATE_OPERATION', 'ALLOW_DDL_OPERATIONS', 'ALLOW_ALL_OPERATIONS',
            'MAX_RESULTS', 'QUERY_TIMEOUT', 'LOG_LEVEL'
        ];

        foreach ($envVars as $var) {
            putenv($var);
        }
    }

    /**
     * Crée une configuration de test avec permissions spécifiques
     */
    public function createTestConfig(array $overrides = []): array
    {
        $config = [
            'MYSQL_HOST' => getenv('MYSQL_HOST') ?: '127.0.0.1',
            'MYSQL_PORT' => (int)(getenv('MYSQL_PORT') ?: 33306),
            'MYSQL_USER' => getenv('MYSQL_USER') ?: 'testuser',
            'MYSQL_PASS' => getenv('MYSQL_PASS') ?: 'testpass',
            'MYSQL_DB' => getenv('MYSQL_DB') ?: 'testdb',
            'ALLOW_INSERT_OPERATION' => $this->getBoolValue(getenv('ALLOW_INSERT_OPERATION') ?: 'true'),
            'ALLOW_UPDATE_OPERATION' => $this->getBoolValue(getenv('ALLOW_UPDATE_OPERATION') ?: 'true'),
            'ALLOW_DELETE_OPERATION' => $this->getBoolValue(getenv('ALLOW_DELETE_OPERATION') ?: 'false'),
            'ALLOW_TRUNCATE_OPERATION' => $this->getBoolValue(getenv('ALLOW_TRUNCATE_OPERATION') ?: 'false'),
            'ALLOW_DDL_OPERATIONS' => $this->getBoolValue(getenv('ALLOW_DDL_OPERATIONS') ?: 'false'),
            'ALLOW_ALL_OPERATIONS' => $this->getBoolValue(getenv('ALLOW_ALL_OPERATIONS') ?: 'false'),
            'MAX_RESULTS' => (int)(getenv('MAX_RESULTS') ?: 1000),
            'QUERY_TIMEOUT' => (int)(getenv('QUERY_TIMEOUT') ?: 30),
            'LOG_LEVEL' => getenv('LOG_LEVEL') ?: 'ERROR',
        ];

        return array_merge($config, $overrides);
    }

    private function getBoolValue(string $value): bool
    {
        return in_array(strtolower($value), ['true', '1', 'yes', 'on'], true);
    }
}