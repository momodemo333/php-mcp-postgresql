#!/usr/bin/env php
<?php

/**
 * Script pour exécuter les tests avec Docker MySQL
 * Démarre le container, exécute les tests, puis nettoie
 */

use Symfony\Component\Process\Process;

require_once __DIR__ . '/../../vendor/autoload.php';

class DockerTestRunner
{
    private const CONTAINER_NAME = 'php-mcp-mysql-test';
    private const MYSQL_PORT = 33306;
    private const HEALTH_CHECK_MAX_ATTEMPTS = 30;
    private const HEALTH_CHECK_INTERVAL = 2;

    private bool $verbose = false;

    public function __construct(bool $verbose = false)
    {
        $this->verbose = $verbose;
    }

    public function run(): int
    {
        $this->log("🐳 Démarrage des tests avec Docker MySQL...\n");

        try {
            // 1. Nettoyer les containers existants
            $this->cleanup();

            // 2. Démarrer le container MySQL
            $this->startMysqlContainer();

            // 3. Attendre que MySQL soit prêt
            $this->waitForMysql();

            // 4. Configurer les variables d'environnement
            $this->setTestEnvironment();

            // 5. Exécuter les tests
            $exitCode = $this->runTests();

            return $exitCode;

        } catch (Exception $e) {
            $this->log("❌ Erreur: " . $e->getMessage() . "\n");
            return 1;
        } finally {
            // 6. Nettoyer les containers
            $this->cleanup();
        }
    }

    private function cleanup(): void
    {
        $this->log("🧹 Nettoyage des containers existants...");
        
        $processes = [
            new Process(['docker', 'stop', self::CONTAINER_NAME]),
            new Process(['docker', 'rm', self::CONTAINER_NAME]),
            new Process(['docker', 'volume', 'rm', 'php-mcp-mysql_mysql_test_data']),
        ];

        foreach ($processes as $process) {
            $process->run();
            // Ignore les erreurs de cleanup
        }

        $this->log(" ✓\n");
    }

    private function startMysqlContainer(): void
    {
        $this->log("🚀 Démarrage du container MySQL...");

        $process = new Process([
            'docker-compose', 
            '-f', 'docker-compose.test.yml', 
            'up', '-d', '--build'
        ]);
        
        $process->setTimeout(120);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new Exception("Impossible de démarrer le container MySQL: " . $process->getErrorOutput());
        }

        $this->log(" ✓\n");
    }

    private function waitForMysql(): void
    {
        $this->log("⏳ Attente de MySQL...");

        $attempt = 0;
        while ($attempt < self::HEALTH_CHECK_MAX_ATTEMPTS) {
            $process = new Process([
                'docker', 'exec', self::CONTAINER_NAME,
                'mysqladmin', 'ping', '-h', 'localhost', '-u', 'root', '-ptestroot'
            ]);

            $process->run();

            if ($process->isSuccessful()) {
                $this->log(" ✓\n");
                $this->log("🔗 MySQL est prêt sur le port " . self::MYSQL_PORT . "\n");
                return;
            }

            $attempt++;
            sleep(self::HEALTH_CHECK_INTERVAL);
            $this->log(".");
        }

        throw new Exception("MySQL n'est pas prêt après " . self::HEALTH_CHECK_MAX_ATTEMPTS . " tentatives");
    }

    private function setTestEnvironment(): void
    {
        putenv('MYSQL_HOST=127.0.0.1');
        putenv('MYSQL_PORT=' . self::MYSQL_PORT);
        putenv('MYSQL_USER=testuser');
        putenv('MYSQL_PASS=testpass');
        putenv('MYSQL_DB=testdb');
        putenv('TEST_ENVIRONMENT=docker');

        // Configuration de test par défaut
        putenv('ALLOW_INSERT_OPERATION=true');
        putenv('ALLOW_UPDATE_OPERATION=true');
        putenv('ALLOW_DELETE_OPERATION=true');
        putenv('ALLOW_TRUNCATE_OPERATION=true');
        putenv('ALLOW_DDL_OPERATIONS=true');
        putenv('ALLOW_ALL_OPERATIONS=false');
        putenv('MAX_RESULTS=1000');
        putenv('QUERY_TIMEOUT=30');
        putenv('LOG_LEVEL=ERROR');

        $this->log("🔧 Variables d'environnement configurées pour les tests\n");
    }

    private function runTests(): int
    {
        $this->log("🧪 Exécution des tests...\n\n");

        // Codeception tests
        $process = new Process(['vendor/bin/codecept', 'run', '--verbose']);
        $process->setTimeout(300);
        $process->run(function ($type, $buffer) {
            echo $buffer;
        });

        $exitCode = $process->getExitCode();

        if ($exitCode === 0) {
            $this->log("\n✅ Tous les tests sont passés avec succès !\n");
        } else {
            $this->log("\n❌ Certains tests ont échoué (code: $exitCode)\n");
        }

        return $exitCode;
    }

    private function log(string $message): void
    {
        if ($this->verbose || strpos($message, '✓') !== false || strpos($message, '❌') !== false) {
            echo $message;
        }
    }
}

// Parsing des arguments de ligne de commande
$verbose = in_array('--verbose', $argv) || in_array('-v', $argv);

// Exécution
$runner = new DockerTestRunner($verbose);
exit($runner->run());