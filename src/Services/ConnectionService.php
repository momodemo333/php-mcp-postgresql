<?php

declare(strict_types=1);

namespace PostgreSqlMcp\Services;

use PostgreSqlMcp\Exceptions\ConnectionException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Service de gestion des connexions PostgreSQL avec pool de connexions
 */
class ConnectionService
{
    private static ?self $instance = null;
    private array $connections = [];
    private array $config;
    private LoggerInterface $logger;
    private int $maxConnections;

    private function __construct(array $config, LoggerInterface $logger = null)
    {
        $this->config = $config;
        $this->logger = $logger ?? new NullLogger();
        $this->maxConnections = (int)($config['CONNECTION_POOL_SIZE'] ?? 5);
    }

    public static function getInstance(array $config = [], LoggerInterface $logger = null): self
    {
        if (self::$instance === null) {
            if (empty($config)) {
                throw new \InvalidArgumentException('Configuration required for first instance');
            }
            self::$instance = new self($config, $logger);
        }
        return self::$instance;
    }

    /**
     * Obtient une connexion PDO du pool
     */
    public function getConnection(): \PDO
    {
        // Cherche une connexion libre existante
        foreach ($this->connections as $key => $connectionData) {
            if (!$connectionData['in_use']) {
                // Vérifie si la connexion est toujours valide
                if ($this->isConnectionAlive($connectionData['pdo'])) {
                    $this->connections[$key]['in_use'] = true;
                    $this->connections[$key]['last_used'] = time();
                    $this->logger->debug('Réutilisation connexion existante', ['connection_id' => $key]);
                    return $connectionData['pdo'];
                } else {
                    // Supprime la connexion morte
                    unset($this->connections[$key]);
                    $this->logger->warning('Connexion morte supprimée du pool', ['connection_id' => $key]);
                }
            }
        }

        // Crée une nouvelle connexion si possible
        if (count($this->connections) < $this->maxConnections) {
            $pdo = $this->createConnection();
            $connectionId = uniqid('conn_');
            
            $this->connections[$connectionId] = [
                'pdo' => $pdo,
                'in_use' => true,
                'created_at' => time(),
                'last_used' => time()
            ];

            $this->logger->info('Nouvelle connexion créée', ['connection_id' => $connectionId]);
            return $pdo;
        }

        throw new ConnectionException('Pool de connexions saturé. Maximum: ' . $this->maxConnections);
    }

    /**
     * Libère une connexion dans le pool
     */
    public function releaseConnection(\PDO $pdo): void
    {
        foreach ($this->connections as $key => $connectionData) {
            if ($connectionData['pdo'] === $pdo) {
                $this->connections[$key]['in_use'] = false;
                $this->logger->debug('Connexion libérée', ['connection_id' => $key]);
                return;
            }
        }
    }

    /**
     * Crée une nouvelle connexion PDO
     */
    private function createConnection(): \PDO
    {
        $host = $this->config['PGSQL_HOST'] ?? 'localhost';
        $port = $this->config['PGSQL_PORT'] ?? 5432;
        $dbname = $this->config['PGSQL_DB'] ?? '';
        $username = $this->config['PGSQL_USER'] ?? 'postgres';
        $password = $this->config['PGSQL_PASS'] ?? '';

        // Construction du DSN PostgreSQL
        $dsn = "pgsql:host={$host};port={$port}";
        if ($dbname) {
            $dsn .= ";dbname={$dbname}";
        }
        // PostgreSQL utilise UTF8 par défaut
        $dsn .= ";options='--client_encoding=UTF8'";

        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
            \PDO::ATTR_TIMEOUT => (int)($this->config['QUERY_TIMEOUT'] ?? 30),
            \PDO::ATTR_PERSISTENT => false,  // Éviter les connexions persistantes
            // PostgreSQL specific: set statement timeout
            \PDO::PGSQL_ATTR_DISABLE_PREPARES => false
        ];

