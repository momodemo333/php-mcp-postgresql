#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use MySqlMcp\Services\ConnectionService;
use MySqlMcp\Services\SecurityService;
use MySqlMcp\Elements\DatabaseTools;
use Psr\Log\NullLogger;

// Configuration de test
$config = [
    'host' => getenv('MYSQL_HOST') ?: '127.0.0.1',
    'port' => (int)(getenv('MYSQL_PORT') ?: 3306),
    'user' => getenv('MYSQL_USER') ?: 'root',
    'password' => getenv('MYSQL_PASS') ?: '',
    'database' => getenv('MYSQL_DB') ?: 'test'
];

try {
    echo "🧪 Test des améliorations listTables\n";
    echo "=====================================\n";
    
    // Services
    $connectionService = ConnectionService::getInstance($config);
    $securityService = new SecurityService([]);
    $logger = new NullLogger();
    
    $databaseTools = new DatabaseTools($connectionService, $securityService, $logger);
    
    echo "\n1. Test mysql_list_table_names (ultra-économe)\n";
    echo "-----------------------------------------------\n";
    $names = $databaseTools->listTableNames(limit: 10);
    echo "Résultat: " . json_encode($names, JSON_PRETTY_PRINT) . "\n";
    
    echo "\n2. Test mysql_list_tables en mode simple (défaut)\n";
    echo "-------------------------------------------------\n";
    $simple = $databaseTools->listTables(detailed: false, limit: 5);
    echo "Résultat: " . json_encode($simple, JSON_PRETTY_PRINT) . "\n";
    
    echo "\n3. Test mysql_list_tables en mode détaillé\n";
    echo "-------------------------------------------\n";
    $detailed = $databaseTools->listTables(detailed: true, limit: 3);
    echo "Résultat: " . json_encode($detailed, JSON_PRETTY_PRINT) . "\n";
    
    echo "\n✅ Tests terminés avec succès!\n";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}