<?php

echo "🔍 Debug des variables d'environnement\n";
echo "=====================================\n\n";

echo "Variables d'environnement reçues :\n";
foreach (['MYSQL_HOST', 'MYSQL_PORT', 'MYSQL_USER', 'MYSQL_PASS', 'MYSQL_DB'] as $var) {
    $envValue = $_ENV[$var] ?? 'NON DÉFINIE';
    $getenvValue = getenv($var) ?: 'NON DÉFINIE';
    echo "  $var : \$_ENV='$envValue' | getenv()='$getenvValue'\n";
}

echo "\nChargement configuration MySqlServer...\n";
require_once __DIR__ . '/vendor/autoload.php';

use MySqlMcp\MySqlServer;

$server = new MySqlServer();
$config = $server->getConfig();

echo "Configuration chargée :\n";
foreach (['MYSQL_HOST', 'MYSQL_PORT', 'MYSQL_USER', 'MYSQL_PASS', 'MYSQL_DB'] as $var) {
    $value = $config[$var] ?? 'NON DÉFINIE';
    echo "  $var : '$value'\n";
}