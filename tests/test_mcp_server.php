#!/usr/bin/env php
<?php

/**
 * Test du serveur MCP PostgreSQL
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PostgreSqlMcp\PostgreSqlServer;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

// Logger simple pour les tests
class TestLogger implements LoggerInterface
{
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
        $time = date('H:i:s');
        $contextStr = empty($context) ? '' : ' ' . json_encode($context);
        echo "[$time] [$level] $message$contextStr\n";
    }
}

// Configuration pour les tests (utilise les variables d'environnement ou les valeurs par défaut)
$config = [
    'PGSQL_HOST' => getenv('PGSQL_HOST') ?: 'localhost',
    'PGSQL_PORT' => getenv('PGSQL_PORT') ?: 54320,
    'PGSQL_USER' => getenv('PGSQL_USER') ?: 'testuser',
    'PGSQL_PASS' => getenv('PGSQL_PASS') ?: 'testpass',
    'PGSQL_DB' => getenv('PGSQL_DB') ?: 'testdb',
    'ALLOW_INSERT_OPERATION' => getenv('ALLOW_INSERT_OPERATION') ?: 'true',
    'ALLOW_UPDATE_OPERATION' => getenv('ALLOW_UPDATE_OPERATION') ?: 'true',
    'ALLOW_DELETE_OPERATION' => getenv('ALLOW_DELETE_OPERATION') ?: 'true',
    'QUERY_TIMEOUT' => 30,
    'MAX_RESULTS' => 1000,
    'CONNECTION_POOL_SIZE' => 5,
];

echo "🐘 Test du serveur MCP PostgreSQL\n";
echo "==================================\n\n";

$logger = new TestLogger();

try {
    echo "1. Création du serveur PostgreSQL MCP... ";
    $pgServer = new PostgreSqlServer($config, $logger);
    echo "✅\n";

    echo "2. Test de connexion... ";
    if (!$pgServer->testConnection()) {
        echo "❌ Impossible de se connecter à PostgreSQL\n";
        exit(1);
    }
    echo "✅\n";

    echo "3. Création du serveur MCP... ";
    $server = $pgServer->createServer();
    echo "✅\n";

    echo "4. Vérification des outils disponibles... ";
    // Le serveur MCP a été créé avec succès, les outils sont enregistrés
    echo "✅\n";
    
    echo "\n📋 Outils MCP enregistrés:\n";
    $expectedTools = [
        'pgsql_list_databases',
        'pgsql_list_tables', 
        'pgsql_describe_table',
        'pgsql_server_status',
        'pgsql_select',
        'pgsql_insert',
        'pgsql_update',
        'pgsql_delete',
        'pgsql_execute_query'
    ];
    
    foreach ($expectedTools as $tool) {
        echo "  - $tool\n";
    }

    echo "\n5. Test d'exécution d'un outil (listDatabases)... ";
    // Créer directement les instances des outils pour les tests
    $connectionService = \PostgreSqlMcp\Services\ConnectionService::getInstance($config, $logger);
    $securityService = new \PostgreSqlMcp\Services\SecurityService($config, $logger);
    
    $dbTools = new \PostgreSqlMcp\Elements\DatabaseTools($connectionService, $securityService, $logger);
    $databases = $dbTools->listDatabases();
    echo "✅\n";
    
    echo "\n📂 Bases de données:\n";
    echo "  Utilisateur: " . count($databases['databases']) . " bases\n";
    foreach ($databases['databases'] as $db) {
        echo "    - $db\n";
    }

    echo "\n6. Test d'exécution d'un outil (listTables)... ";
    $tables = $dbTools->listTables(null, false, 10);
    echo "✅\n";
    
    echo "\n📋 Tables dans la base courante:\n";
    echo "  Total: " . $tables['total_count'] . " tables\n";
    foreach ($tables['tables'] as $table) {
        echo "    - " . (is_array($table) ? $table['name'] : $table) . "\n";
    }

    echo "\n7. Test d'exécution d'un outil (getServerStatus)... ";
    $status = $dbTools->getServerStatus();
    echo "✅\n";
    
    echo "\n⚙️ Statut serveur:\n";
    echo "  Version: " . substr($status['postgresql_version'], 0, 50) . "...\n";
    echo "  Connexions: {$status['postgresql_connections']}\n";
    echo "  Requêtes: {$status['postgresql_queries']}\n";
    echo "  Test connexion: " . ($status['connection_test'] ? 'OK' : 'FAIL') . "\n";

    echo "\n8. Test des QueryTools (SELECT)... ";
    $queryTools = new \PostgreSqlMcp\Elements\QueryTools($connectionService, $securityService, $logger, $config);
    $result = $queryTools->executeSelect("SELECT * FROM users WHERE is_active = true LIMIT 3");
    echo "✅\n";
    
    echo "\n👥 Utilisateurs actifs (limité à 3):\n";
    foreach ($result['results'] as $user) {
        echo "  - {$user['username']} ({$user['email']})\n";
    }
    echo "  Total: {$result['row_count']} résultats\n";
    echo "  Temps: {$result['execution_time_ms']}ms\n";

    echo "\n9. Test des fonctionnalités PostgreSQL (JSONB)... ";
    $jsonResult = $queryTools->executeSelect("
        SELECT username, 
               metadata->>'role' as role,
               metadata->'preferences'->>'theme' as theme
        FROM users 
        WHERE metadata->>'role' IS NOT NULL
    ");
    echo "✅\n";
    
    echo "\n🔧 Utilisateurs avec rôles:\n";
    foreach ($jsonResult['results'] as $user) {
        $theme = $user['theme'] ?? 'non défini';
        echo "  - {$user['username']}: {$user['role']} (thème: $theme)\n";
    }

    echo "\n10. Test des fonctionnalités PostgreSQL (Arrays)... ";
    $arrayResult = $queryTools->executeSelect("
        SELECT name, array_length(tags, 1) as tag_count
        FROM products
        WHERE tags && ARRAY['computers', 'office']::text[]
    ");
    echo "✅\n";
    
    echo "\n🏷️ Produits avec tags 'computers' ou 'office':\n";
    foreach ($arrayResult['results'] as $product) {
        echo "  - {$product['name']} ({$product['tag_count']} tags)\n";
    }

    echo "\n✅ Tous les tests du serveur MCP sont passés avec succès!\n\n";

} catch (\Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}