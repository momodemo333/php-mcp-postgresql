#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Serveur MCP MySQL avec configuration via arguments CLI
 * Usage: php server-cli.php --host=127.0.0.1 --port=3306 --user=root --pass=password --db=database
 */

require_once __DIR__ . '/vendor/autoload.php';

use MySqlMcp\MySqlServer;
use PhpMcp\Server\Transports\StdioServerTransport;
use Psr\Log\LogLevel;

// Logger simple pour STDERR
class StderrLogger implements \Psr\Log\LoggerInterface
{
    private string $minLevel;
    
    public function __construct(string $minLevel = LogLevel::INFO)
    {
        $this->minLevel = $minLevel;
    }
    
    public function emergency(string|\Stringable $message, array $context = []): void { $this->log(LogLevel::EMERGENCY, $message, $context); }
    public function alert(string|\Stringable $message, array $context = []): void { $this->log(LogLevel::ALERT, $message, $context); }
    public function critical(string|\Stringable $message, array $context = []): void { $this->log(LogLevel::CRITICAL, $message, $context); }
    public function error(string|\Stringable $message, array $context = []): void { $this->log(LogLevel::ERROR, $message, $context); }
    public function warning(string|\Stringable $message, array $context = []): void { $this->log(LogLevel::WARNING, $message, $context); }
    public function notice(string|\Stringable $message, array $context = []): void { $this->log(LogLevel::NOTICE, $message, $context); }
    public function info(string|\Stringable $message, array $context = []): void { $this->log(LogLevel::INFO, $message, $context); }
    public function debug(string|\Stringable $message, array $context = []): void { $this->log(LogLevel::DEBUG, $message, $context); }
    
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $levels = [LogLevel::DEBUG => 0, LogLevel::INFO => 1, LogLevel::NOTICE => 2, LogLevel::WARNING => 3, LogLevel::ERROR => 4, LogLevel::CRITICAL => 5, LogLevel::ALERT => 6, LogLevel::EMERGENCY => 7];
        if (($levels[$level] ?? 1) >= ($levels[$this->minLevel] ?? 1)) {
            $timestamp = date('Y-m-d H:i:s');
            $contextStr = empty($context) ? '' : ' ' . json_encode($context, JSON_UNESCAPED_UNICODE);
            fwrite(STDERR, "[{$timestamp}] {$level}: {$message}{$contextStr}\n");
        }
    }
}

// Parse des arguments CLI
function parseCliArgs($argv): array {
    $config = [];
    
    foreach ($argv as $arg) {
        if (strpos($arg, '--') === 0) {
            $parts = explode('=', substr($arg, 2), 2);
            if (count($parts) === 2) {
                $key = strtoupper($parts[0]);
                $value = $parts[1];
                
                // Mapping des arguments vers les variables d'environnement
                $mapping = [
                    'HOST' => 'MYSQL_HOST',
                    'PORT' => 'MYSQL_PORT', 
                    'USER' => 'MYSQL_USER',
                    'PASS' => 'MYSQL_PASS',
                    'PASSWORD' => 'MYSQL_PASS',
                    'DB' => 'MYSQL_DB',
                    'DATABASE' => 'MYSQL_DB',
                    'ALLOW-INSERT' => 'ALLOW_INSERT_OPERATION',
                    'ALLOW-UPDATE' => 'ALLOW_UPDATE_OPERATION',
                    'ALLOW-DELETE' => 'ALLOW_DELETE_OPERATION',
                    'LOG-LEVEL' => 'LOG_LEVEL'
                ];
                
                $envKey = $mapping[$key] ?? 'MYSQL_' . $key;
                $config[$envKey] = $value;
            }
        }
    }
    
    return $config;
}

// Affichage de l'aide
function showHelp() {
    echo "🗄️ Serveur MCP MySQL - Configuration CLI\n\n";
    echo "Usage: php server-cli.php [options]\n\n";
    echo "Options:\n";
    echo "  --host=HOST         Host MySQL (défaut: 127.0.0.1)\n";
    echo "  --port=PORT         Port MySQL (défaut: 3306)\n";
    echo "  --user=USER         Utilisateur MySQL\n";
    echo "  --pass=PASSWORD     Mot de passe MySQL\n";
    echo "  --db=DATABASE       Base de données (optionnel)\n";
    echo "  --allow-insert=BOOL Autoriser INSERT (true/false)\n";
    echo "  --allow-update=BOOL Autoriser UPDATE (true/false)\n";
    echo "  --allow-delete=BOOL Autoriser DELETE (true/false)\n";
    echo "  --log-level=LEVEL   Niveau de log (DEBUG,INFO,WARN,ERROR)\n";
    echo "  --help              Affiche cette aide\n\n";
    echo "Exemple:\n";
    echo "  php server-cli.php --host=127.0.0.1 --port=3306 --user=root --pass=secret --db=myapp\n\n";
}

try {
    // Vérification aide
    if (in_array('--help', $argv) || in_array('-h', $argv)) {
        showHelp();
        exit(0);
    }
    
    // Parse des arguments
    $cliConfig = parseCliArgs($argv);
    
    if (empty($cliConfig)) {
        fwrite(STDERR, "⚠️  Aucune configuration fournie, utilisation des valeurs par défaut/environnement\n");
    }
    
    // Configuration du logger
    $logLevel = $cliConfig['LOG_LEVEL'] ?? $_ENV['LOG_LEVEL'] ?? 'INFO';
    $logger = new StderrLogger($logLevel);
    
    $logger->info('Démarrage du serveur MySQL MCP', ['config_source' => 'CLI']);
    
    // Création du serveur avec la config CLI
    $mysqlServer = new MySqlServer($cliConfig, $logger);
    
    // Test de connexion
    if (!$mysqlServer->testConnection()) {
        $logger->error('Impossible de se connecter à MySQL avec la configuration fournie');
        exit(1);
    }
    
    $logger->info('Connexion MySQL validée', array_filter($mysqlServer->getConfig(), fn($k) => $k !== 'MYSQL_PASS', ARRAY_FILTER_USE_KEY));
    
    // Construction du serveur MCP
    $server = $mysqlServer->createServer();
    
    // Création du transport stdio
    $transport = new StdioServerTransport();
    
    $logger->info('Serveur prêt, écoute via stdio...');
    
    // Démarrage du serveur
    $server->listen($transport);
    
} catch (\Throwable $e) {
    fwrite(STDERR, "[CRITICAL ERROR] " . $e->getMessage() . "\n");
    fwrite(STDERR, "Stack trace: " . $e->getTraceAsString() . "\n");
    exit(1);
}