<?php
/**
 * Script de diagnostic MCP Session
 * Usage: php debug_mcp_session.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use MySqlMcp\MySqlServer;
use PhpMcp\Server\Transports\StdioServerTransport;

echo "=== PHP MCP MySQL - Diagnostic Session ===\n";
echo "Version PHP: " . phpversion() . "\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";

// Test 1: Configuration des variables d'environnement
echo "\n1. Variables d'environnement:\n";
$requiredVars = ['MYSQL_HOST', 'MYSQL_USER', 'MYSQL_PASS', 'MYSQL_DB', 'MYSQL_PORT'];
foreach ($requiredVars as $var) {
    $value = getenv($var) ?: $_ENV[$var] ?? 'NON_DEFINIE';
    $masked = in_array($var, ['MYSQL_PASS']) ? str_repeat('*', min(8, strlen((string)$value))) : $value;
    echo "  {$var}: {$masked}\n";
}

// Test 2: Connexion MySQL directe
echo "\n2. Test connexion MySQL:\n";
try {
    $host = getenv('MYSQL_HOST') ?: $_ENV['MYSQL_HOST'] ?? 'localhost';
    $port = getenv('MYSQL_PORT') ?: $_ENV['MYSQL_PORT'] ?? '3306';
    $user = getenv('MYSQL_USER') ?: $_ENV['MYSQL_USER'] ?? 'root';
    $pass = getenv('MYSQL_PASS') ?: $_ENV['MYSQL_PASS'] ?? '';
    $db = getenv('MYSQL_DB') ?: $_ENV['MYSQL_DB'] ?? 'test';

    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10,
    ]);
    
    $stmt = $pdo->query('SELECT VERSION() as version, NOW() as now');
    $result = $stmt->fetch();
    echo "  ✅ Connexion réussie\n";
    echo "  MySQL Version: {$result['version']}\n";
    echo "  Timestamp: {$result['now']}\n";
    
} catch (Exception $e) {
    echo "  ❌ Erreur connexion: {$e->getMessage()}\n";
    exit(1);
}

// Test 3: Initialisation du serveur MCP
echo "\n3. Test serveur MCP:\n";
try {
    $server = new MySqlServer();
    echo "  ✅ MySqlServer initialisé\n";
    
    // Test configuration
    $reflection = new ReflectionClass($server);
    if ($reflection->hasProperty('config')) {
        $configProp = $reflection->getProperty('config');
        $configProp->setAccessible(true);
        $config = $configProp->getValue($server);
        echo "  Configuration chargée: " . count($config) . " paramètres\n";
    }
    
} catch (Exception $e) {
    echo "  ❌ Erreur serveur MCP: {$e->getMessage()}\n";
    exit(1);
}

// Test 4: Vérification des timeouts
echo "\n4. Configuration timeouts:\n";
$queryTimeout = getenv('QUERY_TIMEOUT') ?: $_ENV['QUERY_TIMEOUT'] ?? 30;
echo "  QUERY_TIMEOUT: {$queryTimeout}s\n";

$phpTimeout = ini_get('max_execution_time');
echo "  PHP max_execution_time: {$phpTimeout}s\n";

$pdoTimeout = 10; // Défini dans la connexion PDO
echo "  PDO timeout: {$pdoTimeout}s\n";

echo "\n5. Recommandations:\n";
if ($queryTimeout > 30) {
    echo "  ⚠️  QUERY_TIMEOUT élevé ({$queryTimeout}s) - risque de timeout MCP\n";
}
if ($phpTimeout > 0 && $phpTimeout < $queryTimeout * 2) {
    echo "  ⚠️  PHP timeout trop bas - augmenter max_execution_time\n";
}

echo "\n=== Diagnostic terminé ===\n";
echo "Pour résoudre les erreurs de session MCP:\n";
echo "1. Redémarrer Claude Code\n";
echo "2. Vérifier la configuration MCP dans .claude.json\n";
echo "3. Réduire QUERY_TIMEOUT si > 30s\n";
echo "4. Vérifier la stabilité de la connexion réseau\n";