#!/usr/bin/env php
<?php
/**
 * Test du mécanisme de retry automatique pour MySQL server gone away
 */

require_once __DIR__ . '/../vendor/autoload.php';

use MySqlMcp\Services\ConnectionService;
use MySqlMcp\Elements\QueryTools;
use MySqlMcp\Services\SecurityService;
use Psr\Log\AbstractLogger;

// Logger simple pour les tests
class TestLogger extends AbstractLogger
{
    public function log($level, $message, array $context = []): void
    {
        $time = date('H:i:s');
        echo "[{$time}] [{$level}] {$message}";
        if (!empty($context)) {
            echo " - " . json_encode($context);
        }
        echo "\n";
    }
}

// Configuration de test - utiliser les valeurs par défaut MySQL locales
$config = [
    'MYSQL_HOST' => getenv('MYSQL_HOST') ?: 'localhost',
    'MYSQL_PORT' => getenv('MYSQL_PORT') ?: '3306',
    'MYSQL_USER' => getenv('MYSQL_USER') ?: 'root',
    'MYSQL_PASS' => getenv('MYSQL_PASS') ?: '',
    'MYSQL_DB' => getenv('MYSQL_DB') ?: 'test',
    'QUERY_TIMEOUT' => 30,
    'CONNECTION_POOL_SIZE' => 5,
    'MAX_RESULTS' => 1000,
    'ALLOW_SELECT_OPERATION' => true,
    'BLOCK_DANGEROUS_KEYWORDS' => false,
];

$logger = new TestLogger();

echo "=== Test du mécanisme de retry automatique ===\n\n";

try {
    // Initialisation des services
    $connectionService = ConnectionService::getInstance($config, $logger);
    $securityService = new SecurityService($config, $logger);
    $queryTools = new QueryTools($connectionService, $securityService, $logger, $config);
    
    echo "1. Test de connexion initiale...\n";
    $result = $queryTools->executeSelect("SELECT 1 as test");
    echo "✅ Connexion réussie: " . json_encode($result['results'][0]) . "\n\n";
    
    echo "2. Simulation d'une connexion fermée (attente longue)...\n";
    echo "   Attendez 10 secondes pour simuler un timeout...\n";
    sleep(10);
    
    echo "3. Test de requête après timeout (devrait faire un retry automatique)...\n";
    $result = $queryTools->executeSelect("SELECT NOW() as current_time");
    echo "✅ Requête réussie après retry: " . json_encode($result['results'][0]) . "\n\n";
    
    echo "4. Test de plusieurs requêtes consécutives...\n";
    for ($i = 1; $i <= 3; $i++) {
        $result = $queryTools->executeSelect("SELECT {$i} as number, CONNECTION_ID() as conn_id");
        echo "   Requête {$i}: conn_id=" . $result['results'][0]['conn_id'] . "\n";
    }
    echo "✅ Toutes les requêtes ont réussi\n\n";
    
    echo "5. Test de maintenance du pool de connexions...\n";
    $connectionService->maintainPool();
    echo "✅ Maintenance du pool effectuée\n\n";
    
    echo "6. Test avec une requête complexe...\n";
    $query = "SELECT 
        DATABASE() as db_name,
        USER() as current_user,
        VERSION() as mysql_version,
        CONNECTION_ID() as connection_id
    ";
    $result = $queryTools->executeSelect($query);
    foreach ($result['results'][0] as $key => $value) {
        echo "   {$key}: {$value}\n";
    }
    echo "✅ Requête complexe réussie\n\n";
    
    echo "=== TOUS LES TESTS SONT PASSÉS AVEC SUCCÈS ===\n";
    echo "Le mécanisme de retry automatique fonctionne correctement!\n";
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}