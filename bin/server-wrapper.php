#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Wrapper pour charger un fichier .env spécifique selon le projet
 * Usage: php server-wrapper.php [env-file-path]
 */

// Chemin du fichier .env passé en argument
$envFile = $argv[1] ?? null;

if ($envFile && file_exists($envFile)) {
    // Charge le fichier .env spécifique
    $envContent = file_get_contents($envFile);
    $lines = explode("\n", $envContent);
    
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
        }
    }
    
    fwrite(STDERR, "Loaded environment from: {$envFile}\n");
} else {
    fwrite(STDERR, "Warning: No environment file specified or file not found\n");
}

// Inclut le serveur principal
require_once __DIR__ . '/server.php';