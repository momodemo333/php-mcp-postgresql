<?php

declare(strict_types=1);

namespace MySqlMcp\Elements;

use MySqlMcp\Services\ConnectionService;
use MySqlMcp\Services\SecurityService;
use MySqlMcp\Exceptions\MySqlMcpException;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Psr\Log\LoggerInterface;

/**
 * Outils MCP pour la gestion des bases de données et tables
 */
class DatabaseTools
{
    private ConnectionService $connectionService;
    private SecurityService $securityService;
    private LoggerInterface $logger;

    public function __construct(
        ConnectionService $connectionService,
        SecurityService $securityService,
        LoggerInterface $logger
    ) {
        $this->connectionService = $connectionService;
        $this->securityService = $securityService;
        $this->logger = $logger;
    }

    /**
     * Liste toutes les bases de données disponibles
     */
    #[McpTool(name: 'mysql_list_databases')]
    public function listDatabases(): array
    {
        $pdo = $this->connectionService->getConnection();
        
        try {
            $stmt = $pdo->query('SHOW DATABASES');
            $databases = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            
            // Filtre les bases système par défaut
            $systemDatabases = ['information_schema', 'performance_schema', 'mysql', 'sys'];
            $userDatabases = array_diff($databases, $systemDatabases);
            
            $this->logger->info('Bases de données listées', [
                'total_count' => count($databases),
                'user_count' => count($userDatabases)
            ]);
            
            return [
                'databases' => array_values($userDatabases),
                'system_databases' => $systemDatabases,
                'total_count' => count($databases)
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du listage des bases', ['error' => $e->getMessage()]);
            throw new MySqlMcpException('Impossible de lister les bases de données: ' . $e->getMessage());
        } finally {
            $this->connectionService->releaseConnection($pdo);
        }
    }

    /**
     * Liste toutes les tables d'une base de données
     */
    #[McpTool(name: 'mysql_list_tables')]
    public function listTables(
        #[Schema(type: 'string', description: 'Nom de la base de données (optionnel en mode multi-DB)')]
        ?string $database = null
    ): array {
        $pdo = $this->connectionService->getConnection();
        
        try {
            if ($database) {
                $stmt = $pdo->prepare('SHOW TABLES FROM `' . $database . '`');
                $stmt->execute();
            } else {
                $stmt = $pdo->query('SHOW TABLES');
            }
            
            $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            
            // Récupère des infos additionnelles sur chaque table
            $tableDetails = [];
            foreach ($tables as $table) {
                $tableInfo = $this->getTableInfo($pdo, $table, $database);
                $tableDetails[] = $tableInfo;
            }
            
            $this->logger->info('Tables listées', [
                'database' => $database ?: 'current',
                'table_count' => count($tables)
            ]);
            
            return [
                'database' => $database,
                'tables' => $tableDetails,
                'table_count' => count($tables)
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du listage des tables', [
                'database' => $database,
                'error' => $e->getMessage()
            ]);
            throw new MySqlMcpException('Impossible de lister les tables: ' . $e->getMessage());
        } finally {
            $this->connectionService->releaseConnection($pdo);
        }
    }

