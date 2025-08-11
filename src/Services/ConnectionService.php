<?php

declare(strict_types=1);

namespace MySqlMcp\Services;

use MySqlMcp\Exceptions\ConnectionException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Service de gestion des connexions MySQL avec pool de connexions
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
        $host = $this->config['MYSQL_HOST'] ?? 'localhost';
        $port = $this->config['MYSQL_PORT'] ?? 3306;
        $dbname = $this->config['MYSQL_DB'] ?? '';
        $username = $this->config['MYSQL_USER'] ?? 'root';
        $password = $this->config['MYSQL_PASS'] ?? '';

        // Construction du DSN avec TCP explicite
        $dsn = "mysql:host={$host};port={$port}";
        if ($dbname) {
            $dsn .= ";dbname={$dbname}";
        }
        
        // Force TCP connection pour éviter les problèmes de socket Unix
        if ($host === 'localhost' || $host === '127.0.0.1') {
            $dsn .= ";charset=utf8mb4";
        }

        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
            \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            \PDO::ATTR_TIMEOUT => (int)($this->config['QUERY_TIMEOUT'] ?? 30),
            // Options pour éviter "MySQL server has gone away"
            \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
            \PDO::MYSQL_ATTR_FOUND_ROWS => true,
        ];

        try {
            $pdo = new \PDO($dsn, $username, $password, $options);
            $this->logger->info('Connexion MySQL établie', [
                'host' => $host,
                'port' => $port,
                'database' => $dbname ?: 'multi-db'
            ]);
            return $pdo;
        } catch (\PDOException $e) {
            $this->logger->error('Échec connexion MySQL', [
                'host' => $host,
                'port' => $port,
                'error' => $e->getMessage()
            ]);
            throw new ConnectionException(
                'Impossible de se connecter à MySQL: ' . $e->getMessage(),
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
            $version = $pdo->query('SELECT VERSION() as version')->fetch()['version'];
            $uptime = $pdo->query("SHOW STATUS LIKE 'Uptime'")->fetch()['Value'];
            
            $this->releaseConnection($pdo);
            
            return [
                'mysql_version' => $version,
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
            // MySQL server has gone away ou autres erreurs de connexion
            if ($e->getCode() == 2006 || $e->getCode() == 2013) {
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
                // MySQL server has gone away
                if (($e->getCode() == 2006 || $e->getCode() == 2013) && $attempt < $maxRetries - 1) {
                    $this->logger->warning('Connexion MySQL fermée, tentative de reconnexion', [
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