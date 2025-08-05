#!/usr/bin/env php
<?php

/**
 * Test du wrapper avec fichier .env spécifique
 */

echo "🧪 Test configuration Wrapper avec .env\n";
echo "======================================\n\n";

$envFile = __DIR__ . '/.env.project-test';

if (file_exists($envFile)) {
    echo "📁 Chargement du fichier : {$envFile}\n";
    
    // Simulation du wrapper (copie du code)
    $envContent = file_get_contents($envFile);
    $lines = explode("\n", $envContent);
    
    $loadedVars = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || $line[0] === '#') {
            continue;
        }
        
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Supprime les guillemets si présents
            if (($value[0] === '"' && $value[-1] === '"') || ($value[0] === "'" && $value[-1] === "'")) {
                $value = substr($value, 1, -1);
            }
            
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
            $loadedVars[$key] = $value;
        }
    }
    
    echo "✅ Variables chargées :\n";
    foreach ($loadedVars as $key => $value) {
        $displayValue = $key === 'MYSQL_PASS' ? '***' : $value;
        echo "   {$key}: {$displayValue}\n";
    }
    
    // Test de connexion
    try {
        require_once __DIR__ . '/vendor/autoload.php';
        
        $mysqlServer = new \MySqlMcp\MySqlServer();
        
        if ($mysqlServer->testConnection()) {
            echo "\n✅ Connexion MySQL validée avec le fichier .env\n";
        } else {
            throw new Exception('Test de connexion échoué');
        }
        
        echo "\n🎯 Configuration MCP Correspondante :\n";
        echo '   {
       "mcpServers": {
           "mysql-project-test": {
               "command": "php",
               "args": [
                   "' . __DIR__ . '/server-wrapper.php",
                   "' . $envFile . '"
               ]
           }
       }
   }' . "\n";
        
        echo "\n✅ Configuration Wrapper avec .env fonctionne parfaitement !\n";
        
    } catch (Exception $e) {
        echo "\n❌ Erreur: " . $e->getMessage() . "\n";
        exit(1);
    }
} else {
    echo "❌ Fichier .env de test introuvable: {$envFile}\n";
    exit(1);
}