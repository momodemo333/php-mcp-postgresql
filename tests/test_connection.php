#!/usr/bin/env php
<?php

/**
 * Test de connexion PostgreSQL basique
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PostgreSqlMcp\Services\ConnectionService;

// Configuration pour les tests
$config = [
    'PGSQL_HOST' => 'localhost',
    'PGSQL_PORT' => 54320,  // Port du container Docker
    'PGSQL_USER' => 'testuser',
    'PGSQL_PASS' => 'testpass',
    'PGSQL_DB' => 'testdb',
    'CONNECTION_POOL_SIZE' => 5,
    'QUERY_TIMEOUT' => 30,
];

echo "🐘 Test de connexion PostgreSQL MCP\n";
echo "===================================\n\n";

try {
    echo "Configuration:\n";
    echo "  Host: {$config['PGSQL_HOST']}:{$config['PGSQL_PORT']}\n";
    echo "  Database: {$config['PGSQL_DB']}\n";
    echo "  User: {$config['PGSQL_USER']}\n\n";

    echo "1. Création du service de connexion... ";
    $connectionService = ConnectionService::getInstance($config);
    echo "✅\n";

    echo "2. Test de connexion... ";
    if ($connectionService->testConnection()) {
        echo "✅\n";
    } else {
        echo "❌ Échec\n";
        exit(1);
    }

    echo "3. Récupération des informations serveur... ";
    $serverInfo = $connectionService->getServerInfo();
    echo "✅\n";
    
    echo "\n📊 Informations serveur:\n";
    echo "  Version: " . substr($serverInfo['postgresql_version'], 0, 50) . "...\n";
    echo "  Uptime: " . number_format($serverInfo['uptime_seconds']) . " secondes\n";
    echo "  Pool de connexions: {$serverInfo['connection_pool_size']}\n";
    echo "  Connexions actives: {$serverInfo['active_connections']}\n";
    echo "  Total connexions: {$serverInfo['total_connections']}\n";

    echo "\n4. Test de requête simple... ";
    $pdo = $connectionService->getConnection();
    $stmt = $pdo->query("SELECT current_database(), current_user, version()");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $connectionService->releaseConnection($pdo);
    echo "✅\n";
    
    echo "\n📋 Résultat requête:\n";
    echo "  Base de données: {$result['current_database']}\n";
    echo "  Utilisateur: {$result['current_user']}\n";
    echo "  Version complète: " . substr($result['version'], 0, 60) . "...\n";

    echo "\n5. Test des tables créées... ";
    $pdo = $connectionService->getConnection();
    $stmt = $pdo->query("
        SELECT tablename 
        FROM pg_tables 
        WHERE schemaname = 'public' 
        ORDER BY tablename
    ");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $connectionService->releaseConnection($pdo);
    echo "✅\n";
    
    echo "\n📂 Tables disponibles:\n";
    foreach ($tables as $table) {
        echo "  - $table\n";
    }

    echo "\n6. Test des données insérées... ";
    $pdo = $connectionService->getConnection();
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetchColumn();
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $productCount = $stmt->fetchColumn();
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders");
    $orderCount = $stmt->fetchColumn();
    $connectionService->releaseConnection($pdo);
    echo "✅\n";
    
    echo "\n📈 Statistiques:\n";
    echo "  Utilisateurs: $userCount\n";
    echo "  Produits: $productCount\n";
    echo "  Commandes: $orderCount\n";

    echo "\n7. Test des types PostgreSQL (JSONB)... ";
    $pdo = $connectionService->getConnection();
    $stmt = $pdo->query("
        SELECT username, metadata->>'role' as role 
        FROM users 
        WHERE metadata->>'role' = 'admin'
        LIMIT 1
    ");
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    $connectionService->releaseConnection($pdo);
    echo "✅\n";
    
    if ($admin) {
        echo "  Admin trouvé: {$admin['username']} (role: {$admin['role']})\n";
    }

    echo "\n8. Test des types PostgreSQL (Arrays)... ";
    $pdo = $connectionService->getConnection();
    $stmt = $pdo->query("
        SELECT name, tags 
        FROM products 
        WHERE 'computers' = ANY(tags)
        LIMIT 1
    ");
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    $connectionService->releaseConnection($pdo);
    echo "✅\n";
    
    if ($product) {
        $tags = trim($product['tags'], '{}');
        echo "  Produit avec tag 'computers': {$product['name']}\n";
        echo "  Tags: $tags\n";
    }

    echo "\n✅ Tous les tests sont passés avec succès!\n\n";
    
} catch (\Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}