    /**
     * Décrit la structure d'une table
     */
    #[McpTool(name: 'mysql_describe_table')]
    public function describeTable(
        #[Schema(type: 'string', description: 'Nom de la table')]
        string $table,
        
        #[Schema(type: 'string', description: 'Nom de la base de données (optionnel)')]
        ?string $database = null
    ): array {
        $pdo = $this->connectionService->getConnection();
        
        try {
            // Construction de la requête DESCRIBE
            $query = $database ? "DESCRIBE `{$database}`.`{$table}`" : "DESCRIBE `{$table}`";
            $stmt = $pdo->query($query);
            $columns = $stmt->fetchAll();
            
            // Récupère les index
            $indexQuery = $database ? "SHOW INDEX FROM `{$database}`.`{$table}`" : "SHOW INDEX FROM `{$table}`";
            $indexStmt = $pdo->query($indexQuery);
            $indexes = $indexStmt->fetchAll();
            
            // Récupère les contraintes (foreign keys)
            $constraintsQuery = "
                SELECT 
                    CONSTRAINT_NAME,
                    COLUMN_NAME,
                    REFERENCED_TABLE_SCHEMA,
                    REFERENCED_TABLE_NAME,
                    REFERENCED_COLUMN_NAME
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = COALESCE(?, DATABASE()) 
                AND TABLE_NAME = ?
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ";
            
            $constraintsStmt = $pdo->prepare($constraintsQuery);
            $constraintsStmt->execute([$database, $table]);
            $foreignKeys = $constraintsStmt->fetchAll();
            
            $this->logger->info('Structure de table décrite', [
                'table' => $table,
                'database' => $database,
                'column_count' => count($columns),
                'index_count' => count($indexes),
                'foreign_key_count' => count($foreignKeys)
            ]);
            
            return [
                'table' => $table,
                'database' => $database,
                'columns' => $columns,
                'indexes' => $this->groupIndexes($indexes),
                'foreign_keys' => $foreignKeys,
                'column_count' => count($columns)
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la description de table', [
                'table' => $table,
                'database' => $database,
                'error' => $e->getMessage()
            ]);
            throw new MySqlMcpException('Impossible de décrire la table: ' . $e->getMessage());
        } finally {
            $this->connectionService->releaseConnection($pdo);
        }
    }

    /**
     * Obtient le statut du serveur MySQL
     */
    #[McpTool(name: 'mysql_server_status')]
    public function getServerStatus(): array
    {
        try {
            $serverInfo = $this->connectionService->getServerInfo();
            
            $pdo = $this->connectionService->getConnection();
            
            // Récupère des statistiques supplémentaires
            $statusStmt = $pdo->query("SHOW STATUS WHERE Variable_name IN ('Connections', 'Queries', 'Uptime', 'Threads_connected')");
            $statusData = [];
            while ($row = $statusStmt->fetch()) {
                $statusData[$row['Variable_name']] = $row['Value'];
            }
            
            $this->connectionService->releaseConnection($pdo);
            
            $result = array_merge($serverInfo, [
                'mysql_connections' => (int)($statusData['Connections'] ?? 0),
                'mysql_queries' => (int)($statusData['Queries'] ?? 0),
                'mysql_threads_connected' => (int)($statusData['Threads_connected'] ?? 0),
                'connection_test' => $this->connectionService->testConnection()
            ]);
            
            $this->logger->info('Statut serveur récupéré', $result);
            
            return $result;
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur récupération statut serveur', ['error' => $e->getMessage()]);
            throw new MySqlMcpException('Impossible de récupérer le statut du serveur: ' . $e->getMessage());
        }
    }

    /**
     * Obtient des informations détaillées sur une table
     */
    private function getTableInfo(\PDO $pdo, string $table, ?string $database): array
    {
        try {
            $query = "
                SELECT 
                    table_rows as row_count,
                    data_length as data_size,
                    index_length as index_size,
                    engine,
                    table_collation
                FROM information_schema.TABLES 
                WHERE table_schema = COALESCE(?, DATABASE()) 
                AND table_name = ?
            ";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([$database, $table]);
            $info = $stmt->fetch();
            
            return [
                'name' => $table,
                'engine' => $info['engine'] ?? 'Unknown',
                'collation' => $info['table_collation'] ?? 'Unknown',
                'row_count' => (int)($info['row_count'] ?? 0),
                'data_size' => (int)($info['data_size'] ?? 0),
                'index_size' => (int)($info['index_size'] ?? 0),
                'total_size' => (int)($info['data_size'] ?? 0) + (int)($info['index_size'] ?? 0)
            ];
            
        } catch (\Exception $e) {
            return [
                'name' => $table,
                'engine' => 'Unknown',
                'collation' => 'Unknown',
                'row_count' => 0,
                'data_size' => 0,
                'index_size' => 0,
                'total_size' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Groupe les index par nom
     */
    private function groupIndexes(array $indexes): array
    {
        $grouped = [];
        
        foreach ($indexes as $index) {
            $keyName = $index['Key_name'];
            
            if (!isset($grouped[$keyName])) {
                $grouped[$keyName] = [
                    'name' => $keyName,
                    'unique' => $index['Non_unique'] == 0,
                    'type' => $index['Index_type'],
                    'columns' => []
                ];
            }
            
            $grouped[$keyName]['columns'][] = [
                'column' => $index['Column_name'],
                'sequence' => (int)$index['Seq_in_index'],
                'collation' => $index['Collation']
            ];
        }
        
        return array_values($grouped);
    }
}