        try {
            $pdo = new \PDO($dsn, $username, $password, $options);
            $this->logger->info('Connexion PostgreSQL établie', [
                'host' => $host,
                'port' => $port,
                'database' => $dbname ?: 'multi-db'
            ]);
            return $pdo;
        } catch (\PDOException $e) {
            $this->logger->error('Échec connexion PostgreSQL', [
                'host' => $host,
                'port' => $port,
                'error' => $e->getMessage()
            ]);
            throw new ConnectionException(
                'Impossible de se connecter à PostgreSQL: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Teste la connexion
     */
    public function testConnection(): bool
    {
        try {
            $pdo = $this->getConnection();
            $stmt = $pdo->query('SELECT 1');
            $result = $stmt->fetch();
            $this->releaseConnection($pdo);
            
            return $result !== false;
        } catch (\Exception $e) {
            $this->logger->error('Test de connexion échoué', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Obtient les informations du serveur MySQL
     */
    public function getServerInfo(): array
    {
        $pdo = $this->getConnection();
        try {
            $version = $pdo->query('SELECT version()')->fetchColumn();
            // PostgreSQL: utilise pg_postmaster_start_time() pour calculer l'uptime
            $startTimeResult = $pdo->query("SELECT EXTRACT(EPOCH FROM (NOW() - pg_postmaster_start_time()))::INT as uptime")->fetch();
            $uptime = $startTimeResult['uptime'] ?? 0;
            
            $this->releaseConnection($pdo);
            
            return [
                'postgresql_version' => $version,
                'uptime_seconds' => (int)$uptime,
                'connection_pool_size' => $this->maxConnections,
                'active_connections' => count(array_filter($this->connections, fn($c) => $c['in_use'])),
                'total_connections' => count($this->connections)
            ];
        } catch (\Exception $e) {
            $this->releaseConnection($pdo);
            throw $e;
        }
    }

    /**
     * Nettoie les connexions inactives
     */
    public function cleanup(): void
    {
        $timeout = 3600; // 1 heure
        $now = time();
        
        foreach ($this->connections as $key => $connectionData) {
            if (!$connectionData['in_use'] && ($now - $connectionData['last_used']) > $timeout) {
                unset($this->connections[$key]);
                $this->logger->info('Connexion inactive nettoyée', ['connection_id' => $key]);
            }
        }
    }

    /**
     * Maintient le pool de connexions en vérifiant périodiquement les connexions inactives
     * (heartbeat pour éviter les connexions zombies)
     */
    public function maintainPool(): void
    {
        foreach ($this->connections as $key => $connectionData) {
            if (!$connectionData['in_use']) {
                if (!$this->isConnectionAlive($connectionData['pdo'])) {
                    unset($this->connections[$key]);
                    $this->logger->debug('Connexion zombie supprimée lors de la maintenance', ['connection_id' => $key]);
                }
            }
        }
    }

    /**
     * Ferme toutes les connexions
     */
    public function closeAll(): void
    {
        $this->connections = [];
        $this->logger->info('Toutes les connexions fermées');
    }

    /**
     * Vérifie si une connexion PDO est toujours vivante
     */
    private function isConnectionAlive(\PDO $pdo): bool
    {
        try {
            $stmt = $pdo->query('SELECT 1');
            return $stmt !== false;
        } catch (\PDOException $e) {
            // PostgreSQL connection errors
            // 57P01: admin_shutdown, 57P02: crash_shutdown, 57P03: cannot_connect_now
            // 08006: connection_failure, 08001: sqlclient_unable_to_establish_sqlconnection
            if (in_array($e->getCode(), ['57P01', '57P02', '57P03', '08006', '08001'])) {
                return false;
            }
            // Pour les autres erreurs, on considère que la connexion est vivante
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Exécute une requête avec retry automatique en cas de connexion fermée
     */
    public function executeWithRetry(callable $callback, int $maxRetries = 2): mixed
    {
        $attempt = 0;
        
        while ($attempt < $maxRetries) {
            try {
                return $callback();
            } catch (\PDOException $e) {
                // PostgreSQL connection lost
                if (in_array($e->getCode(), ['57P01', '57P02', '57P03', '08006', '08001']) && $attempt < $maxRetries - 1) {
                    $this->logger->warning('Connexion PostgreSQL fermée, tentative de reconnexion', [
                        'attempt' => $attempt + 1,
                        'error' => $e->getMessage()
                    ]);
                    
                    // Nettoie les connexions mortes
                    $this->cleanup();
                    $attempt++;
                    continue;
                }
                throw $e;
            }
        }
        
        throw new \RuntimeException('Échec après ' . $maxRetries . ' tentatives');
    }
}