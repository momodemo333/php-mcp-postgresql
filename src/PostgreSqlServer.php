<?php

declare(strict_types=1);

namespace PostgreSqlMcp;

use PostgreSqlMcp\Services\ConnectionService;
use PostgreSqlMcp\Services\SecurityService;
use PostgreSqlMcp\Elements\DatabaseTools;
use PostgreSqlMcp\Elements\QueryTools;
use PhpMcp\Server\Server;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\Container\ContainerInterface;

/**
 * Configuration principale du serveur MCP PostgreSQL
 */
class PostgreSqlServer
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

        // Container DI simple pour les dépendances
        $container = new class($connectionService, $securityService, $this->logger, $this->config) implements ContainerInterface {
            public function __construct(
                private ConnectionService $connectionService,
                private SecurityService $securityService,
                private LoggerInterface $logger,
                private array $config
            ) {}

            public function get(string $id): mixed
            {
                return match ($id) {
                    DatabaseTools::class => new DatabaseTools($this->connectionService, $this->securityService, $this->logger),
                    QueryTools::class => new QueryTools($this->connectionService, $this->securityService, $this->logger, $this->config),
                    default => throw new \InvalidArgumentException("Service '$id' not found")
                };
            }

            public function has(string $id): bool
            {
                return in_array($id, [DatabaseTools::class, QueryTools::class]);
            }
        };

        // Configuration du serveur
        $server = Server::make()
            ->withServerInfo('PostgreSQL MCP Server', '1.0.0')
            ->withLogger($this->logger)
            ->withContainer($container)
            
            // Enregistrement manuel des outils (plus de contrôle)
            ->withTool([DatabaseTools::class, 'listDatabases'])
            ->withTool([DatabaseTools::class, 'listTables'])
            ->withTool([DatabaseTools::class, 'describeTable'])
            ->withTool([DatabaseTools::class, 'getServerStatus'])
            
            ->withTool([QueryTools::class, 'executeSelect'])
            ->withTool([QueryTools::class, 'executeInsert'])
            ->withTool([QueryTools::class, 'executeUpdate'])
            ->withTool([QueryTools::class, 'executeDelete'])
            ->withTool([QueryTools::class, 'executeCustomQuery'])
            
            // Ressources MCP pour l'introspection
            ->withResource(
                function() use ($connectionService): array {
                    return $connectionService->getServerInfo();
                },
                uri: 'postgresql://connection/status',
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
                uri: 'postgresql://server/capabilities',
                mimeType: 'application/json'
            )
            
            ->build();

        $this->logger->info('Serveur MCP PostgreSQL configuré', [
            'server_version' => '1.0.0',
            'pgsql_host' => $this->config['PGSQL_HOST'] ?? 'localhost',
            'pgsql_port' => $this->config['PGSQL_PORT'] ?? 5432,
            'database' => $this->config['PGSQL_DB'] ?: 'multi-db',
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
            'PGSQL_HOST' => getenv('PGSQL_HOST') ?: ($_ENV['PGSQL_HOST'] ?? 'localhost'),
            'PGSQL_PORT' => (int)(getenv('PGSQL_PORT') ?: ($_ENV['PGSQL_PORT'] ?? 5432)),
            'PGSQL_USER' => getenv('PGSQL_USER') ?: ($_ENV['PGSQL_USER'] ?? 'postgres'),
            'PGSQL_PASS' => getenv('PGSQL_PASS') ?: ($_ENV['PGSQL_PASS'] ?? ''),
            'PGSQL_DB' => getenv('PGSQL_DB') ?: ($_ENV['PGSQL_DB'] ?? ''),
            
            'ALLOW_INSERT_OPERATION' => getenv('ALLOW_INSERT_OPERATION') ?: ($_ENV['ALLOW_INSERT_OPERATION'] ?? 'false'),
            'ALLOW_UPDATE_OPERATION' => getenv('ALLOW_UPDATE_OPERATION') ?: ($_ENV['ALLOW_UPDATE_OPERATION'] ?? 'false'),
            'ALLOW_DELETE_OPERATION' => getenv('ALLOW_DELETE_OPERATION') ?: ($_ENV['ALLOW_DELETE_OPERATION'] ?? 'false'),
            'ALLOW_TRUNCATE_OPERATION' => getenv('ALLOW_TRUNCATE_OPERATION') ?: ($_ENV['ALLOW_TRUNCATE_OPERATION'] ?? 'false'),
            
            'QUERY_TIMEOUT' => (int)(getenv('QUERY_TIMEOUT') ?: ($_ENV['QUERY_TIMEOUT'] ?? 30)),
            'MAX_RESULTS' => (int)(getenv('MAX_RESULTS') ?: ($_ENV['MAX_RESULTS'] ?? 1000)),
            'ALLOWED_SCHEMAS' => getenv('ALLOWED_SCHEMAS') ?: ($_ENV['ALLOWED_SCHEMAS'] ?? ''),
            'BLOCK_DANGEROUS_KEYWORDS' => getenv('BLOCK_DANGEROUS_KEYWORDS') ?: ($_ENV['BLOCK_DANGEROUS_KEYWORDS'] ?? 'true'),
            
            'CONNECTION_POOL_SIZE' => (int)(getenv('CONNECTION_POOL_SIZE') ?: ($_ENV['CONNECTION_POOL_SIZE'] ?? 5)),
            'ENABLE_PREPARED_STATEMENTS' => getenv('ENABLE_PREPARED_STATEMENTS') ?: ($_ENV['ENABLE_PREPARED_STATEMENTS'] ?? 'true'),
            
            'LOG_LEVEL' => getenv('LOG_LEVEL') ?: ($_ENV['LOG_LEVEL'] ?? 'INFO')
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
        $safeConfig['PGSQL_PASS'] = '***';
        return $safeConfig;
    }
}