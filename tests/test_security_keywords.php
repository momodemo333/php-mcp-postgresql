#!/usr/bin/env php
<?php
/**
 * Test de la correction de la détection des mots-clés
 * Vérifie que "created_at" n'est plus bloqué incorrectement
 * Issue GitHub: https://github.com/momodemo333/php-mcp-mysql/issues/1
 */

require_once __DIR__ . '/../vendor/autoload.php';

use MySqlMcp\Services\SecurityService;
use MySqlMcp\Exceptions\SecurityException;
use Psr\Log\AbstractLogger;

// Logger simple pour les tests
class TestLogger extends AbstractLogger
{
    public function log($level, $message, array $context = []): void
    {
        // Silencieux pour les tests
    }
}

// Configuration de test
$config = [
    'ALLOW_DDL_OPERATIONS' => false,  // DDL bloqué par défaut
    'BLOCK_DANGEROUS_KEYWORDS' => true,
    'ALLOW_SELECT_OPERATION' => true,
    'ALLOW_INSERT_OPERATION' => true,
    'ALLOW_UPDATE_OPERATION' => true,
    'ALLOW_DELETE_OPERATION' => false,
    'ALLOW_ALL_OPERATIONS' => false,
];

$logger = new TestLogger();
$securityService = new SecurityService($config, $logger);

echo "=== Test de la détection des mots-clés de sécurité ===\n\n";

$testCases = [
    // Cas qui DOIVENT PASSER (ne pas être bloqués)
    'pass' => [
        // Colonnes avec "created_at" et similaires
        "SELECT id, created_at, updated_at FROM users",
        "SELECT * FROM posts WHERE created_at > '2024-01-01'",
        "UPDATE users SET updated_at = NOW() WHERE id = 1",
        "INSERT INTO logs (message, created_at) VALUES ('test', NOW())",
        "SELECT created_at, deleted_at FROM records",
        "SELECT * FROM tables WHERE table_created_at IS NOT NULL",
        "UPDATE posts SET created_by = 'admin', created_at = NOW()",
        "SELECT recreated_at FROM sessions",
        "SELECT altered_at FROM modifications",
        "SELECT dropped_at FROM archived_items",
        
        // Autres cas avec des mots similaires mais pas identiques
        "SELECT creator_id FROM projects",
        "SELECT alternative_email FROM users",
        "SELECT dropdown_value FROM settings",
    ],
    
    // Cas qui DOIVENT ÊTRE BLOQUÉS (vrais mots-clés DDL)
    'block' => [
        "CREATE TABLE users (id INT)",
        "DROP TABLE posts",
        "ALTER TABLE users ADD COLUMN email VARCHAR(255)",
        "CREATE DATABASE test_db",
        "DROP DATABASE old_db",
        "ALTER DATABASE mydb CHARACTER SET utf8mb4",
        "CREATE INDEX idx_name ON users(name)",
        "DROP INDEX idx_email ON users",
        "CREATE VIEW user_view AS SELECT * FROM users",
        "ALTER VIEW user_view AS SELECT id, name FROM users",
        
        // Mots-clés dangereux
        "GRANT ALL ON *.* TO 'user'@'localhost'",
        "REVOKE SELECT ON db.* FROM 'user'@'localhost'",
        "SELECT * FROM users INTO OUTFILE '/tmp/users.csv'",
        "LOAD DATA INFILE '/tmp/data.csv' INTO TABLE users",
        "SHUTDOWN",
        "KILL 123",
    ]
];

$passCount = 0;
$failCount = 0;

// Test des requêtes qui doivent passer
echo "1. Test des requêtes qui DOIVENT ÊTRE AUTORISÉES:\n";
echo str_repeat("-", 60) . "\n";

foreach ($testCases['pass'] as $query) {
    try {
        $securityService->validateQuery($query, 'SELECT');
        echo "✅ PASS: " . substr($query, 0, 50) . "...\n";
        $passCount++;
    } catch (SecurityException $e) {
        echo "❌ FAIL: " . substr($query, 0, 50) . "...\n";
        echo "   Erreur: " . $e->getMessage() . "\n";
        $failCount++;
    }
}

echo "\n2. Test des requêtes qui DOIVENT ÊTRE BLOQUÉES:\n";
echo str_repeat("-", 60) . "\n";

foreach ($testCases['block'] as $query) {
    try {
        $securityService->validateQuery($query, 'SELECT');
        echo "❌ FAIL: " . substr($query, 0, 50) . "... (aurait dû être bloqué)\n";
        $failCount++;
    } catch (SecurityException $e) {
        echo "✅ PASS: " . substr($query, 0, 50) . "... (bloqué correctement)\n";
        $passCount++;
    }
}

// Test avec DDL autorisé
echo "\n3. Test avec ALLOW_DDL_OPERATIONS=true:\n";
echo str_repeat("-", 60) . "\n";

$configWithDDL = array_merge($config, ['ALLOW_DDL_OPERATIONS' => true]);
$securityServiceWithDDL = new SecurityService($configWithDDL, $logger);

$ddlQueries = [
    "CREATE TABLE test (id INT)",
    "ALTER TABLE test ADD COLUMN name VARCHAR(100)",
    "DROP TABLE test",
];

foreach ($ddlQueries as $query) {
    try {
        $securityServiceWithDDL->validateQuery($query, 'SELECT');
        echo "✅ PASS: DDL autorisé - " . substr($query, 0, 40) . "...\n";
        $passCount++;
    } catch (SecurityException $e) {
        echo "❌ FAIL: DDL devrait être autorisé - " . substr($query, 0, 40) . "...\n";
        $failCount++;
    }
}

// Résumé
echo "\n" . str_repeat("=", 60) . "\n";
echo "RÉSUMÉ DES TESTS:\n";
echo str_repeat("=", 60) . "\n";
echo "Tests réussis: {$passCount}\n";
echo "Tests échoués: {$failCount}\n";

if ($failCount === 0) {
    echo "\n🎉 TOUS LES TESTS SONT PASSÉS AVEC SUCCÈS!\n";
    echo "Le problème de 'created_at' est résolu.\n";
    exit(0);
} else {
    echo "\n⚠️  CERTAINS TESTS ONT ÉCHOUÉ\n";
    echo "Veuillez vérifier les erreurs ci-dessus.\n";
    exit(1);
}