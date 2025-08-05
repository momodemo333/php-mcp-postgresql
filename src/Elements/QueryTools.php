<?php

declare(strict_types=1);

namespace MySqlMcp\Elements;

use MySqlMcp\Services\ConnectionService;
use MySqlMcp\Services\SecurityService;
use MySqlMcp\Exceptions\MySqlMcpException;
use MySqlMcp\Exceptions\QueryException;
use MySqlMcp\Exceptions\SecurityException;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Psr\Log\LoggerInterface;

/**
 * Outils MCP pour l'exécution de requêtes SQL
 */
class QueryTools
{
    private ConnectionService $connectionService;
    private SecurityService $securityService;
    private LoggerInterface $logger;
    private array $config;

    public function __construct(
        ConnectionService $connectionService,
        SecurityService $securityService,
        LoggerInterface $logger,
        array $config = []
    ) {
        $this->connectionService = $connectionService;
        $this->securityService = $securityService;
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * Exécute une requête SELECT avec validation de sécurité
     */
    #[McpTool(name: 'mysql_select')]
    public function executeSelect(
        #[Schema(type: 'string', description: 'Requête SELECT à exécuter')]
        string $query,
        
        #[Schema(type: 'array', description: 'Paramètres pour requête préparée (optionnel)')]
        array $params = [],
        
        #[Schema(type: 'integer', minimum: 1, maximum: 10000, description: 'Limite de résultats (optionnel)')]
        ?int $limit = null
    ): array {
        // Validation de sécurité
        $this->securityService->validateQuery($query, 'SELECT');
        
        // Ajout automatique de LIMIT si nécessaire
        if ($limit && !preg_match('/\bLIMIT\b/i', $query)) {
            $query .= " LIMIT " . $limit;
        }
        
        $pdo = $this->connectionService->getConnection();
        $startTime = microtime(true);
        
        try {
            if (!empty($params)) {
                $stmt = $pdo->prepare($query);
                $stmt->execute($params);
            } else {
                $stmt = $pdo->query($query);
            }
            
            $results = $stmt->fetchAll();
            $rowCount = count($results);
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            // Vérification limite de résultats
            $this->securityService->checkResultLimit($rowCount);
            
            $this->logger->info('Requête SELECT exécutée', [
                'query' => substr($query, 0, 100),
                'params_count' => count($params),
                'result_count' => $rowCount,
                'execution_time_ms' => $executionTime
            ]);
            
            return [
                'query' => $query,
                'results' => $results,
                'row_count' => $rowCount,
                'execution_time_ms' => $executionTime,
                'has_more' => false // TODO: Implémenter pagination
            ];
            
        } catch (\PDOException $e) {
            $this->logger->error('Erreur requête SELECT', [
                'query' => substr($query, 0, 100),
                'error' => $e->getMessage()
            ]);
            throw new QueryException('Erreur lors de l\'exécution de la requête: ' . $e->getMessage());
        } finally {
            $this->connectionService->releaseConnection($pdo);
        }
    }

    /**
     * Exécute une requête INSERT
     */
    #[McpTool(name: 'mysql_insert')]
    public function executeInsert(
        #[Schema(type: 'string', description: 'Nom de la table')]
        string $table,
        
        #[Schema(type: 'object', description: 'Données à insérer (clé => valeur)')]
        array $data,
        
        #[Schema(type: 'string', description: 'Base de données (optionnel)')]
        ?string $database = null
    ): array {
        // Validation de sécurité
        $this->securityService->validateQuery("INSERT INTO {$table}", 'INSERT');
        
        if (empty($data)) {
            throw new QueryException('Aucune donnée à insérer');
        }
        
        $pdo = $this->connectionService->getConnection();
        $startTime = microtime(true);
        
        try {
            // Construction de la requête
            $tableName = $database ? "`{$database}`.`{$table}`" : "`{$table}`";
            $columns = array_keys($data);
            $placeholders = ':' . implode(', :', $columns);
            $columnList = '`' . implode('`, `', $columns) . '`';
            
            $query = "INSERT INTO {$tableName} ({$columnList}) VALUES ({$placeholders})";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute($data);
            
            $insertId = $pdo->lastInsertId();
            $affectedRows = $stmt->rowCount();
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            $this->logger->info('Requête INSERT exécutée', [
                'table' => $table,
                'database' => $database,
                'columns' => count($data),
                'insert_id' => $insertId,
                'affected_rows' => $affectedRows,
                'execution_time_ms' => $executionTime
            ]);
            
            return [
                'query' => $query,
                'table' => $table,
                'database' => $database,
                'insert_id' => $insertId ?: null,
                'affected_rows' => $affectedRows,
                'execution_time_ms' => $executionTime,
                'inserted_data' => $data
            ];
            
        } catch (\PDOException $e) {
            $this->logger->error('Erreur requête INSERT', [
                'table' => $table,
                'database' => $database,
                'error' => $e->getMessage()
            ]);
            throw new QueryException('Erreur lors de l\'insertion: ' . $e->getMessage());
        } finally {
            $this->connectionService->releaseConnection($pdo);
        }
    }

    /**
     * Exécute une requête UPDATE
     */
    #[McpTool(name: 'mysql_update')]
    public function executeUpdate(
        #[Schema(type: 'string', description: 'Nom de la table')]
        string $table,
        
        #[Schema(type: 'object', description: 'Données à mettre à jour (clé => valeur)')]
        array $data,
        
        #[Schema(type: 'object', description: 'Conditions WHERE (clé => valeur)')]
        array $conditions,
        
        #[Schema(type: 'string', description: 'Base de données (optionnel)')]
        ?string $database = null
    ): array {
        // Validation de sécurité
        $this->securityService->validateQuery("UPDATE {$table}", 'UPDATE');
        
        if (empty($data)) {
            throw new QueryException('Aucune donnée à mettre à jour');
        }
        
        if (empty($conditions)) {
            throw new SecurityException('UPDATE sans conditions WHERE non autorisé pour la sécurité');
        }
        
        $pdo = $this->connectionService->getConnection();
        $startTime = microtime(true);
        
        try {
            // Construction de la requête
            $tableName = $database ? "`{$database}`.`{$table}`" : "`{$table}`";
            
            // SET clause
            $setParts = [];
            foreach ($data as $column => $value) {
                $setParts[] = "`{$column}` = :set_{$column}";
            }
            $setClause = implode(', ', $setParts);
            
            // WHERE clause
            $whereParts = [];
            foreach ($conditions as $column => $value) {
                $whereParts[] = "`{$column}` = :where_{$column}";
            }
            $whereClause = implode(' AND ', $whereParts);
            
            $query = "UPDATE {$tableName} SET {$setClause} WHERE {$whereClause}";
            
            // Préparation des paramètres
            $params = [];
            foreach ($data as $column => $value) {
                $params["set_{$column}"] = $value;
            }
            foreach ($conditions as $column => $value) {
                $params["where_{$column}"] = $value;
            }
            
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            
            $affectedRows = $stmt->rowCount();
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            $this->logger->info('Requête UPDATE exécutée', [
                'table' => $table,
                'database' => $database,
                'set_columns' => count($data),
                'where_conditions' => count($conditions),
                'affected_rows' => $affectedRows,
                'execution_time_ms' => $executionTime
            ]);
            
            return [
                'query' => $query,
                'table' => $table,
                'database' => $database,
                'affected_rows' => $affectedRows,
                'execution_time_ms' => $executionTime,
                'updated_data' => $data,
                'conditions' => $conditions
            ];
            
        } catch (\PDOException $e) {
            $this->logger->error('Erreur requête UPDATE', [
                'table' => $table,
                'database' => $database,
                'error' => $e->getMessage()
            ]);
            throw new QueryException('Erreur lors de la mise à jour: ' . $e->getMessage());
        } finally {
            $this->connectionService->releaseConnection($pdo);
        }
    }

    /**
     * Exécute une requête DELETE
     */
    #[McpTool(name: 'mysql_delete')]
    public function executeDelete(
        #[Schema(type: 'string', description: 'Nom de la table')]
        string $table,
        
        #[Schema(type: 'object', description: 'Conditions WHERE (clé => valeur)')]
        array $conditions,
        
        #[Schema(type: 'string', description: 'Base de données (optionnel)')]
        ?string $database = null,
        
        #[Schema(type: 'integer', minimum: 1, maximum: 1000, description: 'Limite de suppressions (sécurité)')]
        ?int $limit = null
    ): array {
        // Validation de sécurité
        $this->securityService->validateQuery("DELETE FROM {$table}", 'DELETE');
        
        if (empty($conditions)) {
            throw new SecurityException('DELETE sans conditions WHERE non autorisé pour la sécurité');
        }
        
        $pdo = $this->connectionService->getConnection();
        $startTime = microtime(true);
        
        try {
            // Construction de la requête
            $tableName = $database ? "`{$database}`.`{$table}`" : "`{$table}`";
            
            // WHERE clause
            $whereParts = [];
            foreach ($conditions as $column => $value) {
                $whereParts[] = "`{$column}` = :{$column}";
            }
            $whereClause = implode(' AND ', $whereParts);
            
            $query = "DELETE FROM {$tableName} WHERE {$whereClause}";
            
            // Ajout de LIMIT si spécifié
            if ($limit) {
                $query .= " LIMIT {$limit}";
            }
            
            $stmt = $pdo->prepare($query);
            $stmt->execute($conditions);
            
            $affectedRows = $stmt->rowCount();
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            $this->logger->info('Requête DELETE exécutée', [
                'table' => $table,
                'database' => $database,
                'conditions' => count($conditions),
                'affected_rows' => $affectedRows,
                'limit' => $limit,
                'execution_time_ms' => $executionTime
            ]);
            
            return [
                'query' => $query,
                'table' => $table,
                'database' => $database,
                'affected_rows' => $affectedRows,
                'execution_time_ms' => $executionTime,
                'conditions' => $conditions,
                'limit' => $limit
            ];
            
        } catch (\PDOException $e) {
            $this->logger->error('Erreur requête DELETE', [
                'table' => $table,
                'database' => $database,
                'error' => $e->getMessage()
            ]);
            throw new QueryException('Erreur lors de la suppression: ' . $e->getMessage());
        } finally {
            $this->connectionService->releaseConnection($pdo);
        }
    }

    /**
     * Exécute une requête SQL personnalisée (pour requêtes complexes)
     */
    #[McpTool(name: 'mysql_execute_query')]
    public function executeCustomQuery(
        #[Schema(type: 'string', description: 'Requête SQL à exécuter')]
        string $query,
        
        #[Schema(type: 'array', description: 'Paramètres pour requête préparée (optionnel)')]
        array $params = []
    ): array {
        // Détection du type d'opération
        $operation = $this->detectQueryOperation($query);
        
        // Validation de sécurité
        $this->securityService->validateQuery($query, $operation);
        
        $pdo = $this->connectionService->getConnection();
        $startTime = microtime(true);
        
        try {
            if (!empty($params)) {
                $stmt = $pdo->prepare($query);
                $stmt->execute($params);
            } else {
                $stmt = $pdo->query($query);
            }
            
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            // Gestion du résultat selon le type d'opération
            if (in_array($operation, ['SELECT', 'SHOW', 'DESCRIBE', 'EXPLAIN'])) {
                $results = $stmt->fetchAll();
                $rowCount = count($results);
                
                $this->securityService->checkResultLimit($rowCount);
                
                $response = [
                    'query' => $query,
                    'operation' => $operation,
                    'results' => $results,
                    'row_count' => $rowCount,
                    'execution_time_ms' => $executionTime
                ];
            } else {
                $affectedRows = $stmt->rowCount();
                $insertId = $pdo->lastInsertId();
                
                $response = [
                    'query' => $query,
                    'operation' => $operation,
                    'affected_rows' => $affectedRows,
                    'insert_id' => $insertId ?: null,
                    'execution_time_ms' => $executionTime
                ];
            }
            
            $this->logger->info('Requête personnalisée exécutée', [
                'operation' => $operation,
                'params_count' => count($params),
                'execution_time_ms' => $executionTime
            ]);
            
            return $response;
            
        } catch (\PDOException $e) {
            $this->logger->error('Erreur requête personnalisée', [
                'operation' => $operation,
                'error' => $e->getMessage()
            ]);
            throw new QueryException('Erreur lors de l\'exécution: ' . $e->getMessage());
        } finally {
            $this->connectionService->releaseConnection($pdo);
        }
    }

    /**
     * Détecte le type d'opération d'une requête SQL
     */
    private function detectQueryOperation(string $query): string
    {
        $query = trim(strtoupper($query));
        
        $operations = ['SELECT', 'INSERT', 'UPDATE', 'DELETE', 'SHOW', 'DESCRIBE', 'EXPLAIN', 'CREATE', 'DROP', 'ALTER'];
        
        foreach ($operations as $operation) {
            if (strpos($query, $operation) === 0) {
                return $operation;
            }
        }
        
        return 'UNKNOWN';
    }
}