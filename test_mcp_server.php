#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use MySqlMcp\MySqlServer;

/**
 * Script de test du serveur MCP MySQL
 */

echo "🧪 Test du serveur MCP MySQL...\n";

try {
    // Configuration depuis le fichier .env
    $mysqlServer = new MySqlServer();
    
    echo "✅ Serveur MCP MySQL initialisé\n";
    
    // Test de connexion
    if (!$mysqlServer->testConnection()) {
        throw new Exception('Test de connexion échoué');
    }
    
    echo "✅ Connexion MySQL validée\n";
    
    // Affichage de la configuration (sans mot de passe)
    $config = $mysqlServer->getConfig();
    echo "📋 Configuration :\n";
    foreach ($config as $key => $value) {
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }
        echo "   {$key}: {$value}\n";
    }
    
    echo "\n🎯 Le serveur est prêt à être utilisé !\n";
    echo "\n📋 Commandes disponibles :\n";
    echo "   • mysql_list_databases\n";  
    echo "   • mysql_list_tables\n";
    echo "   • mysql_describe_table\n";
    echo "   • mysql_server_status\n";
    echo "   • mysql_select\n";
    echo "   • mysql_insert\n";
    echo "   • mysql_update\n";
    echo "   • mysql_delete\n";
    echo "   • mysql_execute_query\n";
    
    echo "\n📊 Ressources disponibles :\n";
    echo "   • mysql://connection/status\n";
    echo "   • mysql://server/capabilities\n";
    
    echo "\n🚀 Pour démarrer le serveur MCP :\n";
    echo "   ./server.php\n";
    
    echo "\n💡 Configuration Claude Code :\n";
    echo '   {
       "mcpServers": {
           "mysql-server": {
               "command": "php",
               "args": ["' . __DIR__ . '/server.php"]
           }
       }
   }' . "\n";
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
    exit(1);
}