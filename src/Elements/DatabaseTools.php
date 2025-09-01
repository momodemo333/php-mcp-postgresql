<?php

declare(strict_types=1);

namespace PostgreSqlMcp\Elements;

use PostgreSqlMcp\Services\ConnectionService;
use PostgreSqlMcp\Services\SecurityService;
use PostgreSqlMcp\Exceptions\PostgreSqlMcpException;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Psr\Log\LoggerInterface;

/**
 * Outils MCP pour la gestion des bases de données et tables PostgreSQL
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
    #[McpTool(name: 'pgsql_list_databases')]
    public function listDatabases(): array
    {
        $pdo = $this->connectionService->getConnection();
        
        try {
            // PostgreSQL: requête pour lister les bases de données
            $stmt = $pdo->query('SELECT datname FROM pg_database WHERE datistemplate = false ORDER BY datname');
            $databases = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            
            // Filtre les bases système PostgreSQL
            $systemDatabases = ['postgres', 'template0', 'template1'];
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
            throw new PostgreSqlMcpException('Impossible de lister les bases de données: ' . $e->getMessage());
        } finally {
            // Solution 1: Fermer systématiquement la connexion pour éviter les timeouts PostgreSQL
            $this->connectionService->closeConnection($pdo);
        }
    }

    /**
     * Liste toutes les tables d'une base de données
     */
    #[McpTool(name: 'pgsql_list_tables')]
    public function listTables(
        #[Schema(type: 'string', description: 'Nom de la base de données (optionnel en mode multi-DB)')]
        ?string $database = null,
        
        #[Schema(type: 'boolean', description: 'Récupérer les informations détaillées (défaut: false pour économiser les tokens)')]
        bool $detailed = false,
        
        #[Schema(type: 'integer', description: 'Limite le nombre de tables retournées (défaut: 50)', minimum: 1, maximum: 500)]
        int $limit = 50
    ): array {
        $pdo = $this->connectionService->getConnection();
        
        try {
            // PostgreSQL: utilise information_schema ou pg_tables
            if ($database) {
                // Sélectionne une base spécifique
                $pdo->exec("SET search_path TO public");
                $stmt = $pdo->prepare("
                    SELECT tablename AS table_name
                    FROM pg_tables 
                    WHERE schemaname = 'public' 
                    AND tableowner = current_user
                    ORDER BY tablename
                ");
                $stmt->execute();
            } else {
                // Utilise la base courante
                $stmt = $pdo->query("
                    SELECT tablename AS table_name
                    FROM pg_tables 
                    WHERE schemaname = 'public'
                    ORDER BY tablename
                ");
            }
            
            $tablesRaw = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $totalTableCount = count($tablesRaw);
            
            // Applique la limite
            if ($totalTableCount > $limit) {
                $tablesRaw = array_slice($tablesRaw, 0, $limit);
            }
            
            // Récupère des infos sur chaque table selon le mode
            $tableDetails = [];
            foreach ($tablesRaw as $tableRow) {
                $table = $tableRow['table_name'];
                if ($detailed) {
                    $tableInfo = $this->getTableInfo($pdo, $table, $database);
                } else {
                    // Mode simple : juste le nom et quelques infos de base
                    $tableInfo = $this->getTableInfoSimple($pdo, $table, $database);
                }
                $tableDetails[] = $tableInfo;
            }
            
            $this->logger->info('Tables listées', [
                'database' => $database ?: 'current',
                'table_count' => count($tableDetails),
                'detailed' => $detailed,
                'limited_to' => $limit
            ]);
            
            return [
                'database' => $database,
                'tables' => $tableDetails,
                'table_count' => count($tableDetails),
                'total_table_count' => $totalTableCount,
                'detailed' => $detailed,
                'limited_to' => $limit,
                'truncated' => $totalTableCount > $limit
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du listage des tables', [
                'database' => $database,
                'error' => $e->getMessage()
            ]);
            throw new PostgreSqlMcpException('Impossible de lister les tables: ' . $e->getMessage());
        } finally {
            // Solution 1: Fermer systématiquement la connexion pour éviter les timeouts PostgreSQL
            $this->connectionService->closeConnection($pdo);
        }
    }

    /**
     * Décrit la structure d'une table
     */
    #[McpTool(name: 'pgsql_describe_table')]
    public function describeTable(
        #[Schema(type: 'string', description: 'Nom de la base de données (optionnel)')]
        ?string $database = null,
        
        #[Schema(type: 'string', description: 'Nom de la table')]
        string $table = ''
    ): array {
        // Si l'ancien format est utilisé (table en premier), on inverse
        if ($table === '' && $database !== null) {
            $table = $database;
            $database = null;
        }
        return $this->connectionService->executeWithRetry(function() use ($table, $database) {
            $pdo = $this->connectionService->getConnection();
            
            try {
                // PostgreSQL: utilise information_schema pour décrire la table
                $schemaName = 'public'; // Par défaut dans PostgreSQL
                
                $columnsQuery = "
                    SELECT 
                        column_name,
                        data_type,
                        character_maximum_length,
                        is_nullable,
                        column_default,
                        numeric_precision,
                        numeric_scale
                    FROM information_schema.columns
                    WHERE table_schema = :schema
                    AND table_name = :table
                    ORDER BY ordinal_position
                ";
                
                $stmt = $pdo->prepare($columnsQuery);
                $stmt->execute(['schema' => $schemaName, 'table' => $table]);
                $columns = $stmt->fetchAll();
                
                // Récupère les index PostgreSQL
                $indexQuery = "
                    SELECT 
                        indexname,
                        indexdef
                    FROM pg_indexes
                    WHERE schemaname = :schema
                    AND tablename = :table
                ";
                
                $indexStmt = $pdo->prepare($indexQuery);
                $indexStmt->execute(['schema' => $schemaName, 'table' => $table]);
                $indexes = $indexStmt->fetchAll();
                
                // Récupère les contraintes (foreign keys) - PostgreSQL
                $constraintsQuery = "
                    SELECT 
                        tc.constraint_name,
                        kcu.column_name,
                        ccu.table_schema AS referenced_table_schema,
                        ccu.table_name AS referenced_table_name,
                        ccu.column_name AS referenced_column_name
                    FROM information_schema.table_constraints AS tc 
                    JOIN information_schema.key_column_usage AS kcu
                        ON tc.constraint_name = kcu.constraint_name
                        AND tc.table_schema = kcu.table_schema
                    JOIN information_schema.constraint_column_usage AS ccu
                        ON ccu.constraint_name = tc.constraint_name
                        AND ccu.table_schema = tc.table_schema
                    WHERE tc.constraint_type = 'FOREIGN KEY' 
                    AND tc.table_schema = :schema
                    AND tc.table_name = :table
                ";
                
                $constraintsStmt = $pdo->prepare($constraintsQuery);
                $constraintsStmt->execute(['schema' => $schemaName, 'table' => $table]);
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
                throw new PostgreSqlMcpException('Impossible de décrire la table: ' . $e->getMessage());
            } finally {
                // Solution 1: Fermer systématiquement la connexion pour éviter les timeouts PostgreSQL
            $this->connectionService->closeConnection($pdo);
            }
        });
    }

    /**
     * Liste uniquement les noms des tables (ultra-économe en tokens)
     */
    #[McpTool(name: 'mysql_list_table_names')]
    public function listTableNames(
        #[Schema(type: 'string', description: 'Nom de la base de données (optionnel en mode multi-DB)')]
        ?string $database = null,
        
        #[Schema(type: 'integer', description: 'Limite le nombre de tables retournées (défaut: 100)', minimum: 1, maximum: 1000)]
        int $limit = 100
    ): array {
        $pdo = $this->connectionService->getConnection();
        
        try {
            // PostgreSQL: utilise information_schema ou pg_tables
            if ($database) {
                // Sélectionne une base spécifique
                $pdo->exec("SET search_path TO public");
                $stmt = $pdo->prepare("
                    SELECT tablename 
                    FROM pg_tables 
                    WHERE schemaname = 'public' 
                    AND tableowner = current_user
                    ORDER BY tablename
                ");
                $stmt->execute();
            } else {
                // Utilise la base courante
                $stmt = $pdo->query("
                    SELECT tablename 
                    FROM pg_tables 
                    WHERE schemaname = 'public'
                    ORDER BY tablename
                ");
            }
            
            $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            $totalCount = count($tables);
            
            // Applique la limite
            if ($totalCount > $limit) {
                $tables = array_slice($tables, 0, $limit);
            }
            
            $this->logger->info('Noms de tables listés', [
                'database' => $database ?: 'current',
                'table_count' => count($tables),
                'total_count' => $totalCount
            ]);
            
            return [
                'database' => $database,
                'table_names' => $tables,
                'count' => count($tables),
                'total_count' => $totalCount,
                'truncated' => $totalCount > $limit
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du listage des noms de tables', [
                'database' => $database,
                'error' => $e->getMessage()
            ]);
            throw new PostgreSqlMcpException('Impossible de lister les noms de tables: ' . $e->getMessage());
        } finally {
            // Solution 1: Fermer systématiquement la connexion pour éviter les timeouts PostgreSQL
            $this->connectionService->closeConnection($pdo);
        }
    }

    /**
     * Obtient le statut du serveur PostgreSQL
     */
    #[McpTool(name: 'pgsql_server_status')]
    public function getServerStatus(): array
    {
        return $this->connectionService->executeWithRetry(function() {
            try {
                $serverInfo = $this->connectionService->getServerInfo();
                
                $pdo = $this->connectionService->getConnection();
                
                // Récupère des statistiques PostgreSQL
                $statsQuery = "
                    SELECT 
                        numbackends as connections,
                        xact_commit + xact_rollback as queries,
                        stats_reset
                    FROM pg_stat_database 
                    WHERE datname = current_database()
                ";
                $statusStmt = $pdo->query($statsQuery);
                $statusData = $statusStmt->fetch();
                
                // Solution 1: Fermer systématiquement la connexion pour éviter les timeouts PostgreSQL
            $this->connectionService->closeConnection($pdo);
                
                $result = array_merge($serverInfo, [
                    'postgresql_connections' => (int)($statusData['connections'] ?? 0),
                    'postgresql_queries' => (int)($statusData['queries'] ?? 0),
                    'stats_reset' => $statusData['stats_reset'] ?? null,
                    'connection_test' => $this->connectionService->testConnection()
                ]);
                
                $this->logger->info('Statut serveur récupéré', $result);
                
                return $result;
                
            } catch (\Exception $e) {
                $this->logger->error('Erreur récupération statut serveur', ['error' => $e->getMessage()]);
                throw new PostgreSqlMcpException('Impossible de récupérer le statut du serveur: ' . $e->getMessage());
            }
        });
    }

    /**
     * Obtient des informations détaillées sur une table
     */
    private function getTableInfo(\PDO $pdo, string $table, ?string $database): array
    {
        try {
            // PostgreSQL: utilise pg_stat_user_tables pour les statistiques
            $query = "
                SELECT 
                    n_live_tup as row_count,
                    pg_total_relation_size(c.oid) as total_size,
                    pg_table_size(c.oid) as data_size,
                    pg_indexes_size(c.oid) as index_size
                FROM pg_stat_user_tables t
                JOIN pg_class c ON c.relname = t.relname
                WHERE t.schemaname = 'public'
                AND t.relname = ?
            ";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([$table]);
            $info = $stmt->fetch();
            
            return [
                'name' => $table,
                'row_count' => (int)($info['row_count'] ?? 0),
                'total_size' => (int)($info['total_size'] ?? 0),
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
     * Groupe les index par nom (PostgreSQL)
     */
    private function groupIndexes(array $indexes): array
    {
        $grouped = [];
        
        foreach ($indexes as $index) {
            // PostgreSQL retourne 'indexname' et 'indexdef'
            $keyName = $index['indexname'] ?? $index['Key_name'] ?? '';
            
            if (!isset($grouped[$keyName])) {
                $grouped[$keyName] = [
                    'name' => $keyName,
                    'definition' => $index['indexdef'] ?? '',
                    'unique' => strpos($index['indexdef'] ?? '', 'UNIQUE') !== false,
                    'type' => 'BTREE', // PostgreSQL utilise principalement BTREE
                    'columns' => []
                ];
            }
        }
        
        return array_values($grouped);
    }
    
    /**
     * Obtient des informations basiques sur une table (mode économie de tokens)
     */
    private function getTableInfoSimple(\PDO $pdo, string $table, ?string $database): array
    {
        // Suppression des paramètres inutilisés pour éviter les warnings
        unset($pdo, $database);
        
        // Retourne seulement les informations essentielles pour économiser les tokens
        return [
            'table_name' => $table
        ];
    }
}