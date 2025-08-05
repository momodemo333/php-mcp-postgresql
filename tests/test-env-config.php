#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Test de configuration via variables d'environnement
 * Simule ce que ferait Claude Code avec la config MCP "env"
 */

// Simulation des variables d'environnement que Claude Code passerait
putenv('MYSQL_HOST=127.0.0.1');
putenv('MYSQL_PORT=33099');
putenv('MYSQL_USER=mcpusertest');
putenv('MYSQL_PASS=tototugoi');
putenv('MYSQL_DB=mcptesttable');
putenv('ALLOW_INSERT_OPERATION=true');
putenv('ALLOW_UPDATE_OPERATION=true');
putenv('ALLOW_DELETE_OPERATION=false');
putenv('LOG_LEVEL=INFO');

// Charge le serveur principal
echo "🧪 Test configuration MCP avec variables d'environnement\n";
echo "=====================================\n\n";

require_once __DIR__ . '/../vendor/autoload.php';

use MySqlMcp\MySqlServer;

try {
    $logger = new class implements \Psr\Log\LoggerInterface {
        public function emergency(string|\Stringable $message, array $context = []): void { $this->log('EMERGENCY', $message, $context); }
        public function alert(string|\Stringable $message, array $context = []): void { $this->log('ALERT', $message, $context); }
        public function critical(string|\Stringable $message, array $context = []): void { $this->log('CRITICAL', $message, $context); }
        public function error(string|\Stringable $message, array $context = []): void { $this->log('ERROR', $message, $context); }
        public function warning(string|\Stringable $message, array $context = []): void { $this->log('WARNING', $message, $context); }
        public function notice(string|\Stringable $message, array $context = []): void { $this->log('NOTICE', $message, $context); }
        public function info(string|\Stringable $message, array $context = []): void { $this->log('INFO', $message, $context); }
        public function debug(string|\Stringable $message, array $context = []): void { $this->log('DEBUG', $message, $context); }
        
        public function log($level, string|\Stringable $message, array $context = []): void {
            $contextStr = empty($context) ? '' : ' ' . json_encode($context, JSON_UNESCAPED_UNICODE);
            echo "[{$level}] {$message}{$contextStr}\n";
        }
    };
    
    // Création du serveur avec variables d'environnement
    $mysqlServer = new MySqlServer([], $logger);
    
    echo "✅ Serveur MySQL MCP créé\n";
    
    // Test de connexion
    if ($mysqlServer->testConnection()) {
        echo "✅ Connexion MySQL validée\n";
    } else {
        throw new Exception('Test de connexion échoué');
    }
    
    // Affichage de la configuration
    $config = $mysqlServer->getConfig();
    echo "\n📋 Configuration chargée :\n";
    foreach (['MYSQL_HOST', 'MYSQL_PORT', 'MYSQL_USER', 'MYSQL_DB', 'ALLOW_INSERT_OPERATION', 'ALLOW_UPDATE_OPERATION', 'ALLOW_DELETE_OPERATION'] as $key) {
        $value = $config[$key] ?? 'non défini';
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }
        echo "   {$key}: {$value}\n";
    }
    
    echo "\n🎯 Configuration MCP Correspondante :\n";
    echo '   {
       "mcpServers": {
           "mysql-test": {
               "command": "php",
               "args": ["' . __DIR__ . '/server.php"],
               "env": {
                   "MYSQL_HOST": "127.0.0.1",
                   "MYSQL_PORT": "33099",
                   "MYSQL_USER": "mcpusertest",
                   "MYSQL_PASS": "tototugoi",
                   "MYSQL_DB": "mcptesttable",
                   "ALLOW_INSERT_OPERATION": "true",
                   "ALLOW_UPDATE_OPERATION": "true",
                   "ALLOW_DELETE_OPERATION": "false"
               }
           }
       }
   }' . "\n";
    
    echo "\n✅ Configuration MCP avec 'env' fonctionne parfaitement !\n";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}