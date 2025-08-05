<?php

declare(strict_types=1);

namespace MySqlMcp;

use MySqlMcp\Services\ConnectionService;
use MySqlMcp\Services\SecurityService;
use MySqlMcp\Elements\DatabaseTools;
use MySqlMcp\Elements\QueryTools;
use PhpMcp\Server\Server;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Configuration principale du serveur MCP MySQL
 */
class MySqlServer
{
    private array $config;
    private LoggerInterface $logger;

    public function __construct(array $config = [], LoggerInterface $logger = null)
    {
        $this->config = $this->loadConfiguration($config);
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Crée et configure le serveur MCP
     */
    public function createServer(): Server
    {
        // Création des services
        $connectionService = ConnectionService::getInstance($this->config, $this->logger);
        $securityService = new SecurityService($this->config, $this->logger);

        // Création des éléments MCP
        $databaseTools = new DatabaseTools($connectionService, $securityService, $this->logger);
        $queryTools = new QueryTools($connectionService, $securityService, $this->logger, $this->config);

        // Configuration du serveur
        $server = Server::make()
            ->withServerInfo('MySQL MCP Server', '1.0.0')
            ->withLogger($this->logger)
            
            // Enregistrement manuel des outils (plus de contrôle)
            ->withTool([$databaseTools, 'listDatabases'])
            ->withTool([$databaseTools, 'listTables'])
            ->withTool([$databaseTools, 'describeTable'])
            ->withTool([$databaseTools, 'getServerStatus'])
            
            ->withTool([$queryTools, 'executeSelect'])
            ->withTool([$queryTools, 'executeInsert'])
            ->withTool([$queryTools, 'executeUpdate'])
            ->withTool([$queryTools, 'executeDelete'])
            ->withTool([$queryTools, 'executeCustomQuery'])
            
            // Ressources MCP pour l'introspection
            ->withResource(
                function() use ($connectionService): array {
                    return $connectionService->getServerInfo();
                },
                uri: 'mysql://connection/status',
                mimeType: 'application/json'
            )
            
            ->withResource(
                function(): array {
                    return [
                        'server_version' => '1.0.0',
                        'supported_operations' => [
                            'SELECT' => true,
                            'INSERT' => $this->getBoolConfig('ALLOW_INSERT_OPERATION'),
                            'UPDATE' => $this->getBoolConfig('ALLOW_UPDATE_OPERATION'),
                            'DELETE' => $this->getBoolConfig('ALLOW_DELETE_OPERATION'),
                        ],
                        'security_features' => [
                            'query_validation' => true,
                            'prepared_statements' => $this->getBoolConfig('ENABLE_PREPARED_STATEMENTS'),
                            'result_limits' => (int)($this->config['MAX_RESULTS'] ?? 1000),
                            'query_timeout' => (int)($this->config['QUERY_TIMEOUT'] ?? 30)
                        ]
                    ];
                },
                uri: 'mysql://server/capabilities',
                mimeType: 'application/json'
            )
            
            ->build();

        $this->logger->info('Serveur MCP MySQL configuré', [
            'server_version' => '1.0.0',
            'mysql_host' => $this->config['MYSQL_HOST'] ?? 'localhost',
            'mysql_port' => $this->config['MYSQL_PORT'] ?? 3306,
            'database' => $this->config['MYSQL_DB'] ?: 'multi-db',
            'security_enabled' => true
        ]);

        return $server;
    }

    /**
     * Charge la configuration depuis les variables d'environnement et fichier .env
     */
    private function loadConfiguration(array $override = []): array
    {
        // Charge le fichier .env s'il existe
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
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
                    
                    if (!isset($_ENV[$key])) {
                        $_ENV[$key] = $value;
                    }
                }
            }
        }

        // Configuration par défaut
        $config = [
            'MYSQL_HOST' => getenv('MYSQL_HOST') ?: ($_ENV['MYSQL_HOST'] ?? 'localhost'),
            'MYSQL_PORT' => (int)(getenv('MYSQL_PORT') ?: ($_ENV['MYSQL_PORT'] ?? 3306)),
            'MYSQL_USER' => getenv('MYSQL_USER') ?: ($_ENV['MYSQL_USER'] ?? 'root'),
            'MYSQL_PASS' => getenv('MYSQL_PASS') ?: ($_ENV['MYSQL_PASS'] ?? ''),
            'MYSQL_DB' => getenv('MYSQL_DB') ?: ($_ENV['MYSQL_DB'] ?? ''),
            
            'ALLOW_INSERT_OPERATION' => getenv('ALLOW_INSERT_OPERATION') ?: ($_ENV['ALLOW_INSERT_OPERATION'] ?? 'false'),
            'ALLOW_UPDATE_OPERATION' => getenv('ALLOW_UPDATE_OPERATION') ?: ($_ENV['ALLOW_UPDATE_OPERATION'] ?? 'false'),
            'ALLOW_DELETE_OPERATION' => getenv('ALLOW_DELETE_OPERATION') ?: ($_ENV['ALLOW_DELETE_OPERATION'] ?? 'false'),
            'ALLOW_TRUNCATE_OPERATION' => getenv('ALLOW_TRUNCATE_OPERATION') ?: ($_ENV['ALLOW_TRUNCATE_OPERATION'] ?? 'false'),
            
            'QUERY_TIMEOUT' => (int)(getenv('QUERY_TIMEOUT') ?: ($_ENV['QUERY_TIMEOUT'] ?? 30)),
            'MAX_RESULTS' => (int)(getenv('MAX_RESULTS') ?: ($_ENV['MAX_RESULTS'] ?? 1000)),
            'ALLOWED_SCHEMAS' => getenv('ALLOWED_SCHEMAS') ?: ($_ENV['ALLOWED_SCHEMAS'] ?? ''),
            'BLOCK_DANGEROUS_KEYWORDS' => getenv('BLOCK_DANGEROUS_KEYWORDS') ?: ($_ENV['BLOCK_DANGEROUS_KEYWORDS'] ?? 'true'),
            'ENABLE_QUERY_LOGGING' => getenv('ENABLE_QUERY_LOGGING') ?: ($_ENV['ENABLE_QUERY_LOGGING'] ?? 'true'),
            
            'CONNECTION_POOL_SIZE' => (int)(getenv('CONNECTION_POOL_SIZE') ?: ($_ENV['CONNECTION_POOL_SIZE'] ?? 5)),
            'CACHE_TTL' => (int)(getenv('CACHE_TTL') ?: ($_ENV['CACHE_TTL'] ?? 300)),
            'ENABLE_QUERY_CACHE' => getenv('ENABLE_QUERY_CACHE') ?: ($_ENV['ENABLE_QUERY_CACHE'] ?? 'true'),
            
            'ENABLE_TRANSACTIONS' => getenv('ENABLE_TRANSACTIONS') ?: ($_ENV['ENABLE_TRANSACTIONS'] ?? 'true'),
            'ENABLE_PREPARED_STATEMENTS' => getenv('ENABLE_PREPARED_STATEMENTS') ?: ($_ENV['ENABLE_PREPARED_STATEMENTS'] ?? 'true'),
            'ENABLE_SCHEMA_INTROSPECTION' => getenv('ENABLE_SCHEMA_INTROSPECTION') ?: ($_ENV['ENABLE_SCHEMA_INTROSPECTION'] ?? 'true'),
            'ENABLE_EXPORT_TOOLS' => getenv('ENABLE_EXPORT_TOOLS') ?: ($_ENV['ENABLE_EXPORT_TOOLS'] ?? 'true'),
            
            'LOG_LEVEL' => getenv('LOG_LEVEL') ?: ($_ENV['LOG_LEVEL'] ?? 'INFO'),
            'LOG_FILE' => getenv('LOG_FILE') ?: ($_ENV['LOG_FILE'] ?? '')
        ];

        // Applique les surcharges
        return array_merge($config, $override);
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
     * Teste la connexion MySQL avec la configuration actuelle
     */
    public function testConnection(): bool
    {
        try {
            $connectionService = ConnectionService::getInstance($this->config, $this->logger);
            return $connectionService->testConnection();
        } catch (\Exception $e) {
            $this->logger->error('Test de connexion échoué', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Obtient la configuration actuelle (sans mots de passe)
     */
    public function getConfig(): array
    {
        $safeConfig = $this->config;
        $safeConfig['MYSQL_PASS'] = '***';
        return $safeConfig;
    }
}