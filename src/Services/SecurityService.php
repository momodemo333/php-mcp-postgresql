<?php

declare(strict_types=1);

namespace MySqlMcp\Services;

use MySqlMcp\Exceptions\SecurityException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Service de validation et sécurité pour les requêtes MySQL
 */
class SecurityService
{
    private array $config;
    private LoggerInterface $logger;
    
    // Mots-clés dangereux bloqués par défaut
    private array $dangerousKeywords = [
        'DROP', 'TRUNCATE', 'DELETE', 'ALTER', 'CREATE', 'GRANT', 'REVOKE',
        'LOAD_FILE', 'INTO OUTFILE', 'INTO DUMPFILE', 'SYSTEM', 'EXEC',
        'SHUTDOWN', 'FLUSH', 'RESET', 'KILL', 'SET PASSWORD'
    ];

    public function __construct(array $config, LoggerInterface $logger = null)
    {
        $this->config = $config;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Valide une requête selon la configuration de sécurité
     */
    public function validateQuery(string $query, string $operation = 'SELECT'): void
    {
        $query = trim($query);
        
        if (empty($query)) {
            throw new SecurityException('Requête vide non autorisée');
        }

        // Vérification des permissions par opération
        $this->checkOperationPermission($operation);
        
        // Vérification des mots-clés dangereux
        if ($this->getBoolConfig('BLOCK_DANGEROUS_KEYWORDS', true)) {
            $this->checkDangerousKeywords($query);
        }
        
        // Vérification des schémas autorisés
        $this->checkAllowedSchemas($query);
        
        // Vérification basique d'injection SQL
        $this->checkSqlInjection($query);
        
        $this->logger->info('Requête validée', [
            'operation' => $operation,
            'query_length' => strlen($query),
            'query_preview' => substr($query, 0, 100) . (strlen($query) > 100 ? '...' : '')
        ]);
    }

    /**
     * Vérifie les permissions pour une opération donnée
     */
    private function checkOperationPermission(string $operation): void
    {
        $permissions = [
            'INSERT' => 'ALLOW_INSERT_OPERATION',
            'UPDATE' => 'ALLOW_UPDATE_OPERATION', 
            'DELETE' => 'ALLOW_DELETE_OPERATION',
            'TRUNCATE' => 'ALLOW_TRUNCATE_OPERATION'
        ];

        if (isset($permissions[$operation])) {
            if (!$this->getBoolConfig($permissions[$operation], false)) {
                throw new SecurityException("Opération {$operation} non autorisée par la configuration");
            }
        }
    }

    /**
     * Vérifie la présence de mots-clés dangereux
     */
    private function checkDangerousKeywords(string $query): void
    {
        $upperQuery = strtoupper($query);
        
        foreach ($this->dangerousKeywords as $keyword) {
            if (strpos($upperQuery, $keyword) !== false) {
                $this->logger->warning('Mot-clé dangereux détecté', [
                    'keyword' => $keyword,
                    'query' => substr($query, 0, 200)
                ]);
                throw new SecurityException("Mot-clé non autorisé détecté: {$keyword}");
            }
        }
    }

    /**
     * Vérifie que la requête respecte les schémas autorisés
     */
    private function checkAllowedSchemas(string $query): void
    {
        $allowedSchemas = $this->getArrayConfig('ALLOWED_SCHEMAS');
        
        if (empty($allowedSchemas)) {
            return; // Tous les schémas autorisés
        }

        // Recherche de noms de bases/tables dans la requête
        if (preg_match_all('/(?:FROM|JOIN|INTO|UPDATE)\s+(?:`?(\w+)`?\.)?`?(\w+)`?/i', $query, $matches)) {
            foreach ($matches[1] as $i => $schema) {
                if ($schema && !in_array($schema, $allowedSchemas)) {
                    throw new SecurityException("Schéma non autorisé: {$schema}");
                }
            }
        }
    }

    /**
     * Détection basique d'injection SQL
     */
    private function checkSqlInjection(string $query): void
    {
        $patterns = [
            '/(\'\s*(OR|AND)\s*\'\s*=\s*\')/i',  // '1'='1'
            '/(\'\s*(OR|AND)\s*1\s*=\s*1)/i',    // ' OR 1=1
            '/(UNION\s+SELECT)/i',                // UNION SELECT
            '/(\/\*.*\*\/)/i',                    // Commentaires SQL
            '/(-{2,})/i',                         // Commentaires --
            '/(;[\s]*DROP|;[\s]*DELETE|;[\s]*INSERT)/i' // Requêtes multiples
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $query)) {
                $this->logger->warning('Potentielle injection SQL détectée', [
                    'pattern' => $pattern,
                    'query' => substr($query, 0, 200)
                ]);
                throw new SecurityException('Pattern d\'injection SQL détecté');
            }
        }
    }

    /**
     * Vérifie la limite de résultats
     */
    public function checkResultLimit(int $rowCount): void
    {
        $maxResults = (int)($this->config['MAX_RESULTS'] ?? 1000);
        
        if ($rowCount > $maxResults) {
            throw new SecurityException("Nombre de résultats dépassé. Maximum: {$maxResults}, demandé: {$rowCount}");
        }
    }

    /**
     * Sanitise une valeur pour l'affichage dans les logs
     */
    public function sanitizeForLog(string $value): string
    {
        // Masque les mots de passe potentiels
        $value = preg_replace('/password\s*=\s*[\'"]([^\'"]+)[\'"]/i', 'password=***', $value);
        $value = preg_replace('/pwd\s*=\s*[\'"]([^\'"]+)[\'"]/i', 'pwd=***', $value);
        
        return $value;
    }

    /**
     * Obtient une configuration booléenne
     */
    private function getBoolConfig(string $key, bool $default = false): bool
    {
        $value = $this->config[$key] ?? $default;
        
        if (is_bool($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            return in_array(strtolower($value), ['true', '1', 'yes', 'on']);
        }
        
        return (bool)$value;
    }

    /**
     * Obtient une configuration de type tableau
     */
    private function getArrayConfig(string $key): array
    {
        $value = $this->config[$key] ?? '';
        
        if (is_array($value)) {
            return $value;
        }
        
        if (is_string($value) && !empty($value)) {
            return array_map('trim', explode(',', $value));
        }
        
        return [];
    }
}