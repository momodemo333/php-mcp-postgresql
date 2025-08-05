<?php

require_once __DIR__ . '/vendor/autoload.php';

echo "🔍 Test de connexion MySQL...\n";

$config = [
    'MYSQL_HOST' => '127.0.0.1',
    'MYSQL_PORT' => 33099,
    'MYSQL_USER' => 'mcpusertest',
    'MYSQL_PASS' => 'tototugoi',
    'MYSQL_DB' => 'mcptesttable'
];

try {
    $dsn = "mysql:host={$config['MYSQL_HOST']};port={$config['MYSQL_PORT']};dbname={$config['MYSQL_DB']};charset=utf8mb4";
    echo "DSN: {$dsn}\n";
    
    $pdo = new PDO(
        $dsn,
        $config['MYSQL_USER'],
        $config['MYSQL_PASS'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    
    echo "✅ Connexion PDO réussie!\n";
    
    $stmt = $pdo->query('SELECT VERSION() as version');
    $result = $stmt->fetch();
    echo "MySQL version: " . $result['version'] . "\n";
    
    $stmt = $pdo->query('SELECT DATABASE() as current_db');
    $result = $stmt->fetch();
    echo "Base de données courante: " . $result['current_db'] . "\n";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
}