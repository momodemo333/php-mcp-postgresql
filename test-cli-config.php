#!/usr/bin/env php
<?php

/**
 * Test du serveur CLI (simulation sans démarrer le serveur complet)
 */

echo "🧪 Test configuration CLI\n";
echo "========================\n\n";

// Simulation d'arguments CLI
$testArgs = [
    'server-cli.php',
    '--host=127.0.0.1',
    '--port=33099',
    '--user=mcpusertest',
    '--pass=tototugoi', 
    '--db=mcptesttable',
    '--allow-insert=true',
    '--allow-update=false',
    '--log-level=INFO'
];

// Parse des arguments (copie de la fonction du serveur CLI)
function parseCliArgs($argv): array {
    $config = [];
    
    foreach ($argv as $arg) {
        if (strpos($arg, '--') === 0) {
            $parts = explode('=', substr($arg, 2), 2);
            if (count($parts) === 2) {
                $key = strtoupper($parts[0]);
                $value = $parts[1];
                
                $mapping = [
                    'HOST' => 'MYSQL_HOST',
                    'PORT' => 'MYSQL_PORT', 
                    'USER' => 'MYSQL_USER',
                    'PASS' => 'MYSQL_PASS',
                    'PASSWORD' => 'MYSQL_PASS',
                    'DB' => 'MYSQL_DB',
                    'DATABASE' => 'MYSQL_DB',
                    'ALLOW_INSERT' => 'ALLOW_INSERT_OPERATION',
                    'ALLOW_UPDATE' => 'ALLOW_UPDATE_OPERATION',
                    'ALLOW_DELETE' => 'ALLOW_DELETE_OPERATION',
                    'LOG_LEVEL' => 'LOG_LEVEL'
                ];
                
                $envKey = $mapping[$key] ?? 'MYSQL_' . $key;
                $config[$envKey] = $value;
            }
        }
    }
    
    return $config;
}

$config = parseCliArgs($testArgs);

echo "📋 Arguments parsés :\n";
foreach ($config as $key => $value) {
    if ($key === 'MYSQL_PASS') {
        $value = '***';
    }
    echo "   {$key}: {$value}\n";
}

// Test de connexion rapide
try {
    $dsn = "mysql:host={$config['MYSQL_HOST']};port={$config['MYSQL_PORT']};dbname={$config['MYSQL_DB']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['MYSQL_USER'], $config['MYSQL_PASS'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "\n✅ Connexion PDO testée avec succès\n";
} catch (Exception $e) {
    echo "\n❌ Erreur de connexion: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n🎯 Configuration MCP Correspondante :\n";
echo '   {
       "mcpServers": {
           "mysql-cli-test": {
               "command": "php",
               "args": [
                   "' . __DIR__ . '/server-cli.php",
                   "--host=127.0.0.1",
                   "--port=33099",
                   "--user=mcpusertest",
                   "--pass=tototugoi",
                   "--db=mcptesttable",
                   "--allow-insert=true",
                   "--allow-update=false"
               ]
           }
       }
   }' . "\n";

echo "\n✅ Configuration CLI fonctionne parfaitement !\n";