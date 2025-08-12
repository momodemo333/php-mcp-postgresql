#!/usr/bin/env php
<?php

declare(strict_types=1);

// Autoload des dépendances
require_once __DIR__ . '/../vendor/autoload.php';

use PostgreSqlMcp\PostgreSqlServer;
use PhpMcp\Server\Transports\StdioServerTransport;
use Psr\Log\LogLevel;

/**
 * Simple logger pour STDERR (évite STDOUT utilisé par MCP)
 */
class StderrLogger implements \Psr\Log\LoggerInterface
{
    private string $minLevel;
    
    public function __construct(string $minLevel = LogLevel::INFO)
    {
        $this->minLevel = $minLevel;
    }
    
    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }
    
    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }
    
    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }
    
    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }
    
    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }
    
    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }
    
    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }
    
    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }
    
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $levels = [
            LogLevel::DEBUG => 0,
            LogLevel::INFO => 1,
            LogLevel::NOTICE => 2,
            LogLevel::WARNING => 3,
            LogLevel::ERROR => 4,
            LogLevel::CRITICAL => 5,
            LogLevel::ALERT => 6,
            LogLevel::EMERGENCY => 7,
        ];
        
        if (($levels[$level] ?? 1) >= ($levels[$this->minLevel] ?? 1)) {
            $timestamp = date('Y-m-d H:i:s');
            $contextStr = empty($context) ? '' : ' ' . json_encode($context, JSON_UNESCAPED_UNICODE);
            fwrite(STDERR, "[{$timestamp}] {$level}: {$message}{$contextStr}\n");
        }
    }
}

// Gestion des arguments de ligne de commande
if (isset($argv[1]) && ($argv[1] === '--version' || $argv[1] === '-v')) {
    echo "PostgreSQL MCP Server v1.0.0\n";
    exit(0);
}

try {
    // Configuration du logger selon LOG_LEVEL
    $logLevel = $_ENV['LOG_LEVEL'] ?? 'INFO';
    $logger = new StderrLogger($logLevel);
    
    $logger->info('Démarrage du serveur PostgreSQL MCP');
    
    // Création du serveur PostgreSQL MCP
    $pgsqlServer = new PostgreSqlServer([], $logger);
    
    // Test de connexion avant démarrage
    if (!$pgsqlServer->testConnection()) {
        $logger->error('Impossible de se connecter à PostgreSQL avec la configuration actuelle');
        exit(1);
    }
    
    $logger->info('Connexion PostgreSQL validée', $pgsqlServer->getConfig());
    
    // Construction du serveur MCP
    $server = $pgsqlServer->createServer();
    
    // Création du transport stdio
    $transport = new StdioServerTransport();
    
    $logger->info('Serveur prêt, écoute via stdio...');
    
    // Démarrage du serveur (boucle bloquante)
    $server->listen($transport);
    
} catch (\Throwable $e) {
    fwrite(STDERR, "[CRITICAL ERROR] " . $e->getMessage() . "\n");
    fwrite(STDERR, "Stack trace: " . $e->getTraceAsString() . "\n");
    exit(1);